<?php
// Admin endpoint to verify/reject GCash receipts tied to orders.
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

require_once __DIR__ . '/../classes/database.php';

try {
  $db = new database();
  $con = $db->opencon();

  $raw = file_get_contents('php://input');
  $data = $_POST;
  if (!$data && $raw) { $j = json_decode($raw, true); if (is_array($j)) $data = $j; }

  $action = strtolower(trim((string)($data['action'] ?? ''))); // 'verify' or 'reject'
  $receiptId = isset($data['receipt_id']) ? (int)$data['receipt_id'] : 0;
  $orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
  $rejectReason = trim((string)($data['reason'] ?? ''));
  $adminId = (int)($_SESSION['admin_id'] ?? 0);

  if ($receiptId <= 0) {
    echo json_encode(['success'=>false,'message'=>'Missing receipt id']);
    exit;
  }

  // Fetch receipt
  $rec = $con->prepare("SELECT * FROM order_payment_receipt WHERE Payment_Receipt_ID=? LIMIT 1");
  $rec->execute([$receiptId]);
  $row = $rec->fetch(PDO::FETCH_ASSOC);
  if (!$row) { echo json_encode(['success'=>false,'message'=>'Receipt not found']); exit; }
  if ($orderId && (int)$row['Order_ID'] !== $orderId) { $orderId = (int)$row['Order_ID']; }

  if ($action === 'verify') {
    $u = $con->prepare("UPDATE order_payment_receipt SET Status='verified', Verified_By=?, Verified_At=NOW(), Reject_Reason=NULL WHERE Payment_Receipt_ID=?");
    $u->execute([$adminId, $receiptId]);
    // Optionally set payment_status to Paid if payment exists and admin confirms
    if ($orderId > 0) {
      try {
        $pm = $con->prepare("UPDATE payment SET payment_status='Paid' WHERE Order_ID=?");
        $pm->execute([$orderId]);
        // If the order is already Delivered/Received, insert sales entry
        try { $db->insertSalesIfDeliveredAndPaid($orderId, $adminId); } catch (Throwable $e) {}
      } catch (Throwable $e) { /* ignore */ }
    }
    echo json_encode(['success'=>true,'status'=>'verified']);
    exit;
  } elseif ($action === 'reject') {
    $u = $con->prepare("UPDATE order_payment_receipt SET Status='rejected', Verified_By=?, Verified_At=NOW(), Reject_Reason=? WHERE Payment_Receipt_ID=?");
    $u->execute([$adminId, $rejectReason ?: null, $receiptId]);
    if ($orderId > 0) {
      try {
        // Revert/hold order: set payment back to Unpaid; set status to Pending unless Cancelled
        $con->prepare("UPDATE payment SET payment_status='Unpaid' WHERE Order_ID=?")->execute([$orderId]);
        $cur = $con->prepare("SELECT order_status FROM orders WHERE Order_ID=? LIMIT 1");
        $cur->execute([$orderId]);
        $st = (string)$cur->fetchColumn();
        if ($st !== 'Cancelled') {
          $con->prepare("UPDATE orders SET order_status='Pending' WHERE Order_ID=?")->execute([$orderId]);
        }
      } catch (Throwable $e) { /* ignore */ }
    }
    echo json_encode(['success'=>true,'status'=>'rejected']);
    exit;
  } else {
    echo json_encode(['success'=>false,'message'=>'Invalid action']);
    exit;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
