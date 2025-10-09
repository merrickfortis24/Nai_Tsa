<?php

session_start();
header('Content-Type: application/json; charset=UTF-8');

if(!isset($_SESSION['customer_id'])){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$in = json_decode($raw,true) ?: $_POST;
$orderId = (int)($in['order_id'] ?? 0);
if($orderId <= 0){
    echo json_encode(['success'=>false,'message'=>'Invalid order_id']);
    exit;
}

require_once __DIR__ . '/../classes/database.php';
$db = new database();

try {
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT Order_ID, Customer_ID, order_status, order_type, Payment_Status FROM orders WHERE Order_ID=? LIMIT 1");
    $stmt->execute([$orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row || (int)$row['Customer_ID'] !== (int)$_SESSION['customer_id']){
        echo json_encode(['success'=>false,'message'=>'Not found']);
        exit;
    }

    $current = trim((string)($row['order_status'] ?? ''));
    $type    = trim((string)($row['order_type'] ?? ''));
    $isPickup = stripos($type,'pick') !== false; // treat anything containing 'pick' as pickup

    // Normalize status (case-insensitive + aliases) to canonical set used by UI/backend
    $rawLower = strtolower($current);
    $map = [
        'preparing' => 'Processing',
        'processing' => 'Processing',
        'to ship' => 'Ready to deliver',
        'to receive' => 'On the way',
        'ready for pickup' => 'Ready to pick up',
        'ready to pick up' => 'Ready to pick up',
        'ready to pickup' => 'Ready to pick up',
        'ready for pick up' => 'Ready to pick up',
        'on the way' => 'On the way',
        'delivered' => 'Delivered',
        'received' => 'Received',
        'pending' => 'Pending',
        'cancelled' => 'Cancelled',
    ];
    $canonical = $map[$rawLower] ?? $current; // fallback to original if not mapped

    // Terminal states
    if(in_array($canonical, ['Cancelled','Received','Delivered'], true)){
        echo json_encode(['success'=>true,'already_final'=>true,'final_status'=>$canonical]);
        exit;
    }

    if ($isPickup) {
        // New rule: user confirmation only succeeds if admin already marked as Received
        if ($canonical !== 'Received') {
            echo json_encode(['success'=>false,'message'=>'Order has not been received yet.']);
            exit;
        }
        // Already effectively final, but return success to allow client to prompt review
        echo json_encode([
            'success'=>true,
            'order_id'=>$orderId,
            'final_status'=>'Received',
            'review_prompt'=>true
        ]);
        exit;
    }

    // Delivery flow: allow confirming when status is in allowed list -> set Delivered
    $allowed = ['Pending','Processing','Ready to deliver','On the way'];
    if(!in_array($canonical, $allowed, true)){
        echo json_encode(['success'=>false,'message'=>'Cannot confirm from status: '.$current]);
        exit;
    }
    $newStatus = 'Delivered';
    $upd = $con->prepare("UPDATE orders SET order_status=? WHERE Order_ID=?");
    $upd->execute([$newStatus, $orderId]);

    if(method_exists($db,'insertSalesIfDeliveredAndPaid')){
        try { $db->insertSalesIfDeliveredAndPaid($orderId); } catch(Throwable $e){}
    }

    echo json_encode(['success'=>true,'order_id'=>$orderId,'final_status'=>$newStatus,'review_prompt'=>true]);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
