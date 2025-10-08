<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id    = isset($_POST['product_id'])? (int)$_POST['product_id'] : 0;
$size_code_raw = isset($_POST['size_code'])? trim($_POST['size_code']) : '';
$display_name  = isset($_POST['display_name'])? trim($_POST['display_name']) : '';
$price_amount  = isset($_POST['price_amount'])? (float)$_POST['price_amount'] : null;
$price_id      = isset($_POST['price_id'])? (int)$_POST['price_id'] : 0;
// is_absolute now means: request to create anchor (ABS). Only allowed if product has no anchor yet.
$is_absolute   = isset($_POST['is_absolute'])? (int)$_POST['is_absolute'] : 0; // 1 = Anchor ABS, 0 = DELTA
$sort_order    = isset($_POST['sort_order'])? (int)$_POST['sort_order'] : null;

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
    Price_Source_ID INT NULL,
    Is_Anchor TINYINT(1) NOT NULL DEFAULT 0,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prod_size (Product_ID, Size_ID),
    INDEX (Product_ID),
    INDEX (Size_ID),
    INDEX (Price_Source_ID),
    INDEX (Is_Anchor),
    CONSTRAINT fk_psp_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
    CONSTRAINT fk_psp_size FOREIGN KEY (Size_ID) REFERENCES sizes(Size_ID) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  // Ensure column exists if table was old
  try { $cc=$con->query("SHOW COLUMNS FROM product_size_price LIKE 'Price_Source_ID'"); if($cc&&$cc->rowCount()==0){ $con->exec("ALTER TABLE product_size_price ADD COLUMN Price_Source_ID INT NULL AFTER Price_Value, ADD INDEX (Price_Source_ID)"); } } catch(Throwable $ignore) {}
  // Anchor column (Approach B)
  try { $cc2=$con->query("SHOW COLUMNS FROM product_size_price LIKE 'Is_Anchor'"); if($cc2&&$cc2->rowCount()==0){ $con->exec("ALTER TABLE product_size_price ADD COLUMN Is_Anchor TINYINT(1) NOT NULL DEFAULT 0 AFTER Price_Source_ID, ADD INDEX (Is_Anchor)"); } } catch(Throwable $ignore) {}
  // One-time migration: mark first ABS per product as anchor where none set
  try { $con->exec("UPDATE product_size_price p JOIN (SELECT Product_ID, MIN(Product_Size_Price_ID) mid FROM product_size_price WHERE Price_Mode='ABS' GROUP BY Product_ID) t ON p.Product_ID=t.Product_ID AND p.Product_Size_Price_ID=t.mid SET p.Is_Anchor=1 WHERE p.Is_Anchor=0"); } catch(Throwable $migr) {}

  // Ensure size in master
  // If sort_order provided ensure uniqueness per size if not used; we keep simple incremental otherwise
  if($sort_order !== null){
    // If another size already has this Sort_Order and code differs, bump provided +1 until free
    $probe = $con->prepare("SELECT 1 FROM sizes WHERE Sort_Order=? AND Size_Code<>? LIMIT 1");
    while(true){
      $probe->execute([$sort_order,$norm_code]);
      if(!$probe->fetch()) break; // free slot
      $sort_order++;
    }
    $stmt = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name, Sort_Order) VALUES (?,?,?) ON DUPLICATE KEY UPDATE Display_Name=VALUES(Display_Name), Sort_Order=VALUES(Sort_Order)");
    $stmt->execute([$norm_code,$display_name,$sort_order]);
  } else {
    $stmt = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name) VALUES (?,?) ON DUPLICATE KEY UPDATE Display_Name=VALUES(Display_Name)");
    $stmt->execute([$norm_code,$display_name]);
  }
  $sizeId = (int)$con->lastInsertId();
  if($sizeId === 0){
    // fetch existing id
    $g = $con->prepare("SELECT Size_ID FROM sizes WHERE Size_Code=? LIMIT 1");
    $g->execute([$norm_code]);
    $sizeId = (int)$g->fetchColumn();
  }
  if($sizeId<=0){ echo json_encode(['success'=>false,'message'=>'Failed to resolve size id']); exit; }

  // Resolve price value: prefer price_id lookup, fallback to provided amount (for backward compatibility)
  $resolvedPrice = 0.0;
  if($price_id>0){
    $pstmt = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=? LIMIT 1");
    $pstmt->execute([$price_id]);
    $pv = $pstmt->fetchColumn();
    if($pv !== false) $resolvedPrice = (float)$pv;
  } elseif($price_amount !== null){
    $resolvedPrice = (float)$price_amount;
  }
  // Enforce anchor rules (Approach B): exactly one anchor (ABS) per product; all non-anchor are DELTA.
  $anchorCheck = $con->prepare("SELECT Product_Size_Price_ID FROM product_size_price WHERE Product_ID=? AND Is_Anchor=1 LIMIT 1");
  $anchorCheck->execute([$product_id]);
  $hasAnchor = (bool)$anchorCheck->fetchColumn();
  if(!$hasAnchor && !$is_absolute){
    echo json_encode(['success'=>false,'message'=>'Add an Absolute (anchor) size first before adding delta sizes.']); exit;
  }
  if($hasAnchor && $is_absolute){
    echo json_encode(['success'=>false,'message'=>'Anchor already exists. You cannot add another absolute anchor.']); exit;
  }
  $mode = $is_absolute ? 'ABS' : 'DELTA';
  $isAnchor = $is_absolute ? 1 : 0;
  $stmt2 = $con->prepare("INSERT INTO product_size_price (Product_ID, Size_ID, Price_Mode, Price_Value, Price_Source_ID, Is_Anchor) VALUES (?,?,?,?,?,?)
              ON DUPLICATE KEY UPDATE Price_Mode=VALUES(Price_Mode), Price_Value=VALUES(Price_Value), Price_Source_ID=VALUES(Price_Source_ID), Is_Anchor=VALUES(Is_Anchor)");
  $ok = $stmt2->execute([$product_id,$sizeId,$mode,$resolvedPrice, $price_id>0 ? $price_id : null, $isAnchor]);

  echo json_encode(['success'=>$ok,'size_code'=>$norm_code,'mode'=>$mode]);
} catch(Throwable $e){
  error_log('add_size.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
