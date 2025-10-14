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
    // Remove from blocked_users (if present)
    $deleted = 0;
    try {
        $stmtDel = $con->prepare("DELETE FROM blocked_users WHERE customer_id=?");
        $stmtDel->execute([$cid]);
        $deleted = $stmtDel->rowCount();
    } catch(Throwable $e) {
        error_log("fraud_unblock: failed to delete blocked_users for {$cid}: " . $e->getMessage());
    }

    // Reset customer flag if column exists - use prepared statement and report result
    $clearedFlag = 0;
    try {
        $stmtUpd = $con->prepare("UPDATE customer SET is_blocked=0 WHERE Customer_ID=?");
        $stmtUpd->execute([$cid]);
        $clearedFlag = $stmtUpd->rowCount();
    } catch(Throwable $e) {
        error_log("fraud_unblock: failed to update customer.is_blocked for {$cid}: " . $e->getMessage());
    }

    // Log unblock event
    $fb = new FraudBlocker();
    $fb->logEvent($cid,'UNBLOCK','Manual unblock', $_SESSION['Admin_ID'] ?? $_SESSION['admin_id'] ?? null);

    // Return detailed result so caller can surface if DB was inconsistent
    $msg = 'Unblock processed';
    $details = ['deleted_from_blocked_users' => (int)$deleted, 'cleared_customer_flag' => (int)$clearedFlag];
    if ($deleted === 0 && $clearedFlag === 0) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Unblock attempted but no changes applied','details'=>$details]);
    } else {
        echo json_encode(['success'=>true,'message'=>$msg,'details'=>$details]);
    }
} catch(Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
?>
