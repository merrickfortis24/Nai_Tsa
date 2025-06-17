<?php
session_start();
require_once('../classes/database.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keyword'])) {
    $db = new database();
    $admins = $db->searchAdmin(trim($_POST['keyword']));
    echo json_encode(['success' => true, 'admins' => $admins]);
    exit;
}
echo json_encode(['success' => false, 'admins' => []]);
exit;