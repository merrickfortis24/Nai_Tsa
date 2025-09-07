<?php
// Unified Orders & Payments page (follows pattern of original orders.php + payments.php backend structure)
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'classes/database.php';
$db = new database();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8'); }

// --- Handle inline updates (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['order_id'], $_POST['order_status'])) {
    try { $db->updateOrderStatus((int)$_POST['order_id'], $_POST['order_status']); } catch (Throwable $e) { /* ignore */ }
  }
  if (isset($_POST['payment_id'], $_POST['payment_status'])) {
    try { $db->updatePaymentStatus((int)$_POST['payment_id'], $_POST['payment_status']); } catch (Throwable $e) { /* ignore */ }
  }
  $qs = $_GET; unset($qs['page']);
  header('Location: orders_payments.php' . ($qs ? ('?' . http_build_query($qs)) : ''));
  exit;
}

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
          <form method="get" class="row g-2 mb-3 align-items-end filter-row">
            <div class="col-12 col-md flex-grow-1">
              <input type="text" name="search" value="<?=h($search)?>" class="form-control" placeholder="Search by Order ID or Customer" />
            </div>
            <div class="col-auto">
              <select name="status" class="form-select form-select-sm short-select" title="Order Status">
                <option value="">All Status</option>
                <?php foreach (["Pending","Processing","Ready to deliver","On the way","Delivered","Ready to pick up","Received","Cancelled"] as $s): ?>
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
                <div class="fw-semibold"><?=number_format($total)?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 border rounded bg-light text-center">
                <div class="text-muted">Unpaid Payments</div>
                <div class="fw-semibold text-danger"><?=number_format($unpaidPayments)?></div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 border rounded bg-light text-center">
                <div class="text-muted">Pending / Processing Orders</div>
                <div class="fw-semibold text-warning"><?=number_format($pendingProcessingCount)?></div>
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
                    <td><?=h(date('Y-m-d H:i', strtotime($r['Order_Date'])))?></td>
                    <td>₱<?=number_format($r['Order_Amount'],2)?></td>
                    <td>
                        <form method="post" class="mb-0">
                          <input type="hidden" name="order_id" value="<?= (int)$r['Order_ID'] ?>">
                          <?php
                            // Delivery if address fields present (contact number alone not decisive)
                            $isDelivery = !empty($r['Street']) || !empty($r['City']);
                            $statusOptions = $isDelivery
                              ? ["Pending","Processing","Ready to deliver","On the way","Delivered","Cancelled"]
                              : ["Pending","Processing","Ready to pick up","Received","Cancelled"];
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

  <?php foreach ($rows as $r): try { $items = $db->fetchOrderItems($r['Order_ID']); } catch(Throwable $e) { $items=[]; } ?>
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
                $isDelivery = $hasCoords || !empty($street) || !empty($city);
              ?>
              <div class="mb-3">
                <h6 class="fw-semibold mb-1">Order Location / Contact</h6>
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
                    <div class="mt-2 small">
                      <a href="https://www.google.com/maps?q=<?=urlencode($lat . ',' . $lng)?>" target="_blank" rel="noopener">Open in Google Maps</a>
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
              <?php else: $subtotal=0; ?>
                <ul class="list-group mb-3">
                  <?php foreach ($items as $it): $qty=(int)$it['Quantity']; $price=(float)$it['Price']; $line=$qty*$price; $subtotal+=$line; ?>
                    <li class="list-group-item d-flex justify-content-between">
                      <div>
                        <div class="fw-semibold"><?=h($it['Product_Name'])?></div>
                        <small class="text-muted">Qty: <?=$qty?> @ ₱<?=number_format($price,2)?></small>
                      </div>
                      <span class="fw-semibold">₱<?=number_format($line,2)?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between border-top pt-2"><strong>Items Total</strong><strong>₱<?=number_format($subtotal,2)?></strong></div>
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
</body>
</html>
