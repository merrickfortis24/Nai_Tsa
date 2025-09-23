<?php
// Unified Orders & Payments page (follows pattern of original orders.php + payments.php backend structure)
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'classes/database.php';
$db = new database();

// ...existing code...

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8'); }

// --- Handle inline updates (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['order_id'], $_POST['order_status'])) {
    try {
      $oid = (int)$_POST['order_id'];
      $target = trim((string)($_POST['order_status'] ?? ''));
  // Use existing method and insert sales if the update succeeded
  $ok = false;
  try { $ok = $db->updateOrderStatus($oid, $target); } catch(Throwable $e) { /* ignore */ }
  if ($ok) {
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $db->insertSalesIfDeliveredAndPaid($oid, $adminId);
  } else {
    // Fallback: if the primary method refused the update, run a direct UPDATE to ensure UI action persists.
    try {
      $con = $db->opencon();
      $stmt = $con->prepare("UPDATE orders SET order_status = ? WHERE Order_ID = ?");
      $stmtOk = $stmt->execute([$target, $oid]);
      if ($stmtOk && $stmt->rowCount() > 0) {
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $db->insertSalesIfDeliveredAndPaid($oid, $adminId);
      }
    } catch (Throwable $e) { /* ignore fallback failure */ }
  }
    } catch (Throwable $e) { /* ignore */ }
  }
  if (isset($_POST['payment_id'], $_POST['payment_status'])) {
    try {
      $pid = (int)$_POST['payment_id'];
      if ($db->updatePaymentStatus($pid, $_POST['payment_status'])) {
        $oid = $db->getOrderIdByPaymentId($pid);
        if ($oid) {
          $adminId = (int)($_SESSION['admin_id'] ?? 0);
          $db->insertSalesIfDeliveredAndPaid($oid, $adminId);
        }
      }
    } catch (Throwable $e) { /* ignore */ }
  }
  $qs = $_GET; unset($qs['page']);
  header('Location: orders_payments.php' . ($qs ? ('?' . http_build_query($qs)) : ''));
  exit;
}

// After redirect: display any debug pushed by previous POST
// (This will be rendered in the HTML below if present)

