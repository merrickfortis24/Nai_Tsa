<?php
// Debug endpoint: returns which recipient addresses the mailer will use (no actual send)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

try {
    $out = [
        'env' => [],
        'db_fallback' => [],
        'resolved' => null,
        'notes' => []
    ];

    // Capture env candidates
    $envForce = trim((string)(getenv('MAIL_FORCE_TO') ?: ''));
    $envTo    = trim((string)(getenv('MAIL_TO') ?: ''));
    $envUser  = trim((string)(getenv('SMTP_USER') ?: ''));
    $envFrom  = trim((string)(getenv('MAIL_FROM') ?: ''));
    $out['env'] = ['MAIL_FORCE_TO'=>$envForce,'MAIL_TO'=>$envTo,'SMTP_USER'=>$envUser,'MAIL_FROM'=>$envFrom];

    // First prefer envs
    $primary = $envForce ?: ($envTo ?: $envUser);
    if ($primary && filter_var(explode(',', $primary)[0], FILTER_VALIDATE_EMAIL)) {
        $out['resolved'] = $primary;
        echo json_encode($out);
        exit;
    }

    // Try DB fallback using same logic as mailer
    $out['notes'][] = 'Attempting DB fallback';
    // Try to include common DB helpers if getDB not defined
    if (!function_exists('getDB')) {
        $dbCandidates = [
            __DIR__ . '/../../database/database.php',
            __DIR__ . '/../../admin/classes/database.php',
            __DIR__ . '/../../users/classes/database.php',
            __DIR__ . '/../../database.php'
        ];
        foreach ($dbCandidates as $dbf) {
            if (is_file($dbf)) {
                include_once $dbf;
                if (function_exists('getDB')) break;
            }
        }
    }

    if (function_exists('getDB')) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT Admin_Email FROM admin WHERE Status = 'Active' AND Admin_Email IS NOT NULL AND Admin_Email != ''");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $emails = [];
            foreach ($rows as $r) {
                $e = trim((string)($r['Admin_Email'] ?? ''));
                if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $emails[] = $e;
            }
            $out['db_fallback'] = $emails;
            if (!empty($emails)) {
                $g = array_values(preg_grep('/@gmail\.com$/i', $emails));
                $chosen = !empty($g) ? $g : $emails;
                $out['resolved'] = implode(',', array_unique($chosen, SORT_STRING));
            } else {
                $out['notes'][] = 'No admin emails found in DB';
            }
        } catch (Throwable $e) {
            $out['notes'][] = 'DB query failed: ' . $e->getMessage();
        }
    } else {
        $out['notes'][] = 'getDB() not available and no DB helper included';
    }

    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
exit;
