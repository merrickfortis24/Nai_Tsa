<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/database.php';
$db = new database();
$con = $db->opencon();

$raw = file_get_contents('php://input') ?: '';
$in = json_decode($raw, true) ?: [];
$ids = $in['ids'] ?? [];
if (!is_array($ids) || empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No ids']);
    exit;
}

// Sanitize ids
$ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'No ids']); exit; }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $con->prepare("UPDATE notifications SET Is_Read = 1 WHERE Notification_ID IN ($placeholders)");
$ok = $stmt->execute($ids);
echo json_encode(['success' => (bool)$ok]);
