<?php
session_start();
require_once "classes/database.php";
$db = new database();

$user_id = $_SESSION['customer_id'] ?? null;
$orders = [];
if ($user_id) {
    // Get all orders for this user (you can filter by status if you want)
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT Order_ID, order_status, Driver_Status FROM orders WHERE Customer_ID = ? ORDER BY Order_Date DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orders = array_map(function($r){
        if (in_array($r['Driver_Status'] ?? '', ['on_the_way','picked_up'], true)) {
            $r['display_status'] = 'Out for delivery';
        } elseif (($r['order_status'] ?? '') === 'Processing') {
            $r['display_status'] = 'Preparing';
        } else {
            $r['display_status'] = $r['order_status'];
        }
        return $r;
    }, $rows);
}
header('Content-Type: application/json');
echo json_encode(['orders' => $orders]);