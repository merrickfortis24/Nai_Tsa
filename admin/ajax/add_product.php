<?php

session_start();
require_once('../classes/database.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_desc = trim($_POST['product_desc'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $price_id = $_POST['price_id'] ?? null;
    $admin_id = $_SESSION['admin_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;

    // Validate required fields
    if (empty($product_name) || empty($category_id) || empty($price_id) || empty($admin_id)) {
        $response['message'] = 'All required fields must be filled.';
        echo json_encode($response);
        exit;
    }

    // Handle image upload (optional, only if a new image is uploaded)
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
            $response['message'] = 'Failed to upload image.';
            echo json_encode($response);
            exit;
        }
    }

    $db = new database();
    $result = $db->saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name, $product_id);
    echo json_encode($result);
    exit;
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}