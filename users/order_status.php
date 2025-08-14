<?php
session_start();
require_once "classes/database.php";
$db = new database();

$user_id = $_SESSION['customer_id'] ?? null;
$orders = [];
if ($user_id) {
    // Get all orders for this user (you can filter by status if you want)
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT Order_ID, order_status FROM orders WHERE Customer_ID = ? ORDER BY Order_Date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode(['orders' => $orders]);