<?php
// Lightweight status polling endpoint.
// Use when you only need status/delivery updates without fetching full item details.
// Example frontend usage:
//   fetch('ajax/order_status.php?t='+Date.now())
//     .then(r=>r.json())
//     .then(d=>{ if(d.success) updateBadges(d.counts); updateOrderCards(d.orders); });

session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        return;
    }
    require_once __DIR__ . '/../classes/database.php';
    $db = new database();
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT Order_ID, order_status, Driver_Status FROM orders WHERE Customer_ID = ? ORDER BY Order_Date DESC");
    $stmt->execute([$_SESSION['customer_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $orders = array_map(function($r){
        $ds = $r['Driver_Status'] ?? '';
        $os = $r['order_status'] ?? '';
        if (in_array($ds, ['on_the_way','picked_up'], true)) {
            $r['display_status'] = 'Out for delivery';
        } elseif ($os === 'Processing') {
            $r['display_status'] = 'Preparing';
        } else {
            $r['display_status'] = $os;
        }
        return $r;
    }, $rows);

    // Derive simple counts
    $counts = [
        'total' => count($orders),
        'pending' => 0,
        'processing' => 0,
        'delivered' => 0,
        'cancelled' => 0
    ];
    foreach ($orders as $o) {
        $st = $o['order_status'] ?? '';
        if ($st === 'Pending') $counts['pending']++;
        elseif ($st === 'Processing') $counts['processing']++;
        elseif ($st === 'Delivered') $counts['delivered']++;
        elseif ($st === 'Cancelled') $counts['cancelled']++;
    }

    echo json_encode(['success'=>true,'orders'=>$orders,'counts'=>$counts]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}