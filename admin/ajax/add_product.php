<?php
session_start();
require_once('../classes/database.php');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate inputs
    $required = ['product_name', 'category_id', 'price_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $db = new database();

    // Handle image if uploaded
    $image_name = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('prod_', true) . '.' . $ext;
        $upload_dir = '../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $target_path = $upload_dir . $image_name;
        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
            throw new Exception('Failed to upload image.');
        }
    }

    $result = $db->saveProduct(
        trim($_POST['product_name']),
        trim($_POST['product_desc'] ?? ''),
        $_POST['category_id'],
        $_POST['price_id'],
        $_SESSION['admin_id'],
        $image_name,
        $_POST['product_id'] ?? null
    );

    echo json_encode($result);
} catch (Exception $e) {
    error_log("Add Product Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}