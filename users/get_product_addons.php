<?php
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json');
require_once __DIR__ . '/classes/database.php';

try {
    $db = new database();
    $con = $db->opencon();
    // Global add-ons: ignore product mapping, return active (robust filter)
    $sql = "SELECT Addon_ID, Addon_Name, Addon_Price
            FROM addons
            WHERE (Status IS NULL
                   OR Status = 'Active' OR Status = 'active'
                   OR Status = 'Enabled' OR Status = 'enabled'
                   OR Status = '1' OR Status = 1 OR Status = true)
            ORDER BY Addon_Name ASC";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $addons = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Fallback: if nothing matched, return all add-ons
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
