<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../classes/database.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 20;
    if ($pageSize <= 0) $pageSize = 20;
    if ($pageSize > 100) $pageSize = 100;

    $start = isset($_GET['start']) ? trim($_GET['start']) : '';
    $end = isset($_GET['end']) ? trim($_GET['end']) : '';
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $source = isset($_GET['source']) ? trim($_GET['source']) : '';

    // Normalize dates (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
    $startDt = null; $endDt = null;
    if ($start !== '') {
        $startDt = strlen($start) === 10 ? ($start.' 00:00:00') : $start;
    }
    if ($end !== '') {
        $endDt = strlen($end) === 10 ? ($end.' 23:59:59') : $end;
    }

    $db = new database();
    $con = $db->opencon();

    $where = ["l.action='BLOCK'"]; $params = [];
    if ($startDt) { $where[] = 'l.created_at >= :start'; $params[':start'] = $startDt; }
    if ($endDt) { $where[] = 'l.created_at <= :end'; $params[':end'] = $endDt; }
    if ($source === 'auto') { $where[] = 'l.admin_id IS NULL'; }
    if ($source === 'admin') { $where[] = 'l.admin_id IS NOT NULL'; }
    if ($q !== '') {
        $where[] = "(CAST(l.customer_id AS CHAR) LIKE :q OR c.Customer_Name LIKE :q OR c.Customer_Email LIKE :q OR l.reason LIKE :q)";
        $params[':q'] = '%'.$q.'%';
    }
    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // Total count
    $sqlCount = "SELECT COUNT(*) FROM blocked_users_log l LEFT JOIN customer c ON c.Customer_ID=l.customer_id $whereSql";
    $stmt = $con->prepare($sqlCount);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $sql = "SELECT l.customer_id, l.reason, l.admin_id, l.created_at, c.Customer_Name, c.Customer_Email
            FROM blocked_users_log l
            LEFT JOIN customer c ON c.Customer_ID=l.customer_id
            $whereSql
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt2 = $con->prepare($sql);
    foreach ($params as $k=>$v) { $stmt2->bindValue($k, $v); }
    $stmt2->bindValue(':limit', (int)$pageSize, PDO::PARAM_INT);
    $stmt2->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt2->execute();
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => max(1, (int)ceil($total / $pageSize)),
        'items' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
?>
