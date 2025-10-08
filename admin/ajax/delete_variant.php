<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$variantId = isset($_POST['variant_id'])?(int)$_POST['variant_id']:0;
if(!$variantId){ echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM product_variant WHERE Variant_ID=?");
    $stmt->execute([$variantId]);
    if($stmt->rowCount()<1){ echo json_encode(['success'=>false,'error'=>'Not found']); return; }
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    error_log('delete_variant.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
