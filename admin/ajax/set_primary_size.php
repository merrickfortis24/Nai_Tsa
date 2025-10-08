<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$con = $db->opencon();
$product_id = isset($_POST['product_id'])?(int)$_POST['product_id']:0;
$size_id = isset($_POST['size_id'])?(int)$_POST['size_id']:0;
if($product_id<=0 || $size_id<=0){ echo json_encode(['success'=>false,'message'=>'Missing product or size id']); exit; }
try {
    // Validate mapping exists
    $stmt = $con->prepare("SELECT 1 FROM product_size_price WHERE Product_ID=? AND Size_ID=? LIMIT 1");
    $stmt->execute([$product_id,$size_id]);
    if(!$stmt->fetch()){ echo json_encode(['success'=>false,'message'=>'Size variant not found for product']); exit; }
    // Ensure column exists
    try { $c=$con->query("SHOW COLUMNS FROM product LIKE 'Primary_Size_ID'"); if($c && $c->rowCount()==0){ $con->exec("ALTER TABLE product ADD COLUMN Primary_Size_ID INT NULL AFTER Price_ID, ADD INDEX (Primary_Size_ID)"); } } catch(Throwable $e){}
    $up = $con->prepare("UPDATE product SET Primary_Size_ID=? WHERE Product_ID=? LIMIT 1");
    $ok = $up->execute([$size_id,$product_id]);
    echo json_encode(['success'=>$ok,'message'=>$ok?'Primary size updated.':'No change']);
} catch(Throwable $e){
    error_log('set_primary_size: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
