<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}
require_once __DIR__ . '/../classes/database.php';
$db = new database();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
  echo json_encode(['success'=>false,'message'=>'Invalid order id']);
  exit;
}

try {
  $con = $db->opencon();
  $stmt = $con->prepare("SELECT o.*, c.Customer_Name, p.Payment_ID, p.payment_status, p.Payment_Method,
    COALESCE(addr.Street, '') AS Street,
    COALESCE(addr.Barangay, '') AS Barangay,
    COALESCE(addr.City, '') AS City,
  COALESCE(c.Contact_Number, '') AS Contact_Number,
    COALESCE(addr.customer_lat, '') AS customer_lat,
  COALESCE(addr.customer_lng, '') AS customer_lng,
  COALESCE(od.Delivery_Fee, 0.00) AS Delivery_Fee,
  COALESCE(od.Delivery_Distance_Km, 0.00) AS Delivery_Distance_Km,
    FROM orders o
    LEFT JOIN order_address addr ON addr.Order_ID = o.Order_ID
    LEFT JOIN customer c ON c.Customer_ID = o.Customer_ID
  LEFT JOIN payment p ON p.Order_ID = o.Order_ID
  LEFT JOIN order_delivery od ON od.Order_ID = o.Order_ID
    WHERE o.Order_ID = ? LIMIT 1");
  $stmt->execute([$orderId]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$r) {
    echo json_encode(['success'=>false,'message'=>'Order not found']);
    exit;
  }

  // fetch items with addons
  $items = [];
  try { $items = $db->fetchOrderItemsWithAddons($orderId); } catch(Throwable $e) { $items = []; }

  // Helper esc
  function h_local($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

  // Build modal HTML (same structure as server-rendered modals)
  ob_start();
  ?>
  <div class="modal fade" id="itemsModal<?=h_local($r['Order_ID'])?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Order #<?=h_local($r['Order_ID'])?> Items</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php
            $street = $r['Street'] ?? '';
            $city   = $r['City'] ?? '';
            $contactNum = $r['Contact_Number'] ?? '';
            $lat = $r['customer_lat'] ?? $r['Customer_Lat'] ?? null;
            $lng = $r['customer_lng'] ?? $r['Customer_Lng'] ?? null;
            $hasCoords = is_numeric($lat) && is_numeric($lng);
            $rawType = trim((string)($r['order_type'] ?? ''));
            if ($rawType !== '') {
              $isDelivery = stripos($rawType, 'delivery') !== false;
            } else {
              $isDelivery = $hasCoords || !empty($street) || !empty($city);
            }
          ?>
          <div class="mb-3">
            <h6 class="fw-semibold mb-1">Order Location / Contact</h6>
            <?php $orderType = $rawType !== '' ? ucfirst(strtolower($rawType)) : ($isDelivery ? 'Delivery' : 'Pickup'); ?>
            <div class="small mb-1"><strong>Order Type:</strong> <?= h_local($orderType); ?></div>
            <?php if ($isDelivery): ?>
              <div class="small"><strong>Contact #:</strong> <?= h_local($contactNum ?: '—'); ?></div>
              <?php if ($hasCoords): ?>
                <?php $addressParts = array_filter([$street, $city]); $fullAddress = $addressParts ? implode(', ', $addressParts) : '—'; ?>
                <div class="small"><strong>Address:</strong> <?= h_local($fullAddress) ?></div>
                <div class="ratio ratio-16x9 mt-2" style="border:1px solid #ddd; border-radius:6px; overflow:hidden;">
                  <iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=<?=urlencode($lat . ',' . $lng)?>&z=15&output=embed"></iframe>
                </div>
                <div class="mt-2 d-flex flex-wrap align-items-center gap-3 small">
                  <a href="https://www.google.com/maps?q=<?=urlencode($lat . ',' . $lng)?>" target="_blank" rel="noopener" class="text-decoration-none">Open in Google Maps</a>
                  <button type="button" class="btn btn-sm btn-outline-primary share-delivery-btn" data-order="<?=h_local($r['Order_ID'])?>" data-customer="<?=h_local($r['Customer_Name'] ?? 'Customer')?>" data-contact="<?=h_local($contactNum ?: '')?>" data-lat="<?=h_local($lat)?>" data-lng="<?=h_local($lng)?>" data-address="<?=h_local($fullAddress)?>">
                    <i class="bi bi-share"></i> Share Info
                  </button>
                </div>
              <?php else: ?>
                <div class="small text-muted">No stored coordinates for this delivery.</div>
              <?php endif; ?>
            <?php else: ?>
              <div class="small text-muted">Pickup order – no delivery location needed.</div>
            <?php endif; ?>
          </div>
          <hr class="my-3">
          <h6 class="fw-semibold mb-2">Items</h6>
          <?php if(!$items): ?>
            <p class="text-muted mb-0">No items.</p>
          <?php else: $subtotal=0; $addonsSubtotal=0; ?>
            <ul class="list-group mb-3">
              <?php foreach ($items as $it):
                $qty   = (int)($it['Quantity'] ?? 0);
                $price = (float)($it['Price'] ?? 0);
                $line  = $qty * $price;
                $addonTotal = 0;
                if (!empty($it['addons']) && is_array($it['addons'])){
                  foreach ($it['addons'] as $ad){ $addonQty=(int)($ad['Quantity']??1); $addonPrice=(float)($ad['Addon_Price']??0); $addonTotal += $addonPrice * $addonQty * max(1,$qty); }
                }
                $lineWithAddons = $line + $addonTotal;
                $subtotal += $line; $addonsSubtotal += $addonTotal;
                $img = $it['Image'] ?? $it['Product_Image'] ?? $it['product_image'] ?? $it['Image_URL'] ?? $it['image'] ?? '';
                $imgFile = '';
                if ($img){ $trim=trim($img); if (preg_match('~^https?://~i',$trim)) $imgFile=$trim; else { if (strpos($trim,'/')===false && strpos($trim,'\\')===false) $imgFile='uploads/products/'.$trim; else { if (stripos($trim,'uploads/products/')===0) $imgFile=$trim; else $imgFile='uploads/products/'.basename($trim); } } }
              ?>
              <li class="list-group-item d-flex flex-column">
                <div class="d-flex w-100">
                  <div class="me-3" style="width:56px;">
                    <?php if($imgFile): ?>
                      <img src="<?=h_local($imgFile)?>" alt="Item" class="img-fluid rounded" style="height:56px; width:56px; object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling?.classList.remove('d-none');">
                      <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted d-none" style="height:56px; width:56px; font-size:.65rem;">No Img</div>
                    <?php else: ?>
                      <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="height:56px; width:56px; font-size:.65rem;">No Img</div>
                    <?php endif; ?>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                      <span class="fw-semibold me-2" style="max-width:60%;"><?=h_local($it['Product_Name'] ?? 'Item')?></span>
                      <div class="text-end small">
                        <div><span class="text-muted">Item:</span> ₱<?=number_format($line,2)?></div>
                        <?php if($addonTotal>0): ?><div><span class="text-muted">Add-ons:</span> ₱<?=number_format($addonTotal,2)?></div><?php endif; ?>
                        <div class="fw-semibold border-top mt-1 pt-1">₱<?=number_format($lineWithAddons,2)?></div>
                      </div>
                    </div>
                    <?php if(isset($it['Instruction']) && trim((string)$it['Instruction']) !== ''): ?><div class="small fst-italic mt-1"><span class="text-muted">Instruction:</span> <?=h_local($it['Instruction'])?></div><?php endif; ?>
                    <small class="text-muted d-block">Qty: <?=$qty?> @ ₱<?=number_format($price,2)?></small>
                    <?php if($addonTotal>0): ?><div class="mt-1 small text-muted"><?php foreach($it['addons'] as $ad): $adLine=(float)$ad['Addon_Price'] * (int)$ad['Quantity'] * max(1,$qty); ?><div>• <?=h_local($ad['Addon_Name'])?> x <?= (int)$ad['Quantity'] ?> = ₱<?=number_format($adLine,2)?></div><?php endforeach; ?></div><?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php $deliveryFee = (float)($r['Delivery_Fee'] ?? 0); $grandTotal = $subtotal + $addonsSubtotal + $deliveryFee; ?>
            <div class="border-top pt-2 small">
              <div class="d-flex justify-content-between"><span>Products Subtotal</span><strong>₱<?=number_format($subtotal,2)?></strong></div>
              <div class="d-flex justify-content-between"><span>Add-ons Total</span><strong>₱<?=number_format($addonsSubtotal,2)?></strong></div>
              <div class="d-flex justify-content-between"><span>Shipping Fee</span><strong>₱<?=number_format($deliveryFee,2)?></strong></div>
              <div class="d-flex justify-content-between mt-1 border-top pt-1 fw-semibold"><span>Total</span><span>₱<?=number_format($grandTotal,2)?></span></div>
            </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>
  <?php
  $html = ob_get_clean();
  echo json_encode(['success'=>true,'html'=>$html,'order_id'=>$orderId]);
} catch (Throwable $e) {
  error_log('ajax/get_order_details.php exception: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}

?>
