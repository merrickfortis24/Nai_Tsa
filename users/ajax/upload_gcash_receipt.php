<?php
// Handle GCash receipt upload + metadata (reference number, amount) and link to an order.
// Accepts multipart/form-data with fields: order_id (optional until order created), ref_number, amount, receipt (file)
// Returns JSON: { success, message, receipt_id, order_id }

session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../classes/database.php';

try {
  if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
  }

  $db = new database();
  $con = $db->opencon();

  // Ensure target table exists to avoid PDO exceptions on fresh deployments
  $ensureTable = function(PDO $con){
    try {
      $chk = $con->query("SHOW TABLES LIKE 'order_payment_receipt'");
      if ($chk && $chk->rowCount() > 0) return; // table already exists
    } catch (Throwable $e) { /* continue to attempt create */ }
    try {
      $con->exec(
        "CREATE TABLE IF NOT EXISTS order_payment_receipt (
           Payment_Receipt_ID INT NOT NULL AUTO_INCREMENT,
           Order_ID INT NULL,
           Proof_Photo VARCHAR(255) NOT NULL,
           Reference_Number VARCHAR(100) NULL,
           Submitted_Amount DECIMAL(10,2) NULL,
           Status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
           Verified_By INT NULL,
           Verified_At DATETIME NULL,
           Reject_Reason VARCHAR(255) NULL,
           Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (Payment_Receipt_ID),
           INDEX idx_opr_order (Order_ID)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
      );
      // Best-effort: add FK if possible (ignore failures silently)
      try { $con->exec("ALTER TABLE order_payment_receipt ADD CONSTRAINT fk_opr_order FOREIGN KEY (Order_ID) REFERENCES orders (Order_ID) ON DELETE SET NULL ON UPDATE CASCADE"); } catch (Throwable $e) {}
    } catch (Throwable $e) {
      // Swallow create errors; insert will fail later with a clearer message
    }
  };
  $ensureTable($con);

  // Validate inputs
  $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
  $ref = trim((string)($_POST['ref_number'] ?? ''));
  $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
  if ($orderId <= 0) {
    echo json_encode(['success'=>false,'message'=>'Missing or invalid order ID']);
    exit;
  }
  if ($ref === '' || $amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Reference number and amount are required']);
    exit;
  }

  if (!isset($_FILES['receipt']) || !is_uploaded_file($_FILES['receipt']['tmp_name'])) {
    echo json_encode(['success'=>false,'message'=>'Receipt image is required']);
    exit;
  }

  // Basic file validation
  $err = $_FILES['receipt']['error'];
  if ($err !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'Upload failed (code '.$err.')']);
    exit;
  }
  $tmp = $_FILES['receipt']['tmp_name'];
  $name = $_FILES['receipt']['name'];
  $size = (int)$_FILES['receipt']['size'];
  if ($size <= 0 || $size > 6*1024*1024) { // 6 MB limit
    echo json_encode(['success'=>false,'message'=>'Invalid file size. Max 6MB']);
    exit;
  }
  $finfo = @getimagesize($tmp);
  if ($finfo === false) {
    echo json_encode(['success'=>false,'message'=>'File is not a valid image']);
    exit;
  }
  $ext = image_type_to_extension($finfo[2], false); // jpg, png, etc
  if (!in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
    echo json_encode(['success'=>false,'message'=>'Unsupported image type']);
    exit;
  }

  // Ensure upload directory exists (store under ../admin/uploads/gcash)
  $root = realpath(__DIR__ . '/../../');
  if (!$root) { $root = dirname(dirname(__DIR__), 1); }
  $uploadsBase = $root . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'uploads';
  if (!is_dir($uploadsBase)) { @mkdir($uploadsBase, 0775, true); }
  $gcashDir = $uploadsBase . DIRECTORY_SEPARATOR . 'gcash';
  if (!is_dir($gcashDir)) { @mkdir($gcashDir, 0775, true); }
  if (!is_dir($gcashDir) || !is_writable($gcashDir)) {
    echo json_encode(['success'=>false,'message'=>'Server storage path not available or not writable']);
    exit;
  }

  // Build safe filename (avoid dependency on random_bytes if unavailable)
  $rand = null;
  if (function_exists('random_bytes')) {
    try { $rand = bin2hex(random_bytes(4)); } catch (Throwable $e) { $rand = null; }
  }
  if (!$rand && function_exists('openssl_random_pseudo_bytes')) {
    try { $rand = bin2hex(openssl_random_pseudo_bytes(4)); } catch (Throwable $e) { $rand = null; }
  }
  if (!$rand) { $rand = str_replace('.', '', uniqid('', true)); }
  $base = 'gcash_' . date('Ymd_His') . '_' . $rand . '.' . strtolower($ext);
  $destPath = $gcashDir . DIRECTORY_SEPARATOR . $base;
  if (!move_uploaded_file($tmp, $destPath)) {
    echo json_encode(['success'=>false,'message'=>'Unable to save uploaded file']);
    exit;
  }

  // Store DB record in order_payment_receipt with status=pending
  $relPath = 'uploads/gcash/' . $base; // relative to admin/
  // Detect proper Status casing if enum differs (e.g., 'Pending' vs 'pending')
  $statusVal = 'pending';
  try {
    $col = $con->query("SHOW COLUMNS FROM order_payment_receipt LIKE 'Status'");
    $info = $col ? $col->fetch(PDO::FETCH_ASSOC) : null;
    if ($info && isset($info['Type'])) {
      $type = strtolower((string)$info['Type']); // example: enum('pending','verified','rejected')
      if (strpos($type, "'pending'") === false && strpos(strtolower($type), "'pending'") === false) {
        // Try uppercase 'Pending'
        if (stripos($info['Type'], "'Pending'") !== false) { $statusVal = 'Pending'; }
      }
    }
  } catch (Throwable $e) { /* ignore; default to 'pending' */ }
  $stmt = $con->prepare("INSERT INTO order_payment_receipt (Order_ID, Proof_Photo, Reference_Number, Submitted_Amount, Status) VALUES (?,?,?,?, ?)");
  $stmt->execute([$orderId, $relPath, $ref, $amount, $statusVal]);
  $rid = (int)$con->lastInsertId();

  echo json_encode(['success'=>true,'receipt_id'=>$rid,'order_id'=>$orderId, 'file'=>$relPath]);
} catch (Throwable $e) {
  http_response_code(500);
  // Log server-side for diagnostics
  error_log('upload_gcash_receipt.php error: ' . $e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
