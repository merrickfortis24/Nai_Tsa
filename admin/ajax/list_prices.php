<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1,min(100,(int)$_GET['per_page'])) : 10;
$offset = ($page-1)*$perPage;

try {
    $con = $db->opencon();
    // Count total
    $total = (int)$con->query("SELECT COUNT(*) FROM product_price")->fetchColumn();
    $stmt = $con->prepare("SELECT Price_ID, Price_Amount, Effective_From, Effective_To FROM product_price ORDER BY Price_ID DESC LIMIT :lim OFFSET :off");
    $stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
    $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPages = (int)ceil($total / $perPage);
    echo json_encode([
        'success'=>true,
        'rows'=>$rows,
        'total'=>$total,
        'per_page'=>$perPage,
        'current_page'=>$page,
        'total_pages'=>$totalPages
    ]);
} catch(Throwable $e){
    error_log('list_prices.php: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
