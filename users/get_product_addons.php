<?php
session_start();
header('Content-Type: application/json');
// Customer not strictly required to be logged in to view add-ons, but keep parity with app flow
if (!isset($_SESSION['customer_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/classes/database.php';

try {
    $db = new database();
    $con = $db->opencon();
    $pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if ($pid <= 0) { throw new Exception('Invalid product_id'); }
    $stmt = $con->prepare("SELECT a.Addon_ID, a.Addon_Name, a.Addon_Price
                           FROM product_addons pa
                           JOIN addons a ON a.Addon_ID = pa.Addon_ID
                           WHERE pa.Product_ID = ? AND a.Status = 'Active' 
                           ORDER BY a.Addon_Name ASC");
    $stmt->execute([$pid]);
    $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'addons'=>$addons]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
