<?php
// Returns add-ons. If a product_id is supplied and mapping table exists, filters to those; otherwise returns active global add-ons.
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/database.php';

try {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $db = new database();
    $con = $db->opencon();

    // Detect mapping table presence
    $hasMapping = false;
    try {
        $con->query("SELECT 1 FROM product_addons LIMIT 1");
        $hasMapping = true;
    } catch (Throwable $e) { $hasMapping = false; }

    if ($productId && $hasMapping) {
        $sql = "SELECT a.Addon_ID, a.Addon_Name, a.Addon_Price
                FROM product_addons pa
                JOIN addons a ON pa.Addon_ID = a.Addon_ID
                WHERE pa.Product_ID = ? AND (
                      a.Status IS NULL OR a.Status IN ('Active','active','Enabled','enabled','1') OR a.Status = 1)
                ORDER BY a.Addon_Name ASC";
        $stmt = $con->prepare($sql);
        $stmt->execute([$productId]);
        $addons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        // Global list
        $sql = "SELECT Addon_ID, Addon_Name, Addon_Price
                FROM addons
                WHERE (Status IS NULL OR Status IN ('Active','active','Enabled','enabled','1') OR Status = 1)
                ORDER BY Addon_Name ASC";
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $addons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Fallback: if still empty, return all add-ons unfiltered (safety)
    if (count($addons) === 0) {
        $fallback = $con->prepare("SELECT Addon_ID, Addon_Name, Addon_Price FROM addons ORDER BY Addon_Name ASC");
        $fallback->execute();
        $addons = $fallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    echo json_encode(['success'=>true,'addons'=>$addons]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
