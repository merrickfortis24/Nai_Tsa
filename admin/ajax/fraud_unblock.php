<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/fraud_blocker.php';
try {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw,true) ?: [];
    $cid = isset($j['customer_id']) ? (int)$j['customer_id'] : 0;
    if ($cid <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid customer_id']); exit; }
    $db = new database();
    $con = $db->opencon();
    // Remove from blocked_users
    $con->prepare("DELETE FROM blocked_users WHERE customer_id=?")->execute([$cid]);
    // Reset customer flag if column exists
    try { $con->exec("UPDATE customer SET is_blocked=0 WHERE Customer_ID=".$cid); } catch(Throwable $e){}
    // Log
    $fb = new FraudBlocker();
    $fb->logEvent($cid,'UNBLOCK','Manual unblock', $_SESSION['Admin_ID'] ?? $_SESSION['admin_id'] ?? null);
    echo json_encode(['success'=>true,'message'=>'Unblocked']);
} catch(Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
?>
