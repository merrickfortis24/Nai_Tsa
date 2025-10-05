<?php

require_once('classes/database.php');
$db = new database();

try {
    $pendingProcessingCount = $db->countPendingOrProcessingOrders();
} catch (PDOException $e) {
    $pendingProcessingCount = 0;
}

try {
    $unpaidPayments = $db->countUnpaidPayments();
} catch (PDOException $e) {
    $unpaidPayments = 0;
}

// Count blocked users (if table exists)
try {
    $con = $db->opencon();
    $chk = $con->query("SHOW TABLES LIKE 'blocked_users'");
    if ($chk && $chk->rowCount()>0) {
        $blockedUsersCount = (int)$con->query("SELECT COUNT(*) FROM blocked_users")->fetchColumn();
    } else {
        $blockedUsersCount = 0;
    }
} catch (Throwable $e) {
    $blockedUsersCount = 0;
}
?>