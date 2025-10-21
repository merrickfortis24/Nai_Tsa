<?php
// Return newly created orders (and related payment info) with Order_ID greater than provided last_id.
// Used by admin/orders_payments.php to auto-append new orders without full page reload.
session_start();
// We'll set Content-Type dynamically (POST updates vs polling fetch). Default JSON.
header('Content-Type: application/json; charset=UTF-8');

// Note: detailed error reporting is enabled only when an admin explicitly requests
// debug via the `?debug=1` query parameter further below. Avoid turning on
// display_errors unconditionally in production.

// Require an admin session. Debug mode will only be enabled for logged-in admins.
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

// Allow detailed debug output when explicitly requested by an admin via ?debug=1
$debugRequest = isset($_GET['debug']) && (string)$_GET['debug'] === '1';
if ($debugRequest) {
  // Enable rich error reporting temporarily for debugging (admin-only)
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

// --- Optional status/payment update via POST ---
// This lets the admin update order_status or payment_status through this endpoint (AJAX) without full page reload.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once __DIR__ . '/../classes/database.php';
  $db = new database();
  $con = $db->opencon();
  $payload = [ 'success' => false ];
  try {
    // Accept either form-encoded or JSON
    $raw = file_get_contents('php://input');
    $data = $_POST;
    if (!$data && $raw) {
      $j = json_decode($raw, true);
      if (is_array($j)) { $data = $j; }
    }

    if (isset($data['order_id'], $data['order_status'])) {
      $orderId = (int)$data['order_id'];
      $target  = trim((string)$data['order_status']);
      if ($target === '') {
        $payload['message'] = 'Empty status not allowed';
      } else {
          // Determine allowed enum values from DB (cache per request)
          $allowed = [];
          try {
            $inf = $con->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='order_status' LIMIT 1")->fetchColumn();
            if ($inf && preg_match("/^enum\\((.*)\\)$/i", $inf, $m)) {
              $inner = $m[1]; // content inside enum(...)
              // Use str_getcsv to safely parse quoted comma-separated values.
              // ENUM definition lists each value quoted, e.g. 'Pending','Preparing','Delivered'
              $parts = str_getcsv($inner, ',', "'");
              foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $allowed[] = $p; // str_getcsv already strips surrounding single quotes
              }
            }
          } catch (Throwable $e) { /* ignore */ }
          $allowedLower = array_map('strtolower', $allowed);
          $origTarget = $target;
          // Graceful mapping: if UI uses 'Preparing' but DB still has legacy 'Processing'
          if (!in_array($target, $allowed, true)) {
            if (strcasecmp($target,'Preparing')===0 && in_array('Processing', $allowed, true)) {
              $target = 'Processing';
            }
          }
          if ($allowed && !in_array($target, $allowed, true)) {
            $payload['message'] = 'Invalid status "'.$origTarget.'" for current schema';
            echo json_encode($payload); exit;
          }
        // Fetch current
        $curStmt = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = ? LIMIT 1");
        $curStmt->execute([$orderId]);
        $current = $curStmt->fetchColumn();
        if ($current === false) {
          $payload['message'] = 'Order not found';
        } elseif ($current === 'Cancelled') {
          $payload['message'] = 'Cancelled orders are locked';
        } elseif ($current === $target) {
          $payload['message'] = 'No change';
        } else {
          // Gating: If payment method is GCash and there's a pending/rejected receipt, block advancing beyond 'Pending'.
          try {
            $mth = $con->prepare("SELECT p.Payment_Method, p.payment_status FROM payment p WHERE p.Order_ID=? LIMIT 1");
            $mth->execute([$orderId]);
            $pm = $mth->fetch(PDO::FETCH_ASSOC) ?: [];
            $isGCash = isset($pm['Payment_Method']) && strcasecmp($pm['Payment_Method'], 'GCash') === 0;
            if ($isGCash) {
              // Check receipt status
              $rc = $con->prepare("SELECT Status FROM order_payment_receipt WHERE Order_ID=? ORDER BY Payment_Receipt_ID DESC LIMIT 1");
              $rc->execute([$orderId]);
              $rcs = (string)$rc->fetchColumn();
              $hasReceipt = $rcs !== '' && $rcs !== null;
              $verified = strcasecmp($rcs,'verified')===0;
              // Define statuses that require verification to proceed
              $needsVerified = in_array($target, ['Preparing','Ready to deliver','On the way','Delivered','Ready to pick up','Received'], true);
              if ($needsVerified && (!$hasReceipt || !$verified)) {
                $payload['message'] = $hasReceipt ? 'Cannot proceed: GCash receipt not verified yet.' : 'Cannot proceed: GCash receipt not uploaded.';
                echo json_encode($payload); exit;
              }
            }
          } catch (Throwable $e) { /* ignore gating failure */ }
          $upd = $con->prepare("UPDATE orders SET order_status = ? WHERE Order_ID = ?");
          $upd->execute([$target, $orderId]);
          $changed = $upd->rowCount() > 0;
          $payload['success'] = $changed;
          $payload['changed'] = $changed;
          $payload['previous'] = $current;
          $payload['current']  = $target;
          if ($changed) {
              // Verify it actually stored (invalid enum would become '')
              try {
                $vf = $con->prepare("SELECT order_status FROM orders WHERE Order_ID=? LIMIT 1");
                $vf->execute([$orderId]);
                $after = $vf->fetchColumn();
                if ($after === '' || $after === null) {
                  $payload['success'] = false;
                  $payload['changed'] = false;
                  $payload['message'] = 'Update failed: status value not permitted by enum ('.$target.')';
                  $payload['current'] = $current; // revert view
                  echo json_encode($payload); exit;
                }
              } catch (Throwable $e) { /* ignore */ }
            // Attempt sales insert if qualifies (Delivered/Received + Paid)
            try { $db->insertSalesIfDeliveredAndPaid($orderId, (int)($_SESSION['admin_id'] ?? 0)); } catch (Throwable $e) {}
            $payload['message'] = 'Order status updated';
          } else {
            $payload['message'] = 'Update executed but no rows changed';
          }
        }
      }
    } elseif (isset($data['payment_id'], $data['payment_status'])) {
      $paymentId = (int)$data['payment_id'];
      $pstatus   = trim((string)$data['payment_status']);
      if ($pstatus === '') {
        $payload['message'] = 'Empty payment status';
      } else {
        // Gating: For GCash payments, only allow setting to Paid if a receipt exists and is verified
        if (strcasecmp($pstatus,'Paid')===0) {
          try {
            $oidStmt = $con->prepare("SELECT Order_ID, Payment_Method FROM payment WHERE Payment_ID=? LIMIT 1");
            $oidStmt->execute([$paymentId]);
            $row = $oidStmt->fetch(PDO::FETCH_ASSOC);
            $orderId = (int)($row['Order_ID'] ?? 0);
            $isGCash = isset($row['Payment_Method']) && strcasecmp($row['Payment_Method'],'GCash')===0;
            if ($orderId && $isGCash) {
              $rc = $con->prepare("SELECT Status FROM order_payment_receipt WHERE Order_ID=? ORDER BY Payment_Receipt_ID DESC LIMIT 1");
              $rc->execute([$orderId]);
              $rcs = (string)$rc->fetchColumn();
              if (!($rcs && strcasecmp($rcs,'verified')===0)) {
                $payload['message'] = 'Cannot mark Paid: GCash receipt not verified';
                echo json_encode($payload); exit;
              }
            }
          } catch (Throwable $e) { /* ignore */ }
        }
        $upd = $con->prepare("UPDATE payment SET payment_status = ? WHERE Payment_ID = ?");
        $ok  = $upd->execute([$pstatus, $paymentId]);
        if ($ok && $upd->rowCount() > 0) {
          $payload['success'] = true;
          $payload['changed'] = true;
          $payload['message'] = 'Payment status updated';
          // Get associated order to possibly insert sales
            try {
              $oidStmt = $con->prepare("SELECT Order_ID FROM payment WHERE Payment_ID = ? LIMIT 1");
              $oidStmt->execute([$paymentId]);
              $oid = $oidStmt->fetchColumn();
              if ($oid !== false) {
                $db->insertSalesIfDeliveredAndPaid((int)$oid, (int)($_SESSION['admin_id'] ?? 0));
              }
            } catch (Throwable $e) {}
        } else {
          $payload['message'] = 'No payment row updated';
        }
      }
    } else {
      $payload['message'] = 'No recognized update parameters provided';
    }
  } catch (Throwable $e) {
    $payload['message'] = 'Exception: ' . $e->getMessage();
  }
  echo json_encode($payload);
  exit;
}

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

