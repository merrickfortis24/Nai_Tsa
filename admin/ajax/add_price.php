<?php
session_start();
require_once('../classes/database.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price_amount = $_POST['price_amount'] ?? '';
    $effective_from = $_POST['effective_from'] ?? '';
    $effective_to = $_POST['effective_to'] ?? null;

    if (empty($price_amount) || empty($effective_from)) {
        $response['message'] = 'Price amount and effective from date are required.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $conn = $db->opencon();
        $stmt = $conn->prepare("INSERT INTO product_price (Price_Amount, Effective_From, Effective_To) VALUES (:amount, :from, :to)");
        $stmt->execute([
            ':amount' => $price_amount,
            ':from' => $effective_from,
            ':to' => $effective_to ?: null
        ]);
        $response['success'] = true;
        $response['message'] = 'Price added successfully!';
    } catch (PDOException $e) {
        $response['message'] = 'Database Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);