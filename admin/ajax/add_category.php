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
        $con = $db->opencon();

        try {
            if (!empty($category_id)) {
                // Update existing category
                $stmt = $con->prepare("UPDATE category SET Category_Name = ? WHERE Category_ID = ?");
                $stmt->execute([$category_name, $category_id]);
                $response = ['success' => true, 'message' => 'Category updated successfully!'];
            } else {
                // Add new category
                $stmt = $con->prepare("INSERT INTO category (Category_Name) VALUES (?)");
                $stmt->execute([$category_name]);
                $response = ['success' => true, 'message' => 'Category added successfully!'];
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);