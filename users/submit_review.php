<?php
session_start();
// Attempt to restore session from remember-me cookie for AJAX endpoints
require_once __DIR__ . '/../includes/remember.php';
header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
require_once "classes/database.php";
$db = new database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $customer_id = intval($_SESSION['customer_id']);
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($product_id <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid payload']);
        exit;
    }

    try {
        if (method_exists($db, 'hasReview') && $db->hasReview($product_id, $customer_id)) {
            echo json_encode(['success' => false, 'message' => 'Already reviewed']);
            exit;
        }
        $ok = $db->addReview($product_id, $customer_id, $rating, $review_text);
        echo json_encode(['success' => (bool)$ok]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);