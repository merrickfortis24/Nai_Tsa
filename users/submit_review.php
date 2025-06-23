<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}
require_once "classes/database.php";
$db = new database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $customer_id = $_SESSION['customer_id'];
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit();
    }

    $success = $db->addReview($product_id, $customer_id, $rating, $review_text);
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $errorInfo]);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);