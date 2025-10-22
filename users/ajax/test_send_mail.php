<?php
// Simple test endpoint to exercise send_new_order_email() without placing a real order.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../../utils/mailer.php';

try {
    $sampleOrderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 999999;
    $summary = [
        'customer_email' => 'tester@example.com',
        'amount' => 123.45,
        'items' => [ ['name' => 'Test Coffee', 'qty' => 1], ['name' => 'Sample Milk Tea', 'qty' => 2] ]
    ];
    // If debug mode requested, return resolved recipients without sending
    if (isset($_GET['debug']) && $_GET['debug']) {
        $dbg = intval($_GET['debug']);
        if ($dbg === 2) {
            // Provide include path diagnostics
            $candidates = [
                __DIR__ . '/../database/database.php',
                __DIR__ . '/../../database/database.php',
                __DIR__ . '/../users/classes/database.php',
                __DIR__ . '/../admin/classes/database.php',
                __DIR__ . '/../../admin/classes/database.php',
                __DIR__ . '/../../database.php'
            ];
            $checks = [];
            foreach ($candidates as $c) {
                $checks[] = [
                    'path' => $c,
                    'realpath' => is_file($c) ? realpath($c) : null,
                    'exists' => is_file($c),
                    'readable' => is_readable($c)
                ];
            }
            $out = [
                'cwd' => getcwd(),
                'dir' => __DIR__,
                'php_sapi' => PHP_SAPI,
                'getDB_defined_before' => function_exists('getDB'),
                'candidates' => $checks
            ];
            echo json_encode(['success'=>true,'diagnostic'=>$out], JSON_PRETTY_PRINT);
            exit;
        }
        $debugInfo = mailer_resolve_recipients();
        echo json_encode(['success'=>true,'debug'=>$debugInfo]);
        exit;
    }

    $res = send_new_order_email($sampleOrderId, $summary);
    if ($res === true) {
        echo json_encode(['success' => true, 'message' => 'Mail sent (mailer reported success)']);
        exit;
    }
    // returns string on error
    error_log('[test_send_mail] send_new_order_email error: ' . (string)$res);
    echo json_encode(['success' => false, 'message' => (string)$res]);
} catch (Throwable $e) {
    error_log('[test_send_mail] exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server exception']);
}
exit;
