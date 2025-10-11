<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$variantId = isset($_POST['variant_id'])?(int)$_POST['variant_id']:0;
$label = trim($_POST['label'] ?? '');
$priceMode = strtoupper(trim($_POST['price_mode'] ?? 'DELTA'));
$priceValue = (float)($_POST['price_value'] ?? 0);
$isPrimary = isset($_POST['is_primary']) ? 1 : 0;
$sortOrder = (int)($_POST['sort_order'] ?? 0);
$active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

if(!$variantId || $label===''){ echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit; }
if(!in_array($priceMode,['ABSOLUTE','DELTA'])){ echo json_encode(['success'=>false,'error'=>'Invalid price_mode']); exit; }

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT Product_ID, variant_type FROM product_variant WHERE Variant_ID=?");
    $stmt->execute([$variantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row){ throw new Exception('Variant not found'); }

    if($isPrimary){
        $stmt = $pdo->prepare("UPDATE product_variant SET is_primary=0 WHERE Product_ID=? AND variant_type=?");
        $stmt->execute([$row['Product_ID'],$row['variant_type']]);
    }

    $stmt = $pdo->prepare("UPDATE product_variant SET label=?, price_mode=?, price_value=?, is_primary=?, active=?, sort_order=? WHERE Variant_ID=?");
    $stmt->execute([$label,$priceMode,$priceValue,$isPrimary,$active,$sortOrder,$variantId]);
    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    if($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('update_variant.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
