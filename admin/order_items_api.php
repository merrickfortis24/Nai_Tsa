<?php
// order_items_api.php - returns JSON for items of a given order (admin side)
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
require_once 'classes/database.php';
$db = new database();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid order id']); exit;
}
try {
    $items = $db->fetchOrderItems($orderId); // Ensure method exists; implement if not
    echo json_encode(['success'=>true,'items'=>$items]);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
