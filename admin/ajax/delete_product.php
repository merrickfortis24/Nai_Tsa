<?php

session_start();
require_once('../classes/database.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $db = new database();
    $con = $db->opencon();
    $product_id = $_POST['product_id'];

    try {
        $stmt = $con->prepare("DELETE FROM product WHERE Product_ID = ?");
        $stmt->execute([$product_id]);
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;