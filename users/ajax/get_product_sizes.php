<?php
// Returns size options for a product with computed final unit price.
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json');
require_once __DIR__.'/../classes/database.php';

try {
  $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  if($productId<=0){ echo json_encode(['success'=>false,'message'=>'Missing product id']); exit; }
  $db = new database();
  $con = $db->opencon();

  // Base price
  $stmtBase = $con->prepare("SELECT pp.Price_Amount FROM product p JOIN product_price pp ON p.Price_ID=pp.Price_ID WHERE p.Product_ID=? LIMIT 1");
  $stmtBase->execute([$productId]);
  $base = (float)$stmtBase->fetchColumn();

  $sizes = [];
  $hasNew = false;
  try {
    $q = $con->prepare("SELECT psp.Product_Size_Price_ID, s.Size_Code, s.Display_Name, psp.Price_Mode, psp.Price_Value
                        FROM product_size_price psp
                        JOIN sizes s ON psp.Size_ID = s.Size_ID
                        WHERE psp.Product_ID=?
                        ORDER BY s.Sort_Order, s.Display_Name");
    $q->execute([$productId]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
      $final = ($r['Price_Mode']==='ABS') ? (float)$r['Price_Value'] : ($base + (float)$r['Price_Value']);
      $sizes[] = [
        'code' => $r['Size_Code'],
        'label'=> $r['Display_Name'],
        'mode' => $r['Price_Mode'],
        'value'=> (float)$r['Price_Value'],
        'final_price' => $final
      ];
    }
    if($sizes) $hasNew = true;
  } catch (Throwable $e) { /* ignore */ }

  if(!$hasNew){
    // Legacy fallback product_sizes
    try {
      $legacy = $con->prepare("SELECT Size_Code, Price_Amount, Is_Absolute FROM product_sizes WHERE Product_ID=? ORDER BY Size_Code");
      $legacy->execute([$productId]);
      $lr = $legacy->fetchAll(PDO::FETCH_ASSOC);
      foreach($lr as $r){
        $amt = (float)$r['Price_Amount'];
        $isAbs = (int)$r['Is_Absolute'] === 1;
        $final = $isAbs ? $amt : ($base + $amt);
        $sizes[] = [
          'code'=>$r['Size_Code'],
          'label'=>$r['Size_Code'],
          'mode'=>$isAbs?'ABS':'DELTA',
          'value'=>$amt,
          'final_price'=>$final
        ];
      }
    } catch (Throwable $er) { /* none */ }
  }

  // If still empty, provide a single default size using base price
  if(!$sizes){
    $sizes[] = [ 'code'=>'default', 'label'=>'Regular', 'mode'=>'ABS', 'value'=>$base, 'final_price'=>$base ];
  }

  echo json_encode(['success'=>true,'base_price'=>$base,'sizes'=>$sizes]);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}