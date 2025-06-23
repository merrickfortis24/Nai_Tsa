<?php

session_start();
require_once('../classes/database.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $db = new database();
    $product_id = $_POST['product_id'];
    $result = $db->deleteProduct($product_id);
    echo json_encode($result);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;