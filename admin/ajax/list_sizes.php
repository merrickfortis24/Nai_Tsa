<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
try {
  $con = $db->opencon();
  // Ensure table exists (idempotent)
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
  $stmt = $con->query("SELECT ps.*, p.Product_Name FROM product_sizes ps JOIN product p ON ps.Product_ID=p.Product_ID ORDER BY p.Product_Name, ps.Size_Code");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'rows'=>$rows]);
} catch(Throwable $e){
  error_log('list_sizes.php: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Server error']);
}
