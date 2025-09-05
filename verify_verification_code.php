<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/database/database.php';

$email = trim($_POST['email'] ?? '');
$code  = trim($_POST['code'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $code)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit;
}

$db = new database();
$ok = $db->verifyEmailOtp($email, $code, 300);

echo json_encode(['ok'=>$ok]);
