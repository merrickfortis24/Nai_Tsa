<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $productId = intval($_GET['product_id'] ?? 0);
    if ($productId <= 0) { throw new Exception('Invalid product_id'); }
    $helper = new addons_helper();
    $ids = $helper->getProductAddons($productId);
    $addons = array_map(function($id){ return ['Addon_ID' => (int)$id]; }, $ids);
    echo json_encode(['success'=>true,'addons'=>$addons]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
