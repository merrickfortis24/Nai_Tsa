<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? null;
    $status = $_POST['status'] ?? null;
    if ($id <= 0) { throw new Exception('Invalid id'); }
    if ($name === '' && $price === null && $status === null) { throw new Exception('Nothing to update'); }
    if ($price !== null && !is_numeric($price)) { throw new Exception('Invalid price'); }
    $price = $price !== null ? number_format((float)$price, 2, '.', '') : null;
    if ($status !== null) { $status = ($status === 'Inactive') ? 'Inactive' : 'Active'; }

    $helper = new addons_helper();
    if ($name === '' || $price === null || $status === null) { throw new Exception('All fields are required'); }
    $ok = $helper->updateAddon($id, $name, (float)$price, $status);
    echo json_encode(['success'=> (bool)$ok]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
