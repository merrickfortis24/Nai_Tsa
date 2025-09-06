<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');


$db = new database();
$error = '';
// --- Backend: Filtering, Search, Pagination ---
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['order_status'], $_POST['order_id'])) {
            $db->updateOrderStatus((int)$_POST['order_id'], $_POST['order_status']);
        }
        if (isset($_POST['payment_status'], $_POST['order_id'])) {
            $db->updatePaymentStatusByOrder((int)$_POST['order_id'], $_POST['payment_status']);
        }
    } catch (Throwable $e) {
        $error = 'Update failed: ' . $e->getMessage();
    }
    header('Location: orders.php' . ($error ? ('?msg=' . urlencode($error)) : '?updated=1'));
    exit();
}

// Fetch and filter orders
try {
    $allOrders = $db->fetchOrders();
    // Filter by search (customer name)
    if ($search) {
        $allOrders = array_filter($allOrders, function($o) use ($db, $search) {
            $name = strtolower($db->getCustomerNameById($o['Customer_ID']));
            return strpos($name, strtolower($search)) !== false;
        });
    }
    // Filter by status
    if ($statusFilter) {
        $allOrders = array_filter($allOrders, function($o) use ($statusFilter) {
            return strtolower($o['order_status']) === strtolower($statusFilter);
        });
    }
    // Filter by payment
    if ($paymentFilter) {
        $allOrders = array_filter($allOrders, function($o) use ($paymentFilter) {
            return strtolower($o['payment_status'] ?? 'Unpaid') === strtolower($paymentFilter);
        });
    }
    $totalOrders = count($allOrders);
    $orders = array_slice(array_values($allOrders), $offset, $perPage);
    $totalPages = max(1, ceil($totalOrders / $perPage));
} catch (PDOException $e) {
    $orders = [];
    $error = "Database Error: " . $e->getMessage();
}

// Derived display status helper
function admin_display_status($row) {
    // Derive order type: delivery if has address/contact
    $isDelivery = !empty($row['Street']) || !empty($row['City']) || !empty($row['Contact_Number']);
    // Driver statuses 'on_the_way'/'picked_up' map to Out for delivery
    if ($isDelivery && (
        (isset($row['order_status']) && $row['order_status'] === 'On the way') ||
        (isset($row['Driver_Status']) && in_array($row['Driver_Status'], ['on_the_way','picked_up'], true))
    )) {
        return 'Out for delivery';
    }
    if (!empty($row['order_status']) && $row['order_status'] === 'Processing') {
        return 'Preparing';
    }
    return $row['order_status'] ?? 'Pending';
}

