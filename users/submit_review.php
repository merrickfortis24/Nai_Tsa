<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}
require_once "../admin/classes/database.php";
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

    $stmt = $db->opencon()->prepare("INSERT INTO reviews (Product_ID, Customer_ID, Rating, Review_Text) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$product_id, $customer_id, $rating, $review_text]);
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);