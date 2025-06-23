<?php

session_start();
require_once "classes/database.php";
require_once "classes/order.php";
$db = new database();
$orderObj = new Order();

$user_id = $_SESSION['customer_id'] ?? null;
$orders_by_status = [
    'To Ship' => [],
    'To Receive' => [],
    'Delivered' => []
];
if ($user_id) {
    $orders_by_status = $db->getOrdersByStatusWithItems($user_id, $orderObj);
}
header('Content-Type: application/json');
echo json_encode($orders_by_status);