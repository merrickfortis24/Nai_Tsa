<?php

require_once("database/database.php");

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $db = new database();
    $exists = $db->checkEmailExists($email);
    echo json_encode(['exists' => $exists]);
}
?>