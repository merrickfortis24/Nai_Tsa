<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/database.php';
$db = new database();
$con = $db->opencon();

// Ensure notifications table exists
$con->exec("CREATE TABLE IF NOT EXISTS notifications (
    Notification_ID INT NOT NULL AUTO_INCREMENT,
    Title VARCHAR(150) NOT NULL,
    Message TEXT NOT NULL,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Is_Read TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (Notification_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Choose unread filter depending on schema: prefer Is_Read, fallback to Read_At IS NULL
$unreadSql = '';
try {
    $colsStmt = $con->query("SHOW COLUMNS FROM notifications");
    $cols = $colsStmt ? array_map(function($r){ return $r['Field']; }, $colsStmt->fetchAll(PDO::FETCH_ASSOC)) : [];
} catch (Throwable $e) { $cols = []; }
if (in_array('Is_Read', $cols, true)) {
    $unreadSql = "SELECT Notification_ID, Title, Message, Created_At FROM notifications WHERE Is_Read = 0 ORDER BY Created_At DESC LIMIT 10";
} elseif (in_array('Read_At', $cols, true)) {
    $unreadSql = "SELECT Notification_ID, Title, Message, Created_At FROM notifications WHERE Read_At IS NULL ORDER BY Created_At DESC LIMIT 10";
} else {
    // Fallback: return last 10
    $unreadSql = "SELECT Notification_ID, Title, Message, Created_At FROM notifications ORDER BY Created_At DESC LIMIT 10";
}
$stmt = $con->query($unreadSql);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
echo json_encode(['success' => true, 'data' => $rows]);
