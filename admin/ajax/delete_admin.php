<?php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $targetId = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
    if ($targetId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
        exit;
    }
    // Prevent self-deletion
    if ((int)$_SESSION['admin_id'] === $targetId) {
        echo json_encode(['success' => false, 'message' => "You can't delete your own account while logged in."]);
        exit;
    }

    require_once __DIR__ . '/../classes/database.php';
    $db = new database();
    $result = $db->deleteAdmin($targetId);
    if (is_array($result) && isset($result['success'])) {
        echo json_encode($result);
    } else {
        echo json_encode(['success' => (bool)$result, 'message' => $result ? 'Admin deleted successfully!' : 'Failed to delete admin.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
<?php

session_start();
require_once('../classes/database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
    $db = new database();
    $result = $db->deleteAdmin($_POST['admin_id']);
    echo json_encode($result);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;