<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $productId = intval($_POST['product_id'] ?? 0);
    $addonIdsRaw = $_POST['addon_ids'] ?? '';
    if ($productId <= 0) { throw new Exception('Invalid product_id'); }
    $ids = [];
    if (is_array($addonIdsRaw)) {
        $ids = $addonIdsRaw;
    } else if (is_string($addonIdsRaw) && $addonIdsRaw !== '') {
        $ids = array_filter(array_map('intval', explode(',', $addonIdsRaw)));
    }
    $addonIds = array_values(array_unique(array_map('intval', $ids)));

    $helper = new addons_helper();
    $ok = $helper->setProductAddons($productId, $addonIds);
    echo json_encode(['success'=>$ok]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
