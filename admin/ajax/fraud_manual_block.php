<?php
require_once __DIR__.'/../classes/database.php';
require_once __DIR__.'/../classes/fraud_blocker.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if(!$input) throw new Exception('Invalid JSON');

    $customerId = (int)($input['customer_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Manual block');
    if($customerId <= 0) throw new Exception('Missing customer_id');
    if($reason === '') $reason = 'Manual block';

    $dbClass = new Database();
    $pdo = $dbClass->opencon();
    $fb = new FraudBlocker($pdo);

    // Ensure customer exists
    $stmt = $pdo->prepare('SELECT Customer_ID, Customer_Email, Customer_Name FROM customer WHERE Customer_ID = :cid LIMIT 1');
    $stmt->execute([':cid'=>$customerId]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$cust) throw new Exception('Customer not found');

    // Already blocked?
    $stmt2 = $pdo->prepare('SELECT 1 FROM blocked_users WHERE customer_id = :cid LIMIT 1');
    $stmt2->execute([':cid'=>$customerId]);
    if($stmt2->fetch()) {
        echo json_encode(['success'=>true,'already'=>true,'message'=>'Already blocked']);
        exit;
    }

    $fb->blockCustomer($customerId, $reason, false); // manual block

    echo json_encode(['success'=>true,'blocked'=>true]);
} catch(Exception $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
