<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$type = isset($_POST['variant_type']) ? strtolower(trim($_POST['variant_type'])) : '';
if(!in_array($type,['size','flavor'])){ echo json_encode(['success'=>false,'error'=>'Invalid type']); exit; }
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$code = trim($_POST['code'] ?? '');
$label = trim($_POST['label'] ?? '');
$priceMode = strtoupper(trim($_POST['price_mode'] ?? 'DELTA'));
$priceValue = (float)($_POST['price_value'] ?? 0);
$isPrimary = isset($_POST['is_primary']) ? 1 : 0;
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if(!$productId || $code==='' || $label===''){ echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit; }
if(!in_array($priceMode,['ABSOLUTE','DELTA'])){ echo json_encode(['success'=>false,'error'=>'Invalid price_mode']); exit; }

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    if($isPrimary){
        $stmt = $pdo->prepare("UPDATE product_variant SET is_primary=0 WHERE Product_ID=? AND variant_type=?");
        $stmt->execute([$productId,$type]);
    }
    $stmt = $pdo->prepare("INSERT INTO product_variant (Product_ID, variant_type, code, label, price_mode, price_value, is_primary, active, sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$productId,$type,$code,$label,$priceMode,$priceValue,$isPrimary,1,$sortOrder]);
    $id = $pdo->lastInsertId();
    $pdo->commit();
    echo json_encode(['success'=>true,'id'=>$id]);
} catch(Exception $e){
    if($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('add_variant.php: '.$e->getMessage());
    $msg = strpos($e->getMessage(),'uq_product_variant_code')!==false? 'Duplicate code for this product & type':'Server error';
    echo json_encode(['success'=>false,'error'=>$msg]);
}