// (POST handling moved above, before fetching orders)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
                <?php include 'sidebar.php'; ?>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content">
                <!-- Header -->
                <div class="header d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <h4 class="mb-0 fw-bold">Orders</h4>
                        <p class="mb-0 text-muted">List of all orders</p>
                    </div>
                    <!-- Sidebar toggle button for small screens -->
                    <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="bi bi-list" style="font-size:1.7rem;"></i>
                    </button>
                </div>
                <div class="card mt-3 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="fw-semibold"><i class="bi bi-bag-check me-1"></i> Orders List</span>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <form method="get" action="orders.php" class="d-flex align-items-center gap-2 mb-0">
                                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search customer..." value="<?= htmlspecialchars($search) ?>" style="max-width:180px;" />
                                <select class="form-select form-select-sm" name="status" onchange="this.form.page && (this.form.page.value=1); this.form.submit();">
                                    <option value="">All Status</option>
                                    <?php foreach (["Pending","Processing","Ready to deliver","On the way","Delivered","Ready to pick up","Received","Cancelled"] as $s): ?>
                                        <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm" name="payment" onchange="this.form.page && (this.form.page.value=1); this.form.submit();">
                                    <option value="">All Payments</option>
                                    <option value="Paid" <?= $paymentFilter==='Paid'?'selected':'' ?>>Paid</option>
                                    <option value="Unpaid" <?= $paymentFilter==='Unpaid'?'selected':'' ?>>Unpaid</option>
                                </select>
                                <?php if (isset($_GET['page'])): ?>
                                    <input type="hidden" name="page" value="<?= (int)$_GET['page'] ?>">
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" type="submit" title="Search by customer"><i class="bi bi-search"></i></button>
                            </form>
                            <!-- Placeholder for future actions (e.g., Export CSV) -->
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Street</th>
                                        <th>Barangay</th>
                                        <th>City</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Order Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php 
                                        // Preload items for fallback (acceptable for small page size)
                                        try { 
                                            $preItems = $db->fetchOrderItems($order['Order_ID']); 
                                        } catch (Throwable $e) { 
                                            $preItems = []; 
                                        }
                                        $preItemsJson = htmlspecialchars(json_encode($preItems), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($db->getCustomerNameById($order['Customer_ID'])) ?></span>
                                        </td>
                                        <td><span class="text-muted small"><i class="bi bi-calendar-event me-1"></i><?= date('M j, Y g:i A', strtotime($order['Order_Date'])) ?></span></td>
                                        <td><span class="fw-bold text-primary">₱<?= number_format($order['Order_Amount'], 2) ?></span></td>
                                        <td><?= htmlspecialchars($order['Street']) ?></td>
                                        <td><?= htmlspecialchars($order['Barangay']) ?></td>
                                        <td><?= htmlspecialchars($order['City']) ?></td>
                                        <td><?= htmlspecialchars($order['Contact_Number']) ?></td>
                                        <td>
                                            <?php $disp = admin_display_status($order); ?>
                                            <span class="badge bg-<?=
                                                $disp==='Out for delivery'?'primary':
                                                ($disp==='Preparing'?'info':
                                                ($disp==='Delivered'?'success':
                                                ($disp==='Cancelled'?'danger':'secondary')))
                                            ?>"><?= htmlspecialchars($disp) ?></span>
                                        </td>
                                        <td>
                                            <form method="post" action="orders.php" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>">
                                                <select
                                                    name="order_status"
                                                    class="form-select form-select-sm order-status-select
                                                        <?php
                                                            if ($order['order_status'] === 'Pending') echo ' bg-warning text-dark';
                                                            elseif ($order['order_status'] === 'Processing') echo ' bg-info text-dark';
                                                            elseif ($order['order_status'] === 'Delivered') echo ' bg-success text-white';
                                                            elseif ($order['order_status'] === 'Cancelled') echo ' bg-danger text-white';
                                                        ?>"
                                                    onchange="this.form.submit()"
                                                    style="min-width:120px;"
                                                >
                                                    <?php
                                                    $isDelivery = !empty($order['Street']) || !empty($order['City']) || !empty($order['Contact_Number']);
                                                    if ($isDelivery) {
                                                        $options = ['Pending', 'Processing', 'Ready to deliver', 'On the way', 'Delivered', 'Cancelled'];
                                                    } else {
                                                        $options = ['Pending', 'Processing', 'Ready to pick up', 'Received', 'Cancelled'];
                                                    }
                                                    foreach ($options as $opt) {
                                                        $selected = ($order['order_status'] === $opt) ? 'selected' : '';
                                                        echo "<option value=\"$opt\" $selected>$opt</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge px-2 py-1 <?= ($order['payment_status'] ?? '') === 'Paid' ? 'bg-success' : 'bg-secondary' ?>">
                                                <i class="bi bi-cash-coin"></i> <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info px-2 py-1 view-items-btn"
                                                title="View items"
                                                data-bs-toggle="modal"
                                                data-bs-target="#orderItemsModal"
                                                data-order-id="<?= $order['Order_ID'] ?>"
                                                data-items='<?= $preItemsJson ?>'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination justify-content-end">
                                <li class="page-item <?= $page==1?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$page-1])) ?>">Previous</a>
                                </li>
                                <?php for($i=1;$i<=$totalPages;$i++): ?>
                                <li class="page-item <?= $page==$i?'active':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$i])) ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page==$totalPages?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$page+1])) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Unified Order Items Modal -->
<div class="modal fade" id="orderItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderItemsModalTitle">Order Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="orderItemsLoading" class="text-center text-muted py-3" style="display:none;">
                    <div class="spinner-border spinner-border-sm me-2"></div>Loading...
                </div>
                <div id="orderItemsError" class="alert alert-danger py-2 px-3 small" style="display:none;"></div>
                <ul id="orderItemsList" class="list-unstyled mb-0"></ul>
            </div>
            <div class="modal-footer small justify-content-between">
                <span id="orderItemsSummary" class="text-muted"></span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('orderItemsModal');
  if(!modalEl) return;

  const titleEl = document.getElementById('orderItemsModalTitle');
  const listEl  = document.getElementById('orderItemsList');
  const loadEl  = document.getElementById('orderItemsLoading');
  const errEl   = document.getElementById('orderItemsError');
  const sumEl   = document.getElementById('orderItemsSummary');

  function renderItems(items){
      if(!Array.isArray(items) || !items.length){
          listEl.innerHTML = '<li class="text-muted">No items found for this order.</li>';
          return 0;
      }
      listEl.innerHTML = items.map(it => `
        <li class="mb-2">
          <div class="d-flex justify-content-between">
            <span>${(it.Product_Name||'Item').replace(/</g,'&lt;')} <span class="text-muted small">x ${it.Quantity||1}</span></span>
            <span class="small text-muted">₱${Number(it.Price||0).toFixed(2)}</span>
          </div>
        </li>`).join('');
      return items.reduce((s,i)=> s + (Number(i.Price)||0)*(Number(i.Quantity)||1),0);
  }

  function reset(orderId){
      titleEl.textContent = 'Order #'+orderId+' Items';
      listEl.innerHTML = '';
      sumEl.textContent = '';
      errEl.style.display='none'; errEl.textContent='';
      loadEl.style.display='block';
  }

  modalEl.addEventListener('show.bs.modal', function (ev){
      const btn = ev.relatedTarget;
      if(!btn) return;
      const orderId = btn.getAttribute('data-order-id');
      const preloadRaw = btn.getAttribute('data-items');
      reset(orderId);

      // Preload (cached)
      if(preloadRaw){
          try {
              const pre = JSON.parse(preloadRaw);
              if(Array.isArray(pre) && pre.length){
                  const total = renderItems(pre);
                  sumEl.textContent = 'Line items total: ₱'+total.toFixed(2)+' (cached)';
                  loadEl.style.display='none';
              }
          } catch(e){ console.warn('Preload parse failed', e); }
      }

      // Live fetch
      fetch('order_items_api.php?order_id='+encodeURIComponent(orderId)+'&t='+Date.now())
        .then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(data => {
            loadEl.style.display='none';
            if(!data.success){
                errEl.textContent = data.message || 'Failed to load items.';
                errEl.style.display='block';
                return;
            }
            const total = renderItems(data.items||[]);
            sumEl.textContent = 'Line items total: ₱'+total.toFixed(2);
        })
        .catch(err => {
            loadEl.style.display='none';
            errEl.textContent = 'Error: ' + err.message;
            errEl.style.display='block';
            console.error('Fetch error', err);
        });
  });
})();
</script>
</body>
</html>