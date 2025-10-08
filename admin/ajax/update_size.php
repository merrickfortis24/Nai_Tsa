<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require_once __DIR__.'/../classes/database.php';
$db = new database();

$map_id       = isset($_POST['mapping_id'])? (int)$_POST['mapping_id'] : 0; // Product_Size_Price_ID
$product_id   = isset($_POST['product_id'])? (int)$_POST['product_id'] : 0; // Optional safety check
$size_code    = isset($_POST['size_code'])? trim($_POST['size_code']) : '';
$display_name = isset($_POST['display_name'])? trim($_POST['display_name']) : '';
$price_amount = isset($_POST['price_amount'])? (float)$_POST['price_amount'] : null; // null means don't change
$price_id = isset($_POST['price_id'])? (int)$_POST['price_id'] : 0; // new: prefer price_id lookup
$price_mode   = isset($_POST['price_mode'])? strtoupper(trim($_POST['price_mode'])) : '';
$sort_order   = isset($_POST['sort_order'])? (int)$_POST['sort_order'] : null;

if($map_id<=0){ echo json_encode(['success'=>false,'message'=>'Missing mapping id']); exit; }
if($size_code===''){ echo json_encode(['success'=>false,'message'=>'Missing size code']); exit; }
$norm_code = strtolower(preg_replace('/\s+/','_', $size_code));
if(strlen($norm_code) > 32){ echo json_encode(['success'=>false,'message'=>'Size code too long']); exit; }
if($display_name==='') $display_name = ucwords(str_replace(['_','-'],' ', $size_code));
if($price_mode !== '' && !in_array($price_mode,['ABS','DELTA'])){ echo json_encode(['success'=>false,'message'=>'Invalid price_mode']); exit; }

try {
	$con = $db->opencon();
	// Ensure core tables
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
		UNIQUE KEY uq_prod_size (Product_ID, Size_ID)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
	");

	// Fetch existing mapping
	$cur = $con->prepare("SELECT Product_ID, Size_ID FROM product_size_price WHERE Product_Size_Price_ID=? LIMIT 1");
	$cur->execute([$map_id]);
	$existing = $cur->fetch(PDO::FETCH_ASSOC);
	if(!$existing){ echo json_encode(['success'=>false,'message'=>'Mapping not found']); exit; }
	if($product_id && $product_id != (int)$existing['Product_ID']){
		echo json_encode(['success'=>false,'message'=>'Product mismatch']); exit;
	}

	// Upsert size master (possibly renaming code)
	if($sort_order !== null){
		$probe = $con->prepare("SELECT 1 FROM sizes WHERE Sort_Order=? AND Size_Code<>? LIMIT 1");
		while(true){
			$probe->execute([$sort_order,$norm_code]);
			if(!$probe->fetch()) break; // free slot
			$sort_order++;
		}
		$upSize = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name, Sort_Order) VALUES (?,?,?) ON DUPLICATE KEY UPDATE Display_Name=VALUES(Display_Name), Sort_Order=VALUES(Sort_Order)");
		$upSize->execute([$norm_code,$display_name,$sort_order]);
	} else {
		$upSize = $con->prepare("INSERT INTO sizes (Size_Code, Display_Name) VALUES (?,?) ON DUPLICATE KEY UPDATE Display_Name=VALUES(Display_Name)");
		$upSize->execute([$norm_code,$display_name]);
	}

	// Resolve Size_ID for (possibly new) code
	$sidStmt = $con->prepare("SELECT Size_ID FROM sizes WHERE Size_Code=? LIMIT 1");
	$sidStmt->execute([$norm_code]);
	$sizeId = (int)$sidStmt->fetchColumn();
	if($sizeId<=0){ echo json_encode(['success'=>false,'message'=>'Failed to resolve size id']); exit; }

	// Update mapping: if size code changed we need to ensure uniqueness for (Product_ID, Size_ID)
	$productIdActual = (int)$existing['Product_ID'];
	// If the size changed, check conflict
	$conflict = $con->prepare("SELECT Product_Size_Price_ID FROM product_size_price WHERE Product_ID=? AND Size_ID=? AND Product_Size_Price_ID<>? LIMIT 1");
	$conflict->execute([$productIdActual,$sizeId,$map_id]);
	if($conflict->fetch()){
		echo json_encode(['success'=>false,'message'=>'Another mapping already uses this size for the product']); exit;
	}

	// Build update fragments
	$fields = [];$params = [];
		if($price_mode !== ''){ $fields[] = 'Price_Mode=?'; $params[] = $price_mode; }
		// Resolve price_amount from price_id if present
		if($price_id > 0){
			$pstmt = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=? LIMIT 1");
			$pstmt->execute([$price_id]);
			$pv = $pstmt->fetchColumn();
			if($pv !== false){ $price_amount = (float)$pv; }
		}
		if($price_amount !== null){ $fields[] = 'Price_Value=?'; $params[] = $price_amount; }
	// If size id changed from original, update it
	if($sizeId != (int)$existing['Size_ID']){ $fields[] = 'Size_ID=?'; $params[] = $sizeId; }

	if(empty($fields)){
		echo json_encode(['success'=>true,'message'=>'No change']); exit;
	}
	$sql = 'UPDATE product_size_price SET '.implode(',', $fields).', Updated_At=NOW() WHERE Product_Size_Price_ID=?';
	$params[] = $map_id;
	$upd = $con->prepare($sql);
	$ok = $upd->execute($params);
	echo json_encode(['success'=>$ok]);
} catch (Throwable $e){
	error_log('update_size.php: '.$e->getMessage());
	echo json_encode(['success'=>false,'message'=>'Server error']);
}