// --- Filters & pagination (mirrors orders.php & payments.php variable style) ---
$search  = $_GET['search']  ?? '';
$status  = $_GET['status']  ?? '';     // order status
$payment = $_GET['payment'] ?? '';     // payment status (Paid / Unpaid)
$method  = $_GET['method']  ?? '';     // payment method
$from    = $_GET['from']    ?? '';
$to      = $_GET['to']      ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Fetch combined data
try {
  $rows  = $db->fetchOrdersPaymentsCombined($search, $status, $payment, $method, $from, $to, $perPage, $offset);
  $total = $db->countOrdersPaymentsCombined($search, $status, $payment, $method, $from, $to);
} catch (Throwable $e) {
  // Log details for debugging — visible only in server logs for admins to inspect
  error_log('orders_payments.php: fetchOrdersPaymentsCombined exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  $rows = []; $total = 0;
}
$totalPages = max(1, (int)ceil($total / $perPage));
$currentPage = $page; // for pagination snippet compatibility

// Stats reused from individual pages if available
$unpaidPayments = 0; $pendingProcessingCount = 0;
try { $unpaidPayments = $db->countUnpaidPayments(); } catch (Throwable $e) {}
try { $pendingProcessingCount = $db->countPendingOrProcessingOrders(); } catch (Throwable $e) {}

// Collect payment methods present in current page (simple)
$methods = [];
foreach ($rows as $r) { if (!empty($r['Payment_Method'])) $methods[$r['Payment_Method']] = true; }
ksort($methods);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders & Payments</title>
<!-- Explicit favicon handler to avoid automatic /favicon.ico 404s -->
<link rel="icon" href="/favicon.php" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.badge-status { font-size:.75rem; }
.filter-row .short-select { min-width: 120px; }
@media (max-width: 768px){
  .filter-row .short-select { min-width: 100%; }
}
</style>
</head>
<body class="dashboard-page">
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
      <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-md-10 col-lg-10 main-content">
      <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
  <h4 class="fw-bold mb-0">Orders & Payments</h4>
        <button class="btn btn-outline-primary d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse"><i class="bi bi-list"></i></button>
      </div>
      <div class="card shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-stack me-1"></i> Combined Listing</div>
        <div class="card-body">
          <!-- debug messages removed -->
          <?php if (isset($_GET['debug']) && (string)$_GET['debug'] === '1'): ?>
            <div class="mb-3">
              <h6 class="small text-muted">Debug: raw query result</h6>
              <pre style="max-height:300px; overflow:auto; background:#f8f9fa; padding:10px; border:1px solid #e9ecef;">
<?php echo htmlspecialchars(json_encode(['total'=>$total,'rows'=>$rows], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
              </pre>
            </div>
            <?php
              // Additional diagnostics: raw orders table count and a small sample to ensure the base table has data
              try {
                $con = $db->opencon();
                $rawCount = (int)$con->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                $sample = $con->query("SELECT Order_ID, Order_Date, order_status, Order_Amount FROM orders ORDER BY Order_ID DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                echo '<div class="mb-3">';
                echo '<h6 class="small text-muted">Diagnostic: base orders table</h6>';
                echo '<pre style="background:#fffbe6;padding:10px;border:1px solid #f0e6b8;">';
                echo htmlspecialchars(json_encode(['orders_table_count' => $rawCount, 'sample' => $sample], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                echo '</pre></div>';
              } catch (Throwable $e) {
                echo '<div class="alert alert-warning small">Diagnostic query failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
              }
            ?>
            <?php
              // Reconstruct the combined query with current filters and run it to see why count==0
              try {
                $where = [];
                $params = [];
                $s = trim($search);
                if ($s !== '') {
                  if (ctype_digit($s)) { $where[] = '(o.Order_ID = :oid OR c.Customer_Name LIKE :sLike)'; $params[':oid'] = (int)$s; $params[':sLike'] = '%'.$s.'%'; }
                  else { $where[] = '(c.Customer_Name LIKE :sLike)'; $params[':sLike'] = '%'.$s.'%'; }
                }
                if ($status !== '') { $where[] = '(o.order_status = :oStatus)'; $params[':oStatus'] = $status; }
                if ($payment !== '') { $where[] = '(COALESCE(p.payment_status, "Unpaid") = :pStatus)'; $params[':pStatus'] = $payment; }
                if ($method !== '') { $where[] = '(p.Payment_Method = :pMethod)'; $params[':pMethod'] = $method; }
                if ($from !== '') { $where[] = '(o.Order_Date >= :fromDate)'; $params[':fromDate'] = $from . ' 00:00:00'; }
                if ($to !== '') { $where[] = '(o.Order_Date <= :toDate)'; $params[':toDate'] = $to . ' 23:59:59'; }
                $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
                $debugSql = "SELECT COUNT(*) FROM orders o\n  LEFT JOIN customer c ON o.Customer_ID = c.Customer_ID\n  LEFT JOIN payment p ON o.Order_ID = p.Order_ID\n  $whereSql";
                $stmtD = $con->prepare($debugSql);
                foreach ($params as $k=>$v) { $stmtD->bindValue($k,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
                $stmtD->execute();
                $debugCount = (int)$stmtD->fetchColumn();
                echo '<div class="mb-3">';
                echo '<h6 class="small text-muted">Reconstructed combined COUNT query</h6>';
                echo '<pre style="background:#eef6ff;padding:10px;border:1px solid #cfe3ff;">';
                echo htmlspecialchars(json_encode(['sql'=>$debugSql,'params'=>$params,'count'=>$debugCount], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                echo '</pre></div>';
              } catch (Throwable $e) {
                echo '<div class="alert alert-danger small">Combined query diagnostic failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
              }
            ?>
          <?php endif; ?>
          <form method="get" class="row g-2 mb-3 align-items-end filter-row">
            <div class="col-12 col-md flex-grow-1">
              <input type="text" name="search" value="<?=h($search)?>" class="form-control" placeholder="Search by Order ID or Customer" />
            </div>
            <div class="col-auto">
              <select name="status" class="form-select form-select-sm short-select" title="Order Status">
                <option value="">All Status</option>
                <?php foreach (["Pending","Preparing","Ready to deliver","On the way","Delivered","Ready to pick up","Received","Cancelled"] as $s): ?>
                  <option value="<?=h($s)?>" <?=$status===$s?'selected':''?>><?=h($s)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <select name="payment" class="form-select form-select-sm short-select" title="Payment Status">
                <option value="">All Pay</option>
                <?php foreach (["Paid","Unpaid"] as $s): ?>
                  <option value="<?=h($s)?>" <?=$payment===$s?'selected':''?>><?=h($s)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <select name="method" class="form-select form-select-sm short-select" title="Payment Method">
                <option value="">Method</option>
                <?php foreach ($methods as $m=>$_): ?>
                  <option value="<?=h($m)?>" <?=$method===$m?'selected':''?>><?=h($m)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <input type="date" id="date_from" name="from" value="<?=h($from)?>" class="form-control form-control-sm short-select" placeholder="From" aria-label="From date" />
            </div>
            <div class="col-auto">
              <input type="date" id="date_to" name="to" value="<?=h($to)?>" class="form-control form-control-sm short-select" placeholder="To" aria-label="To date" />
            </div>
            <div class="col-auto d-grid">
              <button class="btn btn-primary btn-sm" title="Apply filters"><i class="bi bi-search"></i></button>
            </div>
          </form>

          <!-- Stats -->
          <div class="row g-3 mb-3 small">
            <div class="col-6 col-md-3">
              <div class="p-2 border rounded bg-light text-center">
                <div class="text-muted">Total Records</div>
                <div class="fw-semibold" id="statTotal"><?=number_format($total)?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 border rounded bg-light text-center">
                <div class="text-muted">Unpaid Payments</div>
                <div class="fw-semibold text-danger" id="statUnpaid"><?=number_format($unpaidPayments)?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 border rounded bg-light text-center">
                <div class="text-muted">Pending / Processing Orders</div>
                <div class="fw-semibold text-warning" id="statPendingProc"><?=number_format($pendingProcessingCount)?></div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Order Status</th>
                  <th>Payment</th>
                  <th>Method</th>
                  <th>Items</th>
                </tr>
              </thead>
              <tbody>
                <?php if(!$rows): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">No records found.</td></tr>
                <?php endif; ?>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?=h($r['Order_ID'])?></td>
                    <td><?=h($r['Customer_Name'] ?? 'Unknown')?></td>
                    <td><?=h(date('F j,Y g:i A', strtotime($r['Order_Date'])))?></td>
                    <td>₱<?=number_format($r['Order_Amount'],2)?></td>
                    <td>
                        <form method="post" class="mb-0">
                          <input type="hidden" name="order_id" value="<?= (int)$r['Order_ID'] ?>">
                          <?php
                            // Decide order type: prefer explicit order_type field if present, otherwise infer from address
                            $rawType = trim((string)($r['order_type'] ?? ''));
                            if ($rawType !== '') {
                              // PHP check for 'delivery' string in order_type
                              $isDelivery = stripos($rawType, 'delivery') !== false;
                            } else {
                              // Delivery if address fields present (contact number alone not decisive)
                              $isDelivery = !empty($r['Street']) || !empty($r['City']);
                            }
                            $statusOptions = $isDelivery
                              ? ["Pending","Preparing","Ready to deliver","On the way","Delivered","Cancelled"]
                              : ["Pending","Preparing","Ready to pick up","Received","Cancelled"];
                          ?>
                          <select name="order_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($statusOptions as $st): ?>
                              <option value="<?=h($st)?>" <?=$r['order_status']===$st?'selected':''?>><?=$st?></option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                    </td>
                    <td>
                      <?php if (!empty($r['Payment_ID'])): ?>
                        <form method="post" class="mb-0">
                          <input type="hidden" name="payment_id" value="<?= (int)$r['Payment_ID'] ?>">
                          <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (["Paid","Unpaid"] as $ps): ?>
                              <option value="<?=$ps?>" <?=(($r['payment_status']??'Unpaid')===$ps)?'selected':''?>><?=$ps?></option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Unpaid</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $r['Payment_Method']? h($r['Payment_Method']) : '<span class="text-muted">-</span>' ?></td>
                    <td>
                      <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#itemsModal<?=h($r['Order_ID'])?>"><i class="bi bi-eye"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php if ($totalPages > 1): ?>
            <?php
              // Build base query string without page for cleaner links
              $baseParams = $_GET; unset($baseParams['page']);
              $baseQS = $baseParams ? ('&'.http_build_query($baseParams)) : '';
            ?>
            <nav class="mt-3">
              <ul class="pagination justify-content-end">
                <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= max(1,$currentPage-1) ?><?= $baseQS ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $baseQS ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= min($totalPages,$currentPage+1) ?><?= $baseQS ?>">Next</a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>

  <?php foreach ($rows as $r): try { $items = $db->fetchOrderItemsWithAddons($r['Order_ID']); } catch(Throwable $e) { $items=[]; } ?>
      <div class="modal fade" id="itemsModal<?=h($r['Order_ID'])?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Order #<?=h($r['Order_ID'])?> Items</h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?php
                $street = $r['Street'] ?? '';
                $city   = $r['City'] ?? '';
                $contactNum = $r['Contact_Number'] ?? '';
                // Prefer stored customer coordinates over textual address
                $lat = $r['customer_lat'] ?? $r['Customer_Lat'] ?? null;
                $lng = $r['customer_lng'] ?? $r['Customer_Lng'] ?? null;
                $hasCoords = is_numeric($lat) && is_numeric($lng);
                // Prefer explicit order_type when present; otherwise infer from stored address/coords
                $rawType = trim((string)($r['order_type'] ?? ''));
                if ($rawType !== '') {
                  $isDelivery = stripos($rawType, 'delivery') !== false;
                } else {
                  $isDelivery = $hasCoords || !empty($street) || !empty($city);
                }
              ?>
              <div class="mb-3">
                <h6 class="fw-semibold mb-1">Order Location / Contact</h6>
                <?php 
                  // Derive order type; fall back based on delivery presence if not set
                  $rawType = $r['order_type'] ?? '';
                  $orderType = $rawType !== '' ? ucfirst(strtolower($rawType)) : ($isDelivery ? 'Delivery' : 'Pickup');
                ?>
                <div class="small mb-1"><strong>Order Type:</strong> <?= h($orderType); ?></div>
                <?php if ($isDelivery): ?>
                  <div class="small"><strong>Contact #:</strong> <?= h($contactNum ?: '—'); ?></div>
                  <?php if ($hasCoords): ?>
                    <?php $addressParts = array_filter([$street, $city]); $fullAddress = $addressParts ? implode(', ', $addressParts) : '—'; ?>
                    <div class="small"><strong>Address:</strong> <?= h($fullAddress) ?></div>
                    <div class="ratio ratio-16x9 mt-2" style="border:1px solid #ddd; border-radius:6px; overflow:hidden;">
                      <iframe
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps?q=<?=urlencode($lat . ',' . $lng)?>&z=15&output=embed">
                      </iframe>
                    </div>
                    <div class="mt-2 d-flex flex-wrap align-items-center gap-3 small">
                      <a href="https://www.google.com/maps?q=<?=urlencode($lat . ',' . $lng)?>" target="_blank" rel="noopener" class="text-decoration-none">Open in Google Maps</a>
                      <button type="button"
                              class="btn btn-sm btn-outline-primary share-delivery-btn"
                              data-order="<?=h($r['Order_ID'])?>"
                              data-customer="<?=h($r['Customer_Name'] ?? 'Customer')?>"
                              data-contact="<?=h($contactNum ?: '')?>"
                              data-lat="<?=h($lat)?>"
                              data-lng="<?=h($lng)?>"
                              data-address="<?=h($fullAddress)?>">
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
                        if (!empty($it['addons']) && is_array($it['addons'])) {
                          foreach ($it['addons'] as $ad) {
                            $addonQty = (int)($ad['Quantity'] ?? 1);
                            $addonPrice = (float)($ad['Addon_Price'] ?? 0);
                            $addonTotal += $addonPrice * $addonQty * max(1,$qty); // stored as per unit addon
                          }
                        }
                        $lineWithAddons = $line + $addonTotal;
                        $subtotal += $line; 
                        $addonsSubtotal += $addonTotal; 
                        // Attempt to locate an image field from common column names
                        $img = $it['Image']
                          ?? $it['Product_Image']
                          ?? $it['product_image']
                          ?? $it['Image_URL']
                          ?? $it['image']
                          ?? '';
                        // Normalize to admin relative path if it's just a filename
                        $imgFile = '';
                        if ($img) {
                          $trim = trim($img);
                          // If it already looks like a URL or already has uploads/products keep as is
                          if (preg_match('~^https?://~i', $trim)) {
                            $imgFile = $trim;
                          } else {
                            // If no directory segment, prepend uploads/products/
                            if (strpos($trim,'/') === false && strpos($trim,'\\') === false) {
                              $imgFile = 'uploads/products/' . $trim; // we're already in /admin/
                            } else {
                              // If it does contain path but not starting with uploads/products, force basename
                              if (stripos($trim, 'uploads/products/') === 0) {
                                $imgFile = $trim;
                              } else {
                                $imgFile = 'uploads/products/' . basename($trim);
                              }
                            }
                          }
                        }
                  ?>
                    <li class="list-group-item d-flex flex-column">
                      <div class="d-flex w-100">
                        <div class="me-3" style="width:56px;">
                          <?php if($imgFile): ?>
                            <img src="<?=h($imgFile)?>" alt="Item" class="img-fluid rounded" style="height:56px; width:56px; object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling?.classList.remove('d-none');">
                            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted d-none" style="height:56px; width:56px; font-size:.65rem;">No Img</div>
                          <?php else: ?>
                            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="height:56px; width:56px; font-size:.65rem;">No Img</div>
                          <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                          <div class="d-flex justify-content-between align-items-start">
                            <span class="fw-semibold me-2" style="max-width:60%;"><?=h($it['Product_Name'] ?? 'Item')?></span>
                            <div class="text-end small">
                              <div><span class="text-muted">Item:</span> ₱<?=number_format($line,2)?></div>
                              <?php if($addonTotal>0): ?><div><span class="text-muted">Add-ons:</span> ₱<?=number_format($addonTotal,2)?></div><?php endif; ?>
                              <div class="fw-semibold border-top mt-1 pt-1">₱<?=number_format($lineWithAddons,2)?></div>
                            </div>
                          </div>
                          <?php if(isset($it['Instruction']) && trim((string)$it['Instruction']) !== ''): ?>
                            <div class="small fst-italic mt-1"><span class="text-muted">Instruction:</span> <?=h($it['Instruction'])?></div>
                          <?php endif; ?>
                          <small class="text-muted d-block">Qty: <?=$qty?> @ ₱<?=number_format($price,2)?></small>
                          <?php if($addonTotal>0): ?>
                            <div class="mt-1 small text-muted">
                              <?php foreach($it['addons'] as $ad): $adLine=(float)$ad['Addon_Price'] * (int)$ad['Quantity'] * max(1,$qty); ?>
                                <div>• <?=h($ad['Addon_Name'])?> x <?= (int)$ad['Quantity'] ?> = ₱<?=number_format($adLine,2)?></div>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <?php 
                  $deliveryFee = (float)($r['Delivery_Fee'] ?? 0); 
                  $grandTotal = $subtotal + $addonsSubtotal + $deliveryFee; 
                ?>
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
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Helper: load modal HTML on-demand for order items
async function loadAndShowOrderModal(orderId){
  try{
    const res = await fetch('ajax/get_order_details.php?order_id=' + encodeURIComponent(orderId), {credentials: 'same-origin'});
    if(!res.ok) throw new Error('HTTP ' + res.status);
    const j = await res.json();
    if(!j.success) throw new Error(j.message || 'Failed to load order details');
    // Insert HTML and initialize bootstrap modal
    const temp = document.createElement('div'); temp.innerHTML = j.html;
    document.body.appendChild(temp.firstElementChild);
    const modalEl = document.getElementById('itemsModal' + orderId);
    if(modalEl){ const bsModal = new bootstrap.Modal(modalEl); bsModal.show(); }
  }catch(e){ console.error('loadAndShowOrderModal error', e); }
}

document.addEventListener('click', async function(e){
  const btn = e.target.closest('.share-delivery-btn');
  if(!btn) return;
  const orderId  = btn.getAttribute('data-order');
  const customer = btn.getAttribute('data-customer') || 'Customer';
  const contact  = btn.getAttribute('data-contact') || 'N/A';
  const lat      = btn.getAttribute('data-lat');
  const lng      = btn.getAttribute('data-lng');
  const address  = btn.getAttribute('data-address') || 'N/A';
  const mapsUrl  = `https://www.google.com/maps?q=${encodeURIComponent(lat+','+lng)}`;
  const text = `Order #${orderId}\nCustomer: ${customer}\nContact: ${contact}\nAddress: ${address}\nLocation: ${lat}, ${lng}\nMap: ${mapsUrl}`;

  // Try Web Share API first (omit separate url so platforms include full text incl. contact)
  if (navigator.share) {
    try {
      await navigator.share({ title: `Order #${orderId} Delivery Info`, text });
      toast('Shared via device dialog.', 'success');
      return;
    } catch(err){ /* fall through to custom share menu */ }
  }
  // Show custom share menu (SMS / Messenger / Copy)
  showShareMenu(btn, { text, mapsUrl });
});

// Dismiss floating share menu when clicking outside
document.addEventListener('click', function(e){
  const menu = document.getElementById('shareMenuFloating');
  if(menu && !e.target.closest('#shareMenuFloating') && !e.target.closest('.share-delivery-btn')){
    menu.remove();
  }
});

function showShareMenu(anchorEl, data){
  const existing = document.getElementById('shareMenuFloating');
  if(existing) existing.remove();
  const { text, mapsUrl } = data;
  const smsLink = 'sms:?&body=' + encodeURIComponent(text);
  // Messenger deep link (device app); fallback to web
  const messengerDeep = 'fb-messenger://share?link=' + encodeURIComponent(mapsUrl);
  const messengerWeb  = 'https://m.me/?link=' + encodeURIComponent(mapsUrl);
  const copyId = 'copyShare_'+Date.now();
  const html = `
    <div id="shareMenuFloating" class="card shadow-sm border-0" style="position:absolute; z-index:2000; width:230px;">
      <div class="card-body p-2 small">
        <div class="fw-semibold mb-2">Share Delivery Info</div>
        <a href="${smsLink}" class="d-flex align-items-center gap-2 text-decoration-none py-1 px-2 rounded hover-bg" style="white-space:nowrap;">
          <i class="bi bi-chat-dots"></i><span>SMS / Messages</span>
        </a>
        <a href="${messengerDeep}" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-decoration-none py-1 px-2 rounded hover-bg messenger-link" data-fallback="${messengerWeb}" style="white-space:nowrap;">
          <i class="bi bi-send"></i><span>Messenger</span>
        </a>
        <button id="${copyId}" type="button" class="w-100 btn btn-sm btn-outline-secondary mt-2">Copy Text</button>
      </div>
    </div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const menu = document.getElementById('shareMenuFloating');
  // Position near anchor
  const rect = anchorEl.getBoundingClientRect();
  const top = window.scrollY + rect.bottom + 6;
  let left = window.scrollX + rect.left;
  if(left + 250 > window.innerWidth) left = window.innerWidth - 250 - 8;
  menu.style.top = top + 'px';
  menu.style.left = left + 'px';
  // Messenger fallback: open deep link; after short delay open web (if app not installed user stays on web)
  menu.querySelectorAll('.messenger-link').forEach(a => {
    a.addEventListener('click', function(){
      const fallback = a.getAttribute('data-fallback');
      setTimeout(()=>{ window.open(fallback, '_blank'); }, 900);
    });
  });
  // Copy text handler
  document.getElementById(copyId).addEventListener('click', async ()=>{
    try { await navigator.clipboard.writeText(text); toast('Copied to clipboard.', 'success'); } catch(e){ alert('Copy failed'); }
    menu.remove();
  });
  // Inject minimal hover style once
  if(!document.getElementById('shareMenuStyle')){
    const style = document.createElement('style');
    style.id = 'shareMenuStyle';
    style.textContent = '.hover-bg:hover{background:#f5f5f5;}';
    document.head.appendChild(style);
  }
}

// Lightweight toast helper (Bootstrap 5 independent minimal)
function toast(message, type){
  let box = document.createElement('div');
  box.className = 'position-fixed top-0 end-0 p-3';
  box.style.zIndex = 1080;
  box.innerHTML = `<div class="alert alert-${type} py-2 px-3 shadow-sm mb-0">${message}</div>`;
  document.body.appendChild(box);
  setTimeout(()=>{ box.remove(); }, 2500);
}

// ---- Realtime new orders polling ----
(function(){
  const tbody = document.querySelector('table tbody');
  if(!tbody) return;
  let lastId = 0;
  // Initialize lastId from current rows
  tbody.querySelectorAll('tr').forEach(tr=>{
    const first = tr.querySelector('td');
    if(first){ const val = parseInt(first.textContent.trim(),10); if(val>lastId) lastId = val; }
  });

  async function poll(){
    try {
  const res = await fetch('ajax/orders_payments_updates.php?last_id=' + lastId + '&t=' + Date.now(), {cache:'no-store', credentials: 'same-origin'});
      if (!res.ok) {
        // Attempt to show server-provided error details to help debugging
        let bodyText = '';
        try {
          bodyText = await res.text();
          try { const parsed = JSON.parse(bodyText); console.error('orders_payments_updates.php error', res.status, parsed); } catch(_){ console.error('orders_payments_updates.php error', res.status, bodyText); }
        } catch(e){ console.error('orders_payments_updates.php error reading body', e); }
        throw new Error('HTTP ' + res.status);
      }
      const data = await res.json();
      if(data.success && Array.isArray(data.rows) && data.rows.length){
        data.rows.forEach(r=>{
          const tr = document.createElement('tr');
          // Decide status options similar to PHP side for consistency; prefer explicit order_type when present
          let isDelivery = false;
          if (r.order_type && typeof r.order_type === 'string' && r.order_type.trim() !== '') {
            isDelivery = /delivery/i.test(r.order_type);
          } else {
            isDelivery = !!(r.Street || r.City);
          }
          const statusOptions = isDelivery ? ["Pending","Preparing","Ready to deliver","On the way","Delivered","Cancelled"] : ["Pending","Preparing","Ready to pick up","Received","Cancelled"];
          const statusSelect = statusOptions.map(st => `<option value="${st}" ${r.order_status===st? 'selected':''}>${st}</option>`).join('');
          const paySelect = `<select name=\"payment_status\" class=\"form-select form-select-sm\" onchange=\"this.form.submit()\"><option value=\"Paid\" ${r.Payment_Status==='Paid'?'selected':''}>Paid</option><option value=\"Unpaid\" ${r.Payment_Status==='Unpaid'?'selected':''}>Unpaid</option></select>`;
          tr.innerHTML = `
            <td>${r.Order_ID}</td>
            <td>${(r.Customer_Name||'Unknown').replace(/</g,'&lt;')}</td>
            <td>${r.Order_Date}</td>
            <td>₱${Number(r.Order_Amount||0).toFixed(2)}</td>
            <td>
              <form method=\"post\" class=\"mb-0\">
                <input type=\"hidden\" name=\"order_id\" value=\"${r.Order_ID}\">
                <select name=\"order_status\" class=\"form-select form-select-sm\" onchange=\"this.form.submit()\">${statusSelect}</select>
              </form>
            </td>
            <td>${ r.Payment_ID ? `<form method=\"post\" class=\"mb-0\"><input type=\"hidden\" name=\"payment_id\" value=\"${r.Payment_ID}\">${paySelect}</form>` : '<span class=\"badge bg-warning text-dark\">Unpaid</span>' }</td>
            <td>${ r.Payment_Method ? r.Payment_Method : '<span class=\"text-muted\">-</span>' }</td>
            <td><span class=\"badge bg-info text-dark\">New</span></td>`;
          tbody.prepend(tr);
          if(r.Order_ID > lastId) lastId = r.Order_ID;
          // Ensure modal is available for this new row: fetch on-demand when Items button clicked
          // The button in the rendered row uses data-bs-toggle/data-bs-target but we fetch modal lazily instead
          const itemsBtn = tr.querySelector('button[data-bs-toggle="modal"]');
          if(itemsBtn){
            itemsBtn.addEventListener('click', function(ev){
              const target = itemsBtn.getAttribute('data-bs-target') || itemsBtn.getAttribute('data-target');
              const m = target && target.replace('#itemsModal','');
              const id = m || r.Order_ID;
              if(!document.getElementById('itemsModal' + id)){
                ev.preventDefault(); ev.stopPropagation(); loadAndShowOrderModal(id);
              }
            });
          }
        });
      }
      if(data.stats){
        const fmt = n => new Intl.NumberFormat().format(n||0);
        if(document.getElementById('statTotal')) document.getElementById('statTotal').textContent = fmt(data.stats.total);
        if(document.getElementById('statUnpaid')) document.getElementById('statUnpaid').textContent = fmt(data.stats.unpaid);
        if(document.getElementById('statPendingProc')) document.getElementById('statPendingProc').textContent = fmt(data.stats.pending_processing);
      }
    } catch(e){ /* silent */ }
    finally { setTimeout(poll, 15000); }
  }
  poll();
})();
</script>
</body>
</html>
