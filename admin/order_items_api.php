<?php
// order_items_api.php - returns JSON for items of a given order (admin side)
header('Content-Type: application/json');
require_once __DIR__.'/classes/database.php'; // use admin database class for consistency
session_start();

// Basic admin authentication check (adjust to your existing session vars)
if(!isset($_SESSION['admin_id'])){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit; 
}

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if($orderId <= 0){
    echo json_encode(['success'=>false,'message'=>'Invalid order id']);
    exit;
}

try {
    $db = new database();
    $items = $db->fetchOrderItems($orderId);
    echo json_encode(['success'=>true,'order_id'=>$orderId,'items'=>$items]);
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
