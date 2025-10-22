<?php
// Endpoint: users/ajax/notify_new_order.php
// Accepts JSON { order_id: int } and triggers send_new_order_email() defined in utils/mailer.php
// Returns JSON { success: bool, message?: string }

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../utils/mailer.php';
// Do NOT require admin/classes/database.php here to avoid class name collisions across different DB wrappers.
// We try to enrich the email with order details only if a compatible DB helper is available. Otherwise send minimal notification.

try {
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No input']);
        exit;
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    $orderId = isset($data['order_id']) ? intval($data['order_id']) : 0;
    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing order_id']);
        exit;
    }

    // Try to include order summary if an admin DB wrapper is present (admin/classes/database.php)
    $summary = [];
    $adminDbPath = __DIR__ . '/../../admin/classes/database.php';
    if (is_file($adminDbPath)) {
        try {
            require_once $adminDbPath; // class 'database' will be available
            $db = new database();
            if (method_exists($db, 'fetchOrderById')) {
                $order = $db->fetchOrderById($orderId);
                if ($order) {
                    $summary['customer_email'] = $order['Email'] ?? ($order['customer_email'] ?? null);
                    $summary['amount'] = $order['Total_Amount'] ?? $order['amount'] ?? null;
                }
            }
            if (method_exists($db, 'fetchOrderItems')) {
                $items = $db->fetchOrderItems($orderId);
                if (is_array($items) && $items) {
                    $summary['items'] = array_map(function($it){
                        return [
                            'name' => $it['Product_Name'] ?? $it['name'] ?? '',
                            'qty' => $it['Quantity'] ?? $it['qty'] ?? 1
                        ];
                    }, $items);
                }
            }
        } catch (Throwable $e) {
            error_log('[notify_new_order] admin DB fetch failed: ' . $e->getMessage());
            // Continue with minimal summary
        }
    }

    $res = send_new_order_email($orderId, $summary);
    if ($res === true) {
        echo json_encode(['success' => true]);
        exit;
    }
    // send_new_order_email returns string on error
    error_log('[notify_new_order] send failed for order ' . $orderId . ' : ' . (string)$res);
    echo json_encode(['success' => false, 'message' => (string)$res]);
} catch (Throwable $e) {
    error_log('[notify_new_order] exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

exit;
