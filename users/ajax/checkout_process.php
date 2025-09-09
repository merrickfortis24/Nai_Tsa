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
  echo json_encode([
    'success' => false,
    'message' => 'Server error during checkout',
    'error' => $e->getMessage()
  ]);
}
// No trailing output.
exit;