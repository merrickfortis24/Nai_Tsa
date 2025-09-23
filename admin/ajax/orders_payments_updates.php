<?php
// Return newly created orders (and related payment info) with Order_ID greater than provided last_id.
// Used by admin/orders_payments.php to auto-append new orders without full page reload.
session_start();
header('Content-Type: application/json; charset=UTF-8');

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
      COALESCE(addr.Street, o.Street) AS Street,
      COALESCE(addr.Barangay, o.Barangay) AS Barangay,
      COALESCE(addr.City, o.City) AS City,
      COALESCE(addr.Contact_Number, o.Contact_Number, c.Contact_Number) AS Contact_Number,
      COALESCE(addr.customer_lat, o.customer_lat) AS customer_lat,
      COALESCE(addr.customer_lng, o.customer_lng) AS customer_lng,
      o.order_type,
      c.Customer_Name AS Customer_Name,
      p.Payment_ID AS Payment_ID,
      p.payment_status AS Payment_Status,
      p.Payment_Method AS Payment_Method
    FROM orders o
    LEFT JOIN order_address addr ON addr.Order_ID = o.Order_ID
    LEFT JOIN customer c ON c.Customer_ID = o.Customer_ID
    LEFT JOIN payment p ON p.Order_ID = o.Order_ID
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
    // If admin explicitly requested debug, include full details in the JSON response to aid debugging.
    if (!empty($debugRequest)) {
      $trace = method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null;
      echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $trace
      ]);
    } else {
      // Generic response for non-debug calls
      echo json_encode(['success'=>false,'message'=>'Server error']);
    }
}
?>
