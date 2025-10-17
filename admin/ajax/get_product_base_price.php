<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if($product_id<=0){ echo json_encode(['success'=>false,'message'=>'Missing product_id']); exit; }
try{
  // Return the product's base price from product_price.Price_Amount.
  // getCurrentProductPrice() returns the resolved/display price (may include deltas or history),
  // which is not what callers of this endpoint expect when they need the product base price.
  $con = $db->opencon();
  $stmt = $con->prepare("SELECT pp.Price_Amount FROM product p JOIN product_price pp ON p.Price_ID = pp.Price_ID WHERE p.Product_ID = ? LIMIT 1");
  $stmt->execute([$product_id]);
  $val = $stmt->fetchColumn();
  $amount = ($val !== false && $val !== null) ? (float)$val : null;
  echo json_encode(['success'=>true,'price'=>$amount]);
}catch(Throwable $e){
  error_log('get_product_base_price.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
