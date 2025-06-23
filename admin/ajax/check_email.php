<?php
require_once('../classes/database.php');

header('Content-Type: application/json');

if (isset($_POST['email'])) {
    $db = new database();
    $exists = $db->adminEmailExists($_POST['email']);
    echo json_encode(['exists' => $exists]);
    exit;
}
echo json_encode(['exists' => false]);
exit;