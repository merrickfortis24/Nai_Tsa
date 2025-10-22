<?php
// Ensure no BOM / whitespace before this tag. This endpoint must output ONLY JSON.
// Disable direct display of warnings/notices to avoid breaking JSON; log them instead.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=UTF-8');

// Robust include using absolute path relative to this file (users/ajax/ -> users/classes/)
require_once __DIR__ . '/../classes/database.php';
// Wrap processing in try/catch so we can return valid JSON on errors and
// log diagnostics to help identify 500 Internal Server Error causes.
try {
  // Read and decode JSON body
  $raw = file_get_contents('php://input');
  if ($raw === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unable to read input stream']);
    exit;
  }

  $data = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => 'Invalid JSON payload',
      'json_error' => json_last_error_msg(),
      'raw_sample' => substr($raw,0,200)
    ]);
    exit;
  }

  if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
  }

  $customer_name = $_SESSION['customer_name'] ?? 'Guest';
  $db = new database();

  $result = $db->processCheckout($data, $customer_name);

  // Guarantee success flag presence
  if (!isset($result['success'])) {
    $result['success'] = ($result['status'] ?? '') === 'ok' || isset($result['order_id']);
  }
  echo json_encode($result);
} catch (Throwable $e) {
  http_response_code(500);

  // Log the exception to a local log so we can inspect it on the server.
  $logDir = __DIR__ . '/tmp';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
  }
  $logFile = $logDir . '/checkout_error.log';
  $logEntry = date('c') . " | Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n---\n";
  @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

  // Allow verbose error JSON only when explicitly requested via ?debug=1
  $isDebug = false;
  if (isset($_GET['debug']) && in_array((string)$_GET['debug'], ['1', 'true', 'on'], true)) {
    $isDebug = true;
  }

  $resp = ['success' => false, 'message' => 'Server error during checkout'];
  if ($isDebug) {
    $resp['error'] = $e->getMessage();
    $resp['trace'] = $e->getTraceAsString();
    $resp['log_file'] = str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', $logFile);
  }
  echo json_encode($resp);
}
// No trailing output.
exit;