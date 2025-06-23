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
    } else {
        $db = new database();
        $response = $db->addPrice($price_amount, $effective_from, $effective_to);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

header('Content-Type: application/json');
echo json_encode($response);