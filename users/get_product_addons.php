<?php
session_start();
header('Content-Type: application/json');
// Keep parity with app flow
if (!isset($_SESSION['customer_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/classes/database.php';

try {
    $db = new database();
    $con = $db->opencon();
    // Global add-ons: ignore product mapping, return all active add-ons
    $stmt = $con->prepare("SELECT Addon_ID, Addon_Name, Addon_Price
                           FROM addons
                           WHERE Status = 'Active'
                           ORDER BY Addon_Name ASC");
    $stmt->execute();
    $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'addons'=>$addons]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
