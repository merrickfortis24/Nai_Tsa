<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();
try {
    $con = $db->opencon();
    // Ensure new tables exist (defensive)
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
        CONSTRAINT fk_psp_product2 FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
        CONSTRAINT fk_psp_size2 FOREIGN KEY (Size_ID) REFERENCES sizes(Size_ID) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create / fetch default size 'regular'
    $defaultCode = 'regular';
    $defaultName = 'Regular';
    $getSize = $con->prepare("SELECT Size_ID FROM sizes WHERE Size_Code=? LIMIT 1");
    $getSize->execute([$defaultCode]);
    $sizeId = $getSize->fetchColumn();
    if(!$sizeId){
        $insSize = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name, Sort_Order) VALUES (?,?,0)");
        $insSize->execute([$defaultCode,$defaultName]);
        $sizeId = $con->lastInsertId();
    }

    // Fetch products + their current product_price amounts
    $stmt = $con->query("SELECT p.Product_ID, pp.Price_Amount
                         FROM product p
                         JOIN product_price pp ON p.Price_ID = pp.Price_ID");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $inserted = 0; $skipped = 0; $updated = 0;
    $ins = $con->prepare("INSERT INTO product_size_price (Product_ID, Size_ID, Price_Mode, Price_Value) VALUES (?,?, 'ABS', ?) ");
    $upd = $con->prepare("UPDATE product_size_price SET Price_Mode='ABS', Price_Value=?, Updated_At=NOW() WHERE Product_ID=? AND Size_ID=?");
    $chk = $con->prepare("SELECT Product_Size_Price_ID, Price_Value, Price_Mode FROM product_size_price WHERE Product_ID=? AND Size_ID=? LIMIT 1");

    foreach($rows as $r){
        $pid = (int)$r['Product_ID'];
        $amt = (float)$r['Price_Amount'];
        $chk->execute([$pid,$sizeId]);
        if($existing = $chk->fetch(PDO::FETCH_ASSOC)){
            // If existing differs, update; else skip
            if($existing['Price_Mode'] !== 'ABS' || (float)$existing['Price_Value'] != $amt){
                $upd->execute([$amt,$pid,$sizeId]);
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            $ins->execute([$pid,$sizeId,$amt]);
            $inserted++;
        }
    }

    echo json_encode([
        'success'=>true,
        'message'=>'Migration completed',
        'size_code'=>$defaultCode,
        'summary'=>[ 'inserted'=>$inserted, 'updated'=>$updated, 'unchanged'=>$skipped, 'total_products'=>count($rows) ]
    ]);
} catch(Throwable $e){
    error_log('migrate_product_price_to_sizes: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Migration error']);
}
?>