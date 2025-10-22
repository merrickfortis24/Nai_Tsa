<?php
// Endpoint: users/ajax/notify_new_order.php
// Accepts JSON { order_id: int } and triggers send_new_order_email() defined in utils/mailer.php
// Returns JSON { success: bool, message?: string }

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../utils/mailer.php';
// Do NOT require admin/classes/database.php here to avoid class name collisions across different DB wrappers.
// We try to enrich the email with order details only if a compatible DB helper is available. Otherwise send minimal notification.

try {
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No input']);
        exit;
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    $orderId = isset($data['order_id']) ? intval($data['order_id']) : 0;
    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing order_id']);
        exit;
    }

    // Try to include order summary if an admin DB wrapper is present (admin/classes/database.php)
    $summary = [];
    $adminDbPath = __DIR__ . '/../../admin/classes/database.php';
    if (is_file($adminDbPath)) {
        try {
            require_once $adminDbPath; // class 'database' will be available
            $db = new database();
            if (method_exists($db, 'fetchOrderById')) {
                $order = $db->fetchOrderById($orderId);
                if ($order) {
                    $summary['customer_email'] = $order['Email'] ?? ($order['customer_email'] ?? null);
                    $summary['amount'] = $order['Total_Amount'] ?? $order['amount'] ?? null;
                }
            }
            if (method_exists($db, 'fetchOrderItems')) {
                $items = $db->fetchOrderItems($orderId);
                if (is_array($items) && $items) {
                    $summary['items'] = array_map(function($it){
                        return [
                            'name' => $it['Product_Name'] ?? $it['name'] ?? '',
                            'qty' => $it['Quantity'] ?? $it['qty'] ?? 1
                        ];
                    }, $items);
                }
            }
        } catch (Throwable $e) {
            error_log('[notify_new_order] admin DB fetch failed: ' . $e->getMessage());
            // Continue with minimal summary
        }
    }

    $res = send_new_order_email($orderId, $summary);

    // If admin DB wrapper is available, insert a notifications row so signed-in admins see it in the UI
    try {
        if (isset($db) && method_exists($db, 'opencon')) {
            try {
                $conAdmin = $db->opencon();
                // Ensure notifications table exists (try not to change existing schema if present)
                try {
                    $conAdmin->exec("CREATE TABLE IF NOT EXISTS notifications (
                        Notification_ID INT NOT NULL AUTO_INCREMENT,
                        Type VARCHAR(30) DEFAULT '',
                        Title VARCHAR(150) NOT NULL,
                        Message TEXT NOT NULL,
                        Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        Read_At DATETIME DEFAULT NULL,
                        Is_Read TINYINT(1) DEFAULT NULL,
                        PRIMARY KEY (Notification_ID)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                } catch (Throwable $e) {
                    // ignore create failures (we'll adapt to existing schema)
                }

                $title = 'New Order #' . intval($orderId);
                $msgParts = [];
                if (!empty($summary['customer_email'])) $msgParts[] = 'Customer: ' . $summary['customer_email'];
                if (!empty($summary['amount'])) $msgParts[] = 'Amount: ₱' . number_format((float)$summary['amount'],2);
                if (!empty($summary['items']) && is_array($summary['items'])) {
                    $first = array_slice($summary['items'],0,3);
                    $names = array_map(function($it){ return ($it['name'] ?? '').' x'.intval($it['qty'] ?? 1); }, $first);
                    $msgParts[] = 'Items: ' . implode(', ', $names) . (count($summary['items'])>3? '...' : '');
                }
                $message = $msgParts ? implode(' | ', $msgParts) : 'A new order has been placed.';

                // Detect which columns exist and insert accordingly to support multiple schema versions
                $cols = [];
                try {
                    $stmtCols = $conAdmin->query("SHOW COLUMNS FROM notifications");
                    $cols = $stmtCols ? array_map(fn($r)=> $r['Field'], $stmtCols->fetchAll(PDO::FETCH_ASSOC)) : [];
                } catch (Throwable $e) { $cols = []; }

                if (in_array('Is_Read', $cols, true)) {
                    $ins = $conAdmin->prepare("INSERT INTO notifications (Title, Message, Created_At, Is_Read) VALUES (?, ?, NOW(), 0)");
                    $ins->execute([$title, $message]);
                } elseif (in_array('Read_At', $cols, true) && in_array('Type', $cols, true)) {
                    $ins = $conAdmin->prepare("INSERT INTO notifications (Type, Title, Message, Created_At, Read_At) VALUES (?, ?, ?, NOW(), NULL)");
                    $ins->execute(['order', $title, $message]);
                } else {
                    // Fallback: try a minimal insert (Title, Message, Created_At)
                    try {
                        $ins = $conAdmin->prepare("INSERT INTO notifications (Title, Message, Created_At) VALUES (?, ?, NOW())");
                        $ins->execute([$title, $message]);
                    } catch (Throwable $ei) {
                        // give up silently
                        error_log('[notify_new_order] could not insert notification: ' . $ei->getMessage());
                    }
                }
            } catch (Throwable $e) {
                error_log('[notify_new_order] failed to insert admin notification: ' . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        // swallow
    }

    if ($res === true) {
        echo json_encode(['success' => true]);
        exit;
    }
    // send_new_order_email returns string on error
    error_log('[notify_new_order] send failed for order ' . $orderId . ' : ' . (string)$res);
    echo json_encode(['success' => false, 'message' => (string)$res]);
} catch (Throwable $e) {
    error_log('[notify_new_order] exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

exit;
