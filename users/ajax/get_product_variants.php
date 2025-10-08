<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$productId = isset($_GET['product_id'])?(int)$_GET['product_id']:0;
if(!$productId){ echo json_encode(['success'=>false,'error'=>'Missing product_id']); exit; }
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT Variant_ID, variant_type, code, label, price_mode, price_value, is_primary FROM product_variant WHERE Product_ID=? AND active=1 ORDER BY variant_type, sort_order, label");
    $stmt->execute([$productId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grouped = ['size'=>[], 'flavor'=>[]];
    foreach($rows as $r){ $grouped[$r['variant_type']][] = $r; }
    echo json_encode(['success'=>true,'variants'=>$grouped]);
} catch(Exception $e){
    error_log('get_product_variants.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