require_once __DIR__ . '/../classes/database.php';
$db = new database();

  try {
  $con = $db->opencon();

  // Quick debug mode: call with ?debug=1 to run a small safe query and return results or full exception details.
  if (isset($_GET['debug']) && (string)$_GET['debug'] === '1') {
    try {
      $one = (int)$con->query('SELECT 1')->fetchColumn();
      $sample = $con->query("SELECT Order_ID, Order_Date, order_status, Order_Amount FROM orders ORDER BY Order_ID DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success' => true, 'debug' => true, 'db_test' => $one, 'sample_rows' => $sample]);
    } catch (Throwable $e) {
      // Return clear debug info to the caller (also logged server-side)
      error_log('ajax/orders_payments_updates.php debug exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
      http_response_code(500);
      echo json_encode(['success' => false, 'debug' => true, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    }
    exit;
  }
  // Fetch new rows
  // Use singular table names that the rest of the codebase expects (customer, payment)
  $stmt = $con->prepare("SELECT 
      o.Order_ID,
      o.Order_Date,
      o.order_status,
  COALESCE(o.Order_Amount, 0) AS Order_Amount,
  COALESCE(addr.Street, '') AS Street,
  COALESCE(addr.Barangay, '') AS Barangay,
  COALESCE(addr.City, '') AS City,
  COALESCE(ca.Contact_Number, c.Contact_Number, '') AS Contact_Number,
  COALESCE(addr.customer_lat, '') AS customer_lat,
  COALESCE(addr.customer_lng, '') AS customer_lng,
  COALESCE(od.Delivery_Fee, 0.00) AS Delivery_Fee,
  COALESCE(od.Delivery_Distance_Km, 0.00) AS Delivery_Distance_Km,
      o.order_type,
      c.Customer_Name AS Customer_Name,
      p.Payment_ID AS Payment_ID,
      p.payment_status AS Payment_Status,
      p.Payment_Method AS Payment_Method
    FROM orders o
    LEFT JOIN order_address addr ON addr.Order_ID = o.Order_ID
    LEFT JOIN customer_address ca ON ca.Customer_ID = o.Customer_ID
    LEFT JOIN customer c ON c.Customer_ID = o.Customer_ID
    LEFT JOIN payment p ON p.Order_ID = o.Order_ID
  LEFT JOIN order_delivery od ON od.Order_ID = o.Order_ID
    WHERE o.Order_ID > ?
    ORDER BY o.Order_ID ASC
    LIMIT 100");
  $stmt->execute([$lastId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $maxId = $lastId;
  foreach($rows as $r){ if((int)$r['Order_ID'] > $maxId) $maxId = (int)$r['Order_ID']; }

  // Lightweight stats (used for small counters on page)
  $totalOrders = (int)$con->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $unpaidPayments = 0;
  try { $unpaidPayments = (int)$con->query("SELECT COUNT(*) FROM payment WHERE payment_status='Unpaid'")->fetchColumn(); } catch(Throwable $e) {}
  $pendingProcessing = 0;
  try { $pendingProcessing = (int)$con->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Pending','Processing')")->fetchColumn(); } catch(Throwable $e) {}

  echo json_encode([
    'success' => true,
    'rows' => $rows,
    'max_id' => $maxId,
    'stats' => [
      'total' => $totalOrders,
      'unpaid' => $unpaidPayments,
      'pending_processing' => $pendingProcessing,
    ]
  ]);
  } catch (Throwable $e) {
    // Log full exception for server-side debugging (message, file/line, trace)
    $errMsg = sprintf("ajax/orders_payments_updates.php exception: %s in %s:%d\nTrace: %s", $e->getMessage(), $e->getFile(), $e->getLine(), method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : 'n/a');
    error_log($errMsg);
    http_response_code(500);
    // Build a helpful JSON payload. Keep full trace only when admin explicitly asked for debug.
    $payload = [
      'success' => false,
      'message' => 'Server error',
      'error' => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine()
    ];
    if (!empty($debugRequest)) {
      $payload['trace'] = method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null;
    }
    echo json_encode($payload);
}
?>
