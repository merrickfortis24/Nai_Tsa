<?php
session_start();
require_once "classes/database.php";
$db = new database();

$order_id = $_GET['order_id'] ?? null;
$response = ['status' => 'Unknown'];

if ($order_id) {
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT order_status, Driver_Status, driver_lat, driver_lng FROM orders WHERE Order_ID = ?");
    $stmt->execute([$order_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // Derived status mapping
        if (in_array($row['Driver_Status'] ?? '', ['on_the_way','picked_up'], true)) {
            $response['status'] = 'Out for delivery';
        } elseif (($row['order_status'] ?? '') === 'Processing') {
            $response['status'] = 'Preparing';
        } else {
            $response['status'] = $row['order_status'];
        }
        $response['lat'] = $row['driver_lat'] ?? null;
        $response['lng'] = $row['driver_lng'] ?? null;
    }
}
header('Content-Type: application/json');
echo json_encode($response);