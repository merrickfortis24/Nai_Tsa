<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
$product_id = isset($_POST['product_id'])? (int)$_POST['product_id'] : 0;
$size_code = isset($_POST['size_code'])? trim($_POST['size_code']) : '';
$price_amount = isset($_POST['price_amount'])? (float)$_POST['price_amount'] : 0.0;
$is_absolute = isset($_POST['is_absolute'])? (int)$_POST['is_absolute'] : 0;
if($product_id<=0 || $size_code===''){ echo json_encode(['success'=>false,'message'=>'Missing product or size']); exit; }
$size_code = strtolower($size_code);
if(!in_array($size_code,['16oz','22oz'])){ echo json_encode(['success'=>false,'message'=>'Invalid size code']); exit; }
try {
  $con = $db->opencon();
  $con->exec("CREATE TABLE IF NOT EXISTS product_sizes (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Size_Code VARCHAR(10) NOT NULL,
    Price_Amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Is_Absolute TINYINT(1) NOT NULL DEFAULT 0,
    Created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_prod_size (Product_ID, Size_Code),
    INDEX (Product_ID),
    CONSTRAINT fk_ps_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  $stmt = $con->prepare("INSERT INTO product_sizes (Product_ID, Size_Code, Price_Amount, Is_Absolute) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE Price_Amount=VALUES(Price_Amount), Is_Absolute=VALUES(Is_Absolute)");
  $ok = $stmt->execute([$product_id,$size_code,$price_amount,$is_absolute]);
  echo json_encode(['success'=>$ok]);
} catch(Throwable $e){
  error_log('add_size.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
