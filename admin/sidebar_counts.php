<?php

require_once('classes/database.php');
$db = new database();

// Fetch all orders
$orders = [];
try {
    $orders = $db->fetchOrders();
} catch (PDOException $e) {
    $orders = [];
}
$pendingProcessingCount = 0;
foreach ($orders as $order) {
    if ($order['order_status'] === 'Pending' || $order['order_status'] === 'Processing') {
        $pendingProcessingCount++;
    }
}

// Fetch all payments
$payments = [];
try {
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT payment_status FROM payment");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $payments = [];
}
$unpaidPayments = 0;
foreach ($payments as $payment) {
    if (($payment['payment_status'] ?? '') === 'Unpaid') {
        $unpaidPayments++;
    }
}
?>