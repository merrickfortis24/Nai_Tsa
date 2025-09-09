<?php
// Unified grouped orders endpoint returning a consistent JSON envelope.
// Provides both grouped data (legacy) and a flattened array for easier client consumption.
// Response shape:
// {
//   success: true/false,
//   groups: { 'To Ship': [...], 'To Receive': [...], 'Delivered': [...] },
//   flat: [ {...order...}, ...],
//   counts: { total: N, to_ship: X, to_receive: Y, delivered: Z, pending: P },
//   message?: string
// }

session_start();
header('Content-Type: application/json');

try {
    // Basic auth check
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }

    // Use relative path from current file (ajax/) to classes/
    require_once __DIR__ . '/../classes/database.php';
    require_once __DIR__ . '/../classes/order.php';

    $db = new database();
    $orderObj = new Order();
    $user_id = $_SESSION['customer_id'];

    // Default empty structure
    $orders_by_status = [
        'To Ship' => [],
        'To Receive' => [],
        'Delivered' => []
    ];

    // Fetch grouped orders + items
    $orders_by_status = $db->getOrdersByStatusWithItems($user_id, $orderObj);
    if (!is_array($orders_by_status)) {
        $orders_by_status = ['To Ship'=>[], 'To Receive'=>[], 'Delivered'=>[]];
    }

    // Build flattened list
    $flat = [];
    foreach ($orders_by_status as $grp => $arr) {
        if (!is_array($arr)) continue;
        foreach ($arr as $o) { $flat[] = $o; }
    }

    // Derive counts (pending heuristic: order_status == Pending or Processing)
    $counts = [
        'total' => count($flat),
        'to_ship' => count($orders_by_status['To Ship'] ?? []),
        'to_receive' => count($orders_by_status['To Receive'] ?? []),
        'delivered' => count($orders_by_status['Delivered'] ?? []),
        'pending' => 0
    ];
    foreach ($flat as $o) {
        $st = $o['order_status'] ?? $o['Order_Status'] ?? $o['Order_Status'] ?? '';
        if (in_array($st, ['Pending','Processing'], true)) $counts['pending']++;
    }

    echo json_encode([
        'success' => true,
        'groups' => $orders_by_status,
        'flat' => $flat,
        'counts' => $counts
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error fetching orders',
        'error' => $e->getMessage()
    ]);
}