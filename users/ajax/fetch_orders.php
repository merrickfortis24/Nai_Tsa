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

    // Build flattened list from grouped (legacy three buckets)
    $flat = [];
    $seenIds = [];
    foreach ($orders_by_status as $grp => $arr) {
        if (!is_array($arr)) continue;
        foreach ($arr as $o) {
            if (!isset($o['Order_ID'])) continue;
            $oid = (int)$o['Order_ID'];
            if (!isset($o['order_status']) || $o['order_status'] === '' || $o['order_status'] === null) {
                switch ($grp) {
                    case 'To Ship': $o['order_status'] = 'Ready to deliver'; break;
                    case 'To Receive': $o['order_status'] = 'On the way'; break;
                    default: $o['order_status'] = $grp; break; // Delivered
                }
            }
            if ($o['order_status'] === 'To Ship') $o['order_status'] = 'Ready to deliver';
            if ($o['order_status'] === 'To Receive') $o['order_status'] = 'On the way';
            $flat[] = $o;
            $seenIds[$oid] = true;
        }
    }

    // Supplement with all other orders (any status) so statuses like Processing / Ready to pick up appear
    $con = $db->opencon();
    $allStmt = $con->prepare("SELECT * FROM orders WHERE Customer_ID = ? ORDER BY Order_Date DESC");
    $allStmt->execute([$user_id]);
    $allOrders = $allStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($allOrders as $row) {
        $oid = (int)$row['Order_ID'];
        if (isset($seenIds[$oid])) continue; // already included
        // Normalize legacy statuses to canonical
        $raw = $row['order_status'] ?? '';
        if ($raw === 'To Ship') $row['order_status'] = 'Ready to deliver';
        elseif ($raw === 'To Receive') $row['order_status'] = 'On the way';
        elseif ($raw === 'Ready for pickup') $row['order_status'] = 'Ready to pick up';
        // Attach items (lightweight)
        try {
            $row['items'] = $orderObj->getOrderItems($oid);
        } catch (Throwable $e) { $row['items'] = []; }
        $flat[] = $row;
    }

    // Recompute simple grouped structure (optional) so legacy keys still exist even if empty
    // We keep original $orders_by_status; but ensure delivered bucket includes any Delivered not previously grouped
    foreach ($flat as $o) {
        $st = $o['order_status'] ?? '';
        if ($st === 'Delivered' && !in_array($o, $orders_by_status['Delivered'], true)) {
            $orders_by_status['Delivered'][] = $o;
        }
    }

    // Derive counts across key statuses
    $counts = [
        'total' => count($flat),
        'pending' => 0,
        'processing' => 0,
        'ready_to_deliver' => 0,
        'on_the_way' => 0,
        'ready_to_pick_up' => 0,
        'received' => 0,
        'delivered' => 0,
        'cancelled' => 0
    ];
    foreach ($flat as $o) {
        $st = $o['order_status'] ?? '';
        switch ($st) {
            case 'Pending': $counts['pending']++; break;
            case 'Processing': $counts['processing']++; break;
            case 'Ready to deliver': $counts['ready_to_deliver']++; break;
            case 'On the way': $counts['on_the_way']++; break;
            case 'Ready to pick up': $counts['ready_to_pick_up']++; break;
            case 'Received': $counts['received']++; break;
            case 'Delivered': $counts['delivered']++; break;
            case 'Cancelled': $counts['cancelled']++; break;
        }
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