<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
try {
  $con = $db->opencon();
  // Verify legacy table exists
  try { $con->query("SELECT 1 FROM product_sizes LIMIT 1"); } catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>'No legacy table to migrate']); return; }

  // Ensure new tables
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
    INDEX (Product_ID), INDEX (Size_ID),
    CONSTRAINT fk_psp_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
    CONSTRAINT fk_psp_size FOREIGN KEY (Size_ID) REFERENCES sizes(Size_ID) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Fetch distinct codes
  $codesStmt = $con->query("SELECT DISTINCT Size_Code FROM product_sizes ORDER BY Size_Code");
  $codes = $codesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
  $insertedSizes = 0; $mapped = 0;
  $sortOrder = 0;
  foreach($codes as $code){
    $norm = strtolower($code);
    $display = ucwords(str_replace(['_','-'],' ', $code));
    $stmt = $con->prepare("INSERT IGNORE INTO sizes (Size_Code, Display_Name, Sort_Order) VALUES (?,?,?)");
    if($stmt->execute([$norm,$display,$sortOrder++])){ if($stmt->rowCount()>0) $insertedSizes++; }
  }
  // Build map of size_code -> Size_ID
  $sizeIdMap = [];
  $allSizes = $con->query("SELECT Size_ID, Size_Code FROM sizes")->fetchAll(PDO::FETCH_ASSOC);
  foreach($allSizes as $s){ $sizeIdMap[strtolower($s['Size_Code'])] = (int)$s['Size_ID']; }

  // Migrate each legacy row
  $legacyStmt = $con->query("SELECT Product_ID, Size_Code, Price_Amount, Is_Absolute FROM product_sizes");
  while($row = $legacyStmt->fetch(PDO::FETCH_ASSOC)){
    $codeKey = strtolower($row['Size_Code']);
    if(!isset($sizeIdMap[$codeKey])) continue; // skip if not mapped (unlikely)
    $sizeId = $sizeIdMap[$codeKey];
    $mode = ((int)$row['Is_Absolute'] === 1) ? 'ABS' : 'DELTA';
    $val  = (float)$row['Price_Amount'];
    $ins = $con->prepare("INSERT INTO product_size_price (Product_ID, Size_ID, Price_Mode, Price_Value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE Price_Mode=VALUES(Price_Mode), Price_Value=VALUES(Price_Value)");
    if($ins->execute([(int)$row['Product_ID'],$sizeId,$mode,$val])) $mapped++;
  }

  echo json_encode(['success'=>true,'inserted_sizes'=>$insertedSizes,'migrated_mappings'=>$mapped,'message'=>'Migration completed']);
} catch(Throwable $e){
  error_log('migrate_legacy_sizes.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}