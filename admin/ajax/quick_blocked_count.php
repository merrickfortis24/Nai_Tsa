<?php
// Lightweight JSON count of blocked users for dashboard auto-refresh.
session_start();
header('Content-Type: application/json; charset=UTF-8');
// Optional: allow dashboard even if not admin session (keep secure: require admin)
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../classes/database.php';
$db = new database();
$count = 0;
try {
    $con = $db->opencon();
    $chk = $con->query("SHOW TABLES LIKE 'blocked_users'");
    if ($chk && $chk->rowCount()>0) {
        $count = (int)$con->query("SELECT COUNT(*) FROM blocked_users")->fetchColumn();
    }
} catch (Throwable $e) { /* ignore */ }
echo json_encode(['success'=>true,'count'=>$count]);
?>
