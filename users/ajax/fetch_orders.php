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
// Attempt to restore session from remember-me cookie for AJAX endpoints
require_once __DIR__ . '/../../includes/remember.php';
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
    // Include payment join to get payment_status and method for all orders
    $allStmt = $con->prepare("SELECT o.*, p.payment_status, p.Payment_Method FROM orders o LEFT JOIN payment p ON p.Order_ID=o.Order_ID WHERE o.Customer_ID = ? ORDER BY o.Order_Date DESC");
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

    // Enrich ALL orders with payment method and latest receipt info in bulk
    $orderIds = array_values(array_unique(array_map(function($o){ return (int)($o['Order_ID'] ?? 0); }, $flat)));
    if ($orderIds) {
        // Build IN clause safely
        $in = implode(',', array_fill(0, count($orderIds), '?'));
        // Payment methods/status map
        try {
            $pmtStmt = $con->prepare("SELECT Order_ID, Payment_Method, payment_status FROM payment WHERE Order_ID IN ($in)");
            $pmtStmt->execute($orderIds);
            $payMap = [];
            while ($r = $pmtStmt->fetch(PDO::FETCH_ASSOC)) { $payMap[(int)$r['Order_ID']] = $r; }
        } catch (Throwable $e) { $payMap = []; }
        // Latest receipt per order (if any)
        $rcpMap = [];
        try {
            $latestStmt = $con->prepare("SELECT opr.Order_ID, opr.Status AS latest_receipt_status, opr.Reject_Reason AS latest_receipt_reason, opr.Reference_Number AS latest_receipt_ref, opr.Submitted_Amount AS latest_receipt_amount, opr.Proof_Photo AS latest_receipt_file
                                          FROM order_payment_receipt opr
                                          JOIN (
                                            SELECT Order_ID, MAX(Payment_Receipt_ID) AS max_id
                                            FROM order_payment_receipt
                                            WHERE Order_ID IN ($in)
                                            GROUP BY Order_ID
                                          ) t ON t.max_id = opr.Payment_Receipt_ID");
            $latestStmt->execute($orderIds);
            while ($r = $latestStmt->fetch(PDO::FETCH_ASSOC)) { $rcpMap[(int)$r['Order_ID']] = $r; }
        } catch (Throwable $e) { $rcpMap = []; }
        // Attach to flat
        foreach ($flat as &$o) {
            $oid = (int)$o['Order_ID'];
            if (isset($payMap[$oid])) {
                $o['payment_status'] = $o['payment_status'] ?? $payMap[$oid]['payment_status'];
                $o['Payment_Method'] = $o['Payment_Method'] ?? $payMap[$oid]['Payment_Method'];
            }
            if (isset($rcpMap[$oid])) { $o = array_merge($o, $rcpMap[$oid]); }
        }
        unset($o);
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