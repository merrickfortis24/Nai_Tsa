<?php
require_once('../classes/database.php');

header('Content-Type: application/json');

if (isset($_POST['email'])) {
    $db = new database();
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT COUNT(*) FROM Admin WHERE Admin_Email = ?");
    $stmt->execute([trim($_POST['email'])]);
    $exists = $stmt->fetchColumn() > 0;
    echo json_encode(['exists' => $exists]);
    exit;
}
echo json_encode(['exists' => false]);
exit;