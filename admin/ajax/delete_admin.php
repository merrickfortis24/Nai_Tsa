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