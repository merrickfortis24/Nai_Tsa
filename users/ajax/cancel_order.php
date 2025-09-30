<?php
session_start();
// Attempt to restore session from remember-me cookie for AJAX endpoints
require_once __DIR__ . '/../../includes/remember.php';
header('Content-Type: application/json');

// Optional: suppress HTML error output so JSON parsing isn't broken
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Auth check
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Correct relative path (file is in users/ajax/, classes is ../classes)
require_once __DIR__ . '/../classes/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Accept either form-data / x-www-form-urlencoded or raw JSON
    $orderId = 0;
    if (isset($_POST['order_id'])) {
        $orderId = (int)$_POST['order_id'];
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['order_id'])) {
                $orderId = (int)$json['order_id'];
            }
        }
    }

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    $db = new database();
    $con = $db->opencon();
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch current status ensuring ownership
    $stmt = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid LIMIT 1");
    $stmt->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
    $currentStatus = $stmt->fetchColumn();

    if ($currentStatus === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if (trim(strtolower($currentStatus)) !== 'pending') {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => 'Only pending orders can be cancelled',
            'order_id' => $orderId,
            'status' => $currentStatus
        ]);
        exit;
    }

    // Attempt guarded update
    $upd = $con->prepare("UPDATE orders SET order_status='Cancelled' WHERE Order_ID=:oid AND Customer_ID=:cid AND TRIM(LOWER(order_status))='pending'");
    $upd->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
    $affected = $upd->rowCount();

    if ($affected === 0) {
        // Double-check status (race condition?)
        $stmt2 = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid LIMIT 1");
        $stmt2->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
        $afterCheck = $stmt2->fetchColumn();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to cancel (status may have changed)',
            'order_id' => $orderId,
            'status' => $afterCheck
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled',
        'order_id' => $orderId,
        'previous_status' => $currentStatus,
        'new_status' => 'Cancelled'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
