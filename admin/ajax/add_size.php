<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id    = isset($_POST['product_id'])? (int)$_POST['product_id'] : 0;
$size_code_raw = isset($_POST['size_code'])? trim($_POST['size_code']) : '';
$display_name  = isset($_POST['display_name'])? trim($_POST['display_name']) : '';
$price_amount  = isset($_POST['price_amount'])? (float)$_POST['price_amount'] : 0.0;
$is_absolute   = isset($_POST['is_absolute'])? (int)$_POST['is_absolute'] : 0; // 1 = Absolute, 0 = Delta

if($product_id<=0 || $size_code_raw===''){ echo json_encode(['success'=>false,'message'=>'Missing product or size']); exit; }

// Normalize code (machine) & display (human)
$norm_code = strtolower(preg_replace('/\s+/','_', $size_code_raw));
if(strlen($norm_code) > 32){ echo json_encode(['success'=>false,'message'=>'Size code too long']); exit; }
if($display_name==='') $display_name = ucwords(str_replace(['_','-'],' ', $size_code_raw));

try {
  $con = $db->opencon();
  // Create master sizes table (global) & product_size_price (mapping) if not exists
  $con->exec("CREATE TABLE IF NOT EXISTS sizes (
    Size_ID INT AUTO_INCREMENT PRIMARY KEY,
    Size_Code VARCHAR(32) NOT NULL UNIQUE,
    Display_Name VARCHAR(64) NOT NULL,
    Category_Scope VARCHAR(64) NULL,
    Sort_Order INT NOT NULL DEFAULT 0,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $con->exec("CREATE TABLE IF NOT EXISTS product_size_price (
    Product_Size_Price_ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Size_ID INT NOT NULL,
    Price_Mode ENUM('ABS','DELTA') NOT NULL DEFAULT 'ABS',
    Price_Value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prod_size (Product_ID, Size_ID),
    INDEX (Product_ID),
    INDEX (Size_ID),
    CONSTRAINT fk_psp_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
    CONSTRAINT fk_psp_size FOREIGN KEY (Size_ID) REFERENCES sizes(Size_ID) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Ensure size in master
  $stmt = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name) VALUES (?,?) ON DUPLICATE KEY UPDATE Display_Name=VALUES(Display_Name)");
  $stmt->execute([$norm_code,$display_name]);
  $sizeId = (int)$con->lastInsertId();
  if($sizeId === 0){
    // fetch existing id
    $g = $con->prepare("SELECT Size_ID FROM sizes WHERE Size_Code=? LIMIT 1");
    $g->execute([$norm_code]);
    $sizeId = (int)$g->fetchColumn();
  }
  if($sizeId<=0){ echo json_encode(['success'=>false,'message'=>'Failed to resolve size id']); exit; }

  $mode = $is_absolute ? 'ABS' : 'DELTA';
  $stmt2 = $con->prepare("INSERT INTO product_size_price (Product_ID, Size_ID, Price_Mode, Price_Value) VALUES (?,?,?,?)
              ON DUPLICATE KEY UPDATE Price_Mode=VALUES(Price_Mode), Price_Value=VALUES(Price_Value)");
  $ok = $stmt2->execute([$product_id,$sizeId,$mode,$price_amount]);

  echo json_encode(['success'=>$ok,'size_code'=>$norm_code,'mode'=>$mode]);
} catch(Throwable $e){
  error_log('add_size.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
