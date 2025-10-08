<?php
// Returns size options for a product with computed final unit price (anchor-based model).
// If size variants exist, product's own base price is ignored and anchor price becomes the base.
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json');
require_once __DIR__.'/../classes/database.php';

try {
  $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  if ($productId <= 0) { echo json_encode(['success'=>false,'message'=>'Missing product id']); exit; }
  $db = new database();
  $con = $db->opencon();

  // Fetch legacy product base (used only if no size data OR no anchor found)
  $stmtBase = $con->prepare("SELECT pp.Price_Amount FROM product p JOIN product_price pp ON p.Price_ID=pp.Price_ID WHERE p.Product_ID=? LIMIT 1");
  $stmtBase->execute([$productId]);
  $legacyBase = (float)$stmtBase->fetchColumn();

  $sizes = [];
  $anchorPrice = null;
  $anchorCode = null;
  $hasModern = false;

  // Attempt new anchor-based table
  try {
    $q = $con->prepare("SELECT psp.Product_Size_Price_ID, psp.Price_Mode, psp.Price_Value, psp.Is_Anchor,
                               s.Size_Code, s.Display_Name
                        FROM product_size_price psp
                        JOIN sizes s ON psp.Size_ID = s.Size_ID
                        WHERE psp.Product_ID=?
                        ORDER BY psp.Is_Anchor DESC, s.Sort_Order ASC, s.Display_Name ASC");
    $q->execute([$productId]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
      $hasModern = true;
      foreach ($rows as $r) {
        $isAnchor = (int)($r['Is_Anchor'] ?? 0) === 1;
        if ($isAnchor && $anchorPrice === null) {
          // Anchor must be ABS; fallback safe guard
            $anchorPrice = ($r['Price_Mode'] === 'ABS') ? (float)$r['Price_Value'] : (float)$r['Price_Value'];
            $anchorCode = $r['Size_Code'];
        }
        $sizes[] = $r; // store raw for second pass
      }
    }
  } catch (Throwable $e) { /* ignore - table might not exist */ }

  $finalSizes = [];
  if ($hasModern && $sizes) {
    if ($anchorPrice === null) { // Migration fallback: choose first ABS as anchor else first row
      foreach ($sizes as $r) { if ($r['Price_Mode']==='ABS') { $anchorPrice=(float)$r['Price_Value']; $anchorCode=$r['Size_Code']; break; } }
      if ($anchorPrice===null) { $anchorPrice = (float)$sizes[0]['Price_Value']; $anchorCode=$sizes[0]['Size_Code']; }
    }
    foreach ($sizes as $r) {
      $isAnchor = (int)($r['Is_Anchor'] ?? 0) === 1 || $r['Size_Code'] === $anchorCode;
      $mode = $r['Price_Mode'];
      $val = (float)$r['Price_Value'];
      if ($isAnchor) {
        $final = $anchorPrice; // anchor defined
      } else {
        if ($mode === 'DELTA') {
          $final = $anchorPrice + $val;
        } elseif ($mode === 'ABS') {
          $final = $val; // uncommon, but supported
        } else { // unknown -> treat as delta
          $final = $anchorPrice + $val;
        }
      }
      $finalSizes[] = [
        'code' => $r['Size_Code'],
        'label'=> $r['Display_Name'] ?: $r['Size_Code'],
        'mode' => $mode,
        'value'=> $val,
        'is_anchor' => $isAnchor ? 1 : 0,
        'final_price' => $final
      ];
    }
  } else {
    // Legacy fallback tables (old product_sizes) OR no sizes -> behave like single default size
    try {
      $legacy = $con->prepare("SELECT Size_Code, Price_Amount, Is_Absolute FROM product_sizes WHERE Product_ID=? ORDER BY Size_Code");
      $legacy->execute([$productId]);
      $lrows = $legacy->fetchAll(PDO::FETCH_ASSOC);
      if ($lrows) {
        // Pick first absolute as anchor else first row
        foreach ($lrows as $r) { if ((int)$r['Is_Absolute']===1) { $anchorPrice=(float)$r['Price_Amount']; $anchorCode=$r['Size_Code']; break; } }
        if ($anchorPrice===null){ $anchorPrice = (float)$lrows[0]['Price_Amount']; $anchorCode=$lrows[0]['Size_Code']; }
        foreach ($lrows as $r) {
          $isAbs = (int)$r['Is_Absolute']===1;
          $val = (float)$r['Price_Amount'];
          $isAnchor = $isAbs && $r['Size_Code']===$anchorCode;
          $final = $isAbs ? $val : ($anchorPrice + $val); // legacy deltas relative to anchor price
          $finalSizes[] = [
            'code'=>$r['Size_Code'],
            'label'=>$r['Size_Code'],
            'mode'=>$isAbs?'ABS':'DELTA',
            'value'=>$val,
            'is_anchor'=>$isAnchor?1:0,
            'final_price'=>$final
          ];
        }
      }
    } catch (Throwable $e) { /* ignore */ }
  }

  if (!$finalSizes) {
    // Single implicit size (no variants) using product legacy base
    $finalSizes[] = [
      'code' => 'default',
      'label'=> 'Regular',
      'mode' => 'ABS',
      'value'=> $legacyBase,
      'is_anchor' => 1,
      'final_price' => $legacyBase
    ];
    $anchorPrice = $legacyBase;
    $anchorCode = 'default';
  }

  echo json_encode([
    'success' => true,
    'base_price' => $anchorPrice ?? $legacyBase, // for UI baseline (anchor)
    'anchor_code' => $anchorCode,
    'sizes' => $finalSizes
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}