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
  $stmt = $con->prepare("SELECT 
      o.Order_ID,
      o.Order_Date,
      o.order_status,
      COALESCE(o.Order_Amount, o.total_amount, 0) AS Order_Amount,
      o.Street, o.City, o.Contact_Number,
      o.order_type,
      c.Customer_Name,
      p.Payment_ID, p.Payment_Status, p.Payment_Method
    FROM orders o
    LEFT JOIN customers c ON c.Customer_ID = o.Customer_ID
    LEFT JOIN payments p ON p.Order_ID = o.Order_ID
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
  try { $unpaidPayments = (int)$con->query("SELECT COUNT(*) FROM payments WHERE Payment_Status='Unpaid'")->fetchColumn(); } catch(Throwable $e) {}
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
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
?>
