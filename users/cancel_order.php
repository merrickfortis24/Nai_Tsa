<?php
session_start();
header('Content-Type: application/json');

// Require authenticated customer
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/classes/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    $db = new database();
    $con = $db->opencon();
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure the order belongs to the current user and is still Pending
    $check = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid LIMIT 1");
    $check->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
    $status = $check->fetchColumn();
    if ($status === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    if (trim(strtolower($status)) !== 'pending') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
        exit;
    }

    // Perform the cancellation (case-insensitive + trim guard on current status)
    $upd = $con->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE Order_ID = :oid AND Customer_ID = :cid AND TRIM(LOWER(order_status)) = 'pending'");
    $upd->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
    $rows_guarded = $upd->rowCount();

    if ($rows_guarded === 0) {
        // Fallback: if our pre-check says it's pending but the guarded update didn't match (e.g., collation/whitespace quirks), try without the status guard
        $upd2 = $con->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE Order_ID = :oid AND Customer_ID = :cid");
        $upd2->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
        $rows_fallback = $upd2->rowCount();
        if ($rows_fallback > 0) {
            // Verify final status
            $post = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid");
            $post->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
            $status_after = $post->fetchColumn();
            echo json_encode(['success' => true, 'note' => 'Fallback update applied', 'status_before' => $status, 'rows_guarded' => $rows_guarded, 'rows_fallback' => $rows_fallback, 'status_after' => $status_after]);
            exit;
        }
    }

    if ($rows_guarded > 0) {
        $post = $con->prepare("SELECT order_status FROM orders WHERE Order_ID = :oid AND Customer_ID = :cid");
        $post->execute([':oid' => $orderId, ':cid' => $_SESSION['customer_id']]);
        $status_after = $post->fetchColumn();
        echo json_encode(['success' => true, 'status_before' => $status, 'rows_guarded' => $rows_guarded, 'status_after' => $status_after]);
    } else {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Unable to cancel at this stage', 'status_before' => $status, 'rows_guarded' => $rows_guarded]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'detail' => $e->getMessage()]);
}
