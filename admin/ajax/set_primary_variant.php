<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$variantId = isset($_POST['variant_id'])?(int)$_POST['variant_id']:0;
if(!$variantId){ echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
try {
    $pdo = getDB();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT Product_ID, variant_type FROM product_variant WHERE Variant_ID=?");
    $stmt->execute([$variantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row) throw new Exception('Variant not found');

    $stmt = $pdo->prepare("UPDATE product_variant SET is_primary=0 WHERE Product_ID=? AND variant_type=?");
    $stmt->execute([$row['Product_ID'],$row['variant_type']]);
    $stmt = $pdo->prepare("UPDATE product_variant SET is_primary=1 WHERE Variant_ID=?");
    $stmt->execute([$variantId]);
    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    if($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('set_primary_variant.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
