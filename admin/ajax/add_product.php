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

    $product_name = $_POST['product_name'];
    $product_desc = $_POST['product_desc'];
    $category_id = $_POST['category_id'];
    $price_id = $_POST['price_id'];
    $admin_id = $_SESSION['admin_id'];
    $product_id = !empty($_POST['product_id']) ? $_POST['product_id'] : null;

    // Handle allergens (array to comma-separated string)
    $product_allergens = '';
    if (isset($_POST['Product_Allergens'])) {
        if (is_array($_POST['Product_Allergens'])) {
            $product_allergens = implode(',', $_POST['Product_Allergens']);
        } else {
            $product_allergens = $_POST['Product_Allergens'];
        }
    }

    // Handle image upload if needed
    $image_name = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
        $image_name = uniqid() . '_' . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], '../uploads/products/' . $image_name);
    } else if (!empty($_POST['product_image_existing'])) {
        $image_name = $_POST['product_image_existing'];
    }

    if ($product_id) {
        // Update
        $result = $db->saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name, $product_id, $product_allergens);
    } else {
        // Add
        $result = $db->saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name, null, $product_allergens);
    }

    echo json_encode($result);
} catch (Exception $e) {
    error_log("Add Product Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}