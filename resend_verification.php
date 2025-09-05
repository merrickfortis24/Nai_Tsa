<?php
require_once __DIR__ . '/database/database.php';
header('Content-Type: application/json');
try {
    require_once __DIR__ . '/utils/mailer.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mailer not configured: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing email']);
    exit;
}

$db = new database();
$user = $db->getUserByEmail($email, 'customer');
if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Email not found']);
    exit;
}

if (!empty($user['is_verified']) && (int)$user['is_verified'] === 1) {
    echo json_encode(['ok' => true, 'already' => true]);
    exit;
}

$token = $db->issueVerificationToken($email);
if (!$token) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not issue token']);
    exit;
}

$sent = send_verification_email($email, $token);
if ($sent !== true) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $sent ?: 'Mail send failed']);
    exit;
}

echo json_encode(['ok' => true]);
