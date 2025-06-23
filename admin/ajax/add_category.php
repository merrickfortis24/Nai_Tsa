<?php

session_start();
require_once('../classes/database.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';

    if (empty($category_name)) {
        $response['message'] = 'Category name is required.';
    } else {
        $db = new database();
        $response = $db->saveCategory($category_name, $category_id ?: null);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);