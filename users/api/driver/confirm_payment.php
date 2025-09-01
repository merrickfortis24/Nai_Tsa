<?php
// Driver confirms payment for an order (COD)
// Auth: Bearer <Api_Token>
// Input: { order_id: number }
// Output: { success, message }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo json_encode(['ok' => true]); exit; }

require_once __DIR__ . '/../../classes/database.php';

function get_auth_token(): ?string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (!$hdr && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $hdr = $headers['Authorization'] ?? '';
    }
    if (!$hdr) return null;
    if (stripos($hdr, 'Bearer ') === 0) {
        return trim(substr($hdr, 7));
    }
    return null;
}

try {
    $db = new database();
    $con = $db->opencon();
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection error']);
    exit;
}

// Parse input
$raw = file_get_contents('php://input') ?: '';
$ctype = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$in = [];
if (stripos($ctype, 'application/json') !== false) {
    $in = json_decode($raw, true) ?: [];
} elseif (!empty($_POST)) {
    $in = $_POST;
} elseif ($raw !== '') {
    $tmp = json_decode($raw, true);
    if (is_array($tmp)) { $in = $tmp; } else { parse_str($raw, $in); }
}

$orderId = (int)($in['order_id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

// Auth
$token = get_auth_token();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing bearer token']);
    exit;
}

try {
    // Validate token
    $stmt = $con->prepare("SELECT Driver_ID, Name, Token_Expires FROM drivers WHERE Api_Token = :t LIMIT 1");
    $stmt->execute([':t' => $token]);
    $drv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$drv) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    if (!empty($drv['Token_Expires']) && strtotime($drv['Token_Expires']) < time()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token expired']);
        exit;
    }

    // Ensure order exists
    $chk = $con->prepare('SELECT Order_ID FROM orders WHERE Order_ID = :id');
    $chk->execute([':id' => $orderId]);
    if (!$chk->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $con->beginTransaction();

    // Mark payment as Paid
    $upPay = $con->prepare("UPDATE payment SET payment_status = 'Paid' WHERE Order_ID = :id");
    $upPay->execute([':id' => $orderId]);

    // Optionally tag orders with received_at/by if columns exist
    try {
        $hasRecvAt = $con->query("SHOW COLUMNS FROM orders LIKE 'payment_received_at'");
        $hasRecvBy = $con->query("SHOW COLUMNS FROM orders LIKE 'payment_received_by'");
        if ($hasRecvAt && $hasRecvAt->rowCount() > 0 && $hasRecvBy && $hasRecvBy->rowCount() > 0) {
            $upOrd = $con->prepare("UPDATE orders SET payment_received_at = NOW(), payment_received_by = :by WHERE Order_ID = :id");
            $by = 'Driver #' . (int)$drv['Driver_ID'] . ' - ' . (string)$drv['Name'];
            $upOrd->execute([':by' => $by, ':id' => $orderId]);
        }
    } catch (Throwable $e) { /* ignore schema probe errors */ }

    // Ensure notifications table exists and insert a record
    $con->exec("CREATE TABLE IF NOT EXISTS notifications (
        Notification_ID INT NOT NULL AUTO_INCREMENT,
        Title VARCHAR(150) NOT NULL,
        Message TEXT NOT NULL,
        Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Is_Read TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (Notification_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $title = 'Payment Confirmed';
    $msg = 'Driver ' . (string)$drv['Name'] . ' confirmed payment for Order #' . $orderId;
    $ins = $con->prepare('INSERT INTO notifications (Title, Message) VALUES (:t, :m)');
    $ins->execute([':t' => $title, ':m' => $msg]);

    $con->commit();

    echo json_encode(['success' => true, 'message' => 'Payment marked as Paid and notification sent']);
} catch (Throwable $e) {
    if ($con && $con->inTransaction()) { $con->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

?>
