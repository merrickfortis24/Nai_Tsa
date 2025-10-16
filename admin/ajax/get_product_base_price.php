<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if($product_id<=0){ echo json_encode(['success'=>false,'message'=>'Missing product_id']); exit; }
try{
  // getCurrentProductPrice returns a float (preferred) or may return an array in older implementations.
  $row = $db->getCurrentProductPrice($product_id);
  $amount = null;
  if (is_array($row) && isset($row['Price_Amount'])) {
    $amount = (float)$row['Price_Amount'];
  } elseif (is_numeric($row)) {
    $amount = (float)$row;
  }
  echo json_encode(['success'=>true,'price'=>$amount]);
}catch(Throwable $e){
  error_log('get_product_base_price.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
