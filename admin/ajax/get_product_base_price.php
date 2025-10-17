<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if($product_id<=0){ echo json_encode(['success'=>false,'message'=>'Missing product_id']); exit; }
try{
  // First try to return the product's anchor base price (from product_size_price.Is_Anchor)
  // which represents the true base used when sizes/deltas are applied. If none exists,
  // fall back to the legacy product_price.Price_Amount linked by product.Price_ID.
  $con = $db->opencon();
  // Try anchor size price
  try {
    $stmtA = $con->prepare("SELECT Price_Value FROM product_size_price WHERE Product_ID = ? AND Is_Anchor = 1 LIMIT 1");
    $stmtA->execute([$product_id]);
    $anchorVal = $stmtA->fetchColumn();
    if ($anchorVal !== false && $anchorVal !== null) {
      $amount = (float)$anchorVal;
      echo json_encode(['success'=>true,'price'=>$amount]);
      exit;
    }
  } catch (Throwable $e) {
    // ignore and fallback
  }

  // Fallback to product_price.Price_Amount
  $stmt = $con->prepare("SELECT pp.Price_Amount FROM product p JOIN product_price pp ON p.Price_ID = pp.Price_ID WHERE p.Product_ID = ? LIMIT 1");
  $stmt->execute([$product_id]);
  $val = $stmt->fetchColumn();
  $amount = ($val !== false && $val !== null) ? (float)$val : null;
  echo json_encode(['success'=>true,'price'=>$amount]);
}catch(Throwable $e){
  error_log('get_product_base_price.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
