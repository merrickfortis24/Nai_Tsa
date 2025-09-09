<?php
// Hardened delivery tracking endpoint: returns JSON only
session_start();
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/database.php';
$db = new database();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order_id']);
    exit;
}

try {
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT order_status, Driver_Status, driver_lat, driver_lng FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid LIMIT 1");
    $stmt->execute([':oid' => $order_id, ':cid' => $_SESSION['customer_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $driverStatus = $row['Driver_Status'] ?? null;
    $orderStatus  = $row['order_status'] ?? 'Unknown';
    $derived = $orderStatus;
    if (in_array($driverStatus, ['on_the_way','picked_up'], true)) {
        $derived = 'On the way';
    } elseif ($orderStatus === 'Processing') {
        $derived = 'Preparing';
    }
    $terminal = in_array($orderStatus, ['Delivered','Received','Cancelled'], true);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_status' => $orderStatus,
        'driver_status' => $driverStatus,
        'derived_status' => $derived,
        'lat' => isset($row['driver_lat']) ? (float)$row['driver_lat'] : null,
        'lng' => isset($row['driver_lng']) ? (float)$row['driver_lng'] : null,
        'terminal' => $terminal
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}