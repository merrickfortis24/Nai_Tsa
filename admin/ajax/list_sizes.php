<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
try {
  $con = $db->opencon();
  // Create new tables if absent
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
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prod_size (Product_ID, Size_ID),
    INDEX (Product_ID),
    INDEX (Size_ID),
    INDEX (Price_Source_ID),
    CONSTRAINT fk_psp_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
    CONSTRAINT fk_psp_size FOREIGN KEY (Size_ID) REFERENCES sizes(Size_ID) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // If table existed already, ensure Price_Source_ID column (idempotent)
  try {
    $colChk = $con->query("SHOW COLUMNS FROM product_size_price LIKE 'Price_Source_ID'");
    if($colChk && $colChk->rowCount() === 0){
      $con->exec("ALTER TABLE product_size_price ADD COLUMN Price_Source_ID INT NULL AFTER Price_Value, ADD INDEX (Price_Source_ID)");
    }
  } catch (Throwable $cEx) { /* ignore */ }

  // Attempt to load from new mapping
  $rows = [];
  try {
  $categoryFilter = null;
  if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] !== '') {
    $categoryFilter = (int)$_REQUEST['category_id'];
  }
  if($categoryFilter){
    $stmt = $con->prepare("SELECT psp.*, s.Size_Code, s.Display_Name, s.Sort_Order, p.Product_Name FROM product_size_price psp
              JOIN sizes s ON psp.Size_ID = s.Size_ID
              JOIN product p ON psp.Product_ID = p.Product_ID
              WHERE p.Category_ID = ?
              ORDER BY p.Product_Name, s.Sort_Order, s.Display_Name");
    $stmt->execute([$categoryFilter]);
  } else {
    $stmt = $con->query("SELECT psp.*, s.Size_Code, s.Display_Name, s.Sort_Order, p.Product_Name FROM product_size_price psp
              JOIN sizes s ON psp.Size_ID = s.Size_ID
              JOIN product p ON psp.Product_ID = p.Product_ID
              ORDER BY p.Product_Name, s.Sort_Order, s.Display_Name");
  }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $inner) { /* ignore */ }

  // Fallback: legacy table product_sizes (if still present, include them so admin can migrate)
  if(empty($rows)) {
    try {
      $stmt2 = $con->query("SELECT ps.ID, ps.Product_ID, ps.Size_Code, ps.Price_Amount, ps.Is_Absolute, ps.Updated_At, p.Product_Name
                             FROM product_sizes ps JOIN product p ON ps.Product_ID=p.Product_ID
                             ORDER BY p.Product_Name, ps.Size_Code");
      $legacy = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
      // Tag legacy rows
      foreach($legacy as &$l){ $l['LEGACY'] = 1; }
      $rows = $legacy;
    } catch (Throwable $legacyE) { /* none */ }
  }

  echo json_encode(['success'=>true,'rows'=>$rows]);
} catch(Throwable $e){
  error_log('list_sizes.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
