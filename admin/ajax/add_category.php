<?php

session_start();
require_once('../classes/database.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');

    if (empty($category_name)) {
        $response['message'] = 'Category name is required.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $conn = $db->opencon();
        $stmt = $conn->prepare("INSERT INTO category (Category_Name) VALUES (:name)");
        $stmt->execute([':name' => $category_name]);
        $response['success'] = true;
        $response['message'] = 'Category added successfully!';
    } catch (PDOException $e) {
        $response['message'] = 'Database Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);