<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { throw new Exception('Invalid id'); }
    $helper = new addons_helper();
    $ok = $helper->deleteAddon($id);
    echo json_encode(['success'=>$ok]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
