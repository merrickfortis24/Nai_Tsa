<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$id = isset($_POST['id'])? (int)$_POST['id'] : 0;
if($id<=0){ echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
try{
  $con = $db->opencon();
  // Try new mapping table first
  $ok = false;
  try {
    $stmt = $con->prepare('DELETE FROM product_size_price WHERE Product_Size_Price_ID=?');
    $ok = $stmt->execute([$id]);
  } catch (Throwable $e) { /* ignore */ }
  if(!$ok){
    try {
      $stmt2 = $con->prepare('DELETE FROM product_sizes WHERE ID=?');
      $ok = $stmt2->execute([$id]);
    } catch (Throwable $e2) { /* ignore */ }
  }
  echo json_encode(['success'=>$ok]);
} catch(Throwable $e){
  error_log('delete_size.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
