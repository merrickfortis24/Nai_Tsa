<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '0';
    $status = $_POST['status'] ?? 'Active';
    if ($name === '' || !is_numeric($price)) { throw new Exception('Invalid name or price'); }
    $price = number_format((float)$price, 2, '.', '');
    $status = ($status === 'Inactive') ? 'Inactive' : 'Active';

    $helper = new addons_helper();
    $res = $helper->addAddon($name, (float)$price, $status);
    echo json_encode(['success'=> (bool)($res['success'] ?? false), 'id'=> ($res['id'] ?? null)]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
