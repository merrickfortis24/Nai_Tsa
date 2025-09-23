<?php
// Simple DB connectivity test for debugging
require_once __DIR__ . '/classes/database.php';
header('Content-Type: application/json; charset=UTF-8');
try {
    $db = new database();
    $con = $db->opencon();
    $one = $con->query('SELECT 1')->fetchColumn();
    echo json_encode(['success' => true, 'message' => 'DB connect OK', 'test' => (int)$one]);
} catch (Throwable $e) {
    // echo the error so you can paste it here; also log full trace on server
    error_log('admin/test_db_conn.php exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'DB connect FAILED', 'error' => $e->getMessage()]);
}
