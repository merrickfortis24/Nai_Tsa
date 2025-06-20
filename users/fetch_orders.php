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
    $stmt = $db->opencon()->prepare("
        SELECT o.*, p.payment_status 
        FROM orders o
        LEFT JOIN payment p ON o.Order_ID = p.Order_ID
        WHERE o.Customer_ID = ?
        ORDER BY o.Order_Date DESC
    ");
    $stmt->execute([$user_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_orders as $order) {
        // Fetch items for this order
        $items = $orderObj->getOrderItems($order['Order_ID']);
        $order['items'] = $items; // Add items to the order array

        if ($order['order_status'] === 'Pending' && $order['payment_status'] === 'Unpaid') {
            $orders_by_status['To Ship'][] = $order;
        } elseif ($order['order_status'] === 'Processing' && $order['payment_status'] === 'Paid') {
            $orders_by_status['To Receive'][] = $order;
        } elseif ($order['order_status'] === 'Delivered') {
            $orders_by_status['Delivered'][] = $order;
        }
    }
}
header('Content-Type: application/json');
echo json_encode($orders_by_status);