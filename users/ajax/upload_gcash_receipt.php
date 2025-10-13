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

  // Validate inputs
  $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
  $ref = trim((string)($_POST['ref_number'] ?? ''));
  $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
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
  $uploadDir = realpath(__DIR__ . '/../../admin/uploads');
  if (!$uploadDir) { @mkdir(__DIR__ . '/../../admin/uploads', 0775, true); $uploadDir = realpath(__DIR__ . '/../../admin/uploads'); }
  $gcashDir = $uploadDir ? ($uploadDir . DIRECTORY_SEPARATOR . 'gcash') : null;
  if ($gcashDir && !is_dir($gcashDir)) { @mkdir($gcashDir, 0775, true); }
  if (!$gcashDir || !is_dir($gcashDir)) {
    echo json_encode(['success'=>false,'message'=>'Server storage path not available']);
    exit;
  }

  // Build safe filename
  $base = 'gcash_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
  $destPath = $gcashDir . DIRECTORY_SEPARATOR . $base;
  if (!move_uploaded_file($tmp, $destPath)) {
    echo json_encode(['success'=>false,'message'=>'Unable to save uploaded file']);
    exit;
  }

  // Store DB record in order_payment_receipt with status=pending
  $relPath = 'uploads/gcash/' . $base; // relative to admin/
  $stmt = $con->prepare("INSERT INTO order_payment_receipt (Order_ID, Proof_Photo, Reference_Number, Submitted_Amount, Status) VALUES (?,?,?,?, 'pending')");
  $stmt->execute([$orderId ?: null, $relPath, $ref, $amount]);
  $rid = (int)$con->lastInsertId();

  echo json_encode(['success'=>true,'receipt_id'=>$rid,'order_id'=>$orderId, 'file'=>$relPath]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
