<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

// Ensure tables exist (safe no-ops if already there)
function ensureAddonsTables(database $db){
    $con = $db->opencon();
    $con->exec("CREATE TABLE IF NOT EXISTS addons (
        Addon_ID INT NOT NULL AUTO_INCREMENT,
        Addon_Name VARCHAR(100) NOT NULL,
        Addon_Price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        Status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (Addon_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

try {
    $helper = new addons_helper();
    ensureAddonsTables($helper);
    $addons = $helper->getAllAddons();
    echo json_encode(['success'=>true,'addons'=>$addons]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
