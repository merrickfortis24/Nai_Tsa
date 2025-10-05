<?php
// Admin endpoint: run (or simulate) fraud detection.
// GET params:
//   dry=1   -> simulate only (no blocking)
//   detail=1 -> include full evaluated metrics
//
// Response JSON:
// { success:true, simulate:false, blocked_now:[...], evaluated_count:N, evaluated:[...] }

session_start();
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { // adapt to whichever session key is used
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/fraud_blocker.php';

try {
    $dry = isset($_GET['dry']) && $_GET['dry'] == '1';
    $detail = isset($_GET['detail']) && $_GET['detail'] == '1';
    $fb = new FraudBlocker(['simulate'=>$dry]);
    $res = $fb->runDetection();
    if (!$detail) {
        unset($res['evaluated']); // trim payload if not requested
    }
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
?>
