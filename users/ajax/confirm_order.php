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

    $current = trim($row['order_status'] ?? '');
    $type    = trim($row['order_type'] ?? '');
    $isPickup = stripos($type,'pick') !== false; // treat anything containing 'pick' as pickup

    // Terminal states
    if(in_array($current, ['Cancelled','Received','Delivered'], true)){
        echo json_encode(['success'=>true,'already_final'=>true,'final_status'=>$current]);
        exit;
    }

    $allowed = $isPickup
        ? ['Pending','Processing','Ready to pick up']
        : ['Pending','Processing','Ready to deliver','On the way'];

    if(!in_array($current, $allowed, true)){
        echo json_encode(['success'=>false,'message'=>'Cannot confirm from status: '.$current]);
        exit;
    }

    $newStatus = $isPickup ? 'Received' : 'Delivered';

    $upd = $con->prepare("UPDATE orders SET order_status=? WHERE Order_ID=?");
    $upd->execute([$newStatus, $orderId]);

    // Optional sales insertion if method exists (ignoring failures silently)
    if(method_exists($db,'insertSalesIfDeliveredAndPaid')){
        try { $db->insertSalesIfDeliveredAndPaid($orderId); } catch(Throwable $e){}
    }

    echo json_encode([
        'success'=>true,
        'order_id'=>$orderId,
        'final_status'=>$newStatus
    ]);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
