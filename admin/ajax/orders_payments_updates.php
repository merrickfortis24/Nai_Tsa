<?php
// Return newly created orders (and related payment info) with Order_ID greater than provided last_id.
// Used by admin/orders_payments.php to auto-append new orders without full page reload.
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

require_once __DIR__ . '/../classes/database.php';
$db = new database();

try {
  $con = $db->opencon();

  // Fetch new rows
  // Use singular table names that the rest of the codebase expects (customer, payment)
  $stmt = $con->prepare("SELECT 
      o.Order_ID,
      o.Order_Date,
      o.order_status,
      COALESCE(o.Order_Amount, o.total_amount, 0) AS Order_Amount,
      o.Street, o.City, o.Contact_Number,
      o.order_type,
      -- customer name may be stored with different case in some schemas
      COALESCE(c.Customer_Name, c.customer_name) AS Customer_Name,
      -- payment columns: support either camelCase or snake_case column names
      COALESCE(p.Payment_ID, p.payment_id) AS Payment_ID,
      COALESCE(p.Payment_Status, p.payment_status) AS Payment_Status,
      COALESCE(p.Payment_Method, p.payment_method) AS Payment_Method
    FROM orders o
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
  try { $unpaidPayments = (int)$con->query("SELECT COUNT(*) FROM payment WHERE Payment_Status='Unpaid'")->fetchColumn(); } catch(Throwable $e) {}
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
  // Log full exception for server-side debugging
  error_log('ajax/orders_payments_updates.php exception: ' . $e->getMessage());
  http_response_code(500);
  // Return a helpful message (may include DB error details) to assist debugging in development
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
?>
