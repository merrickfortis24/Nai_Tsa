<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/database.php';

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'size';
if(!in_array($type,['size','flavor'])){ echo json_encode(['success'=>false,'error'=>'Invalid type']); exit; }

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

try {
    $pdo = getDB();
    $params = [];
    $sql = "SELECT v.*, p.Product_Name FROM product_variant v JOIN product p ON p.Product_ID = v.Product_ID WHERE v.variant_type = ?";
    $params[] = $type;
    if($productId){ $sql .= " AND v.Product_ID = ?"; $params[] = $productId; }
    $sql .= " ORDER BY p.Product_Name, v.sort_order, v.label";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'data'=>$rows]);
} catch(Exception $e){
    error_log('list_variants.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
