<?php
// Orders page with search, filters, pagination, static per-order modals (professional version)
session_start();
require_once __DIR__ . '/../includes/remember.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'classes/database.php';
$db = new database();

function safe($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Legacy POST status updates removed. Order status now updated via AJAX endpoint (ajax/orders_payments_updates.php).

// --- Search, filters, pagination ---
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment = $_GET['payment'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
        $orders = $db->fetchOrdersFiltered($search, $status, $payment, $perPage, $offset);
        $totalOrders = $db->countOrdersFiltered($search, $status, $payment);
} catch (Throwable $e) {
        $orders = [];
        $totalOrders = 0;
}
$totalPages = max(1, ceil($totalOrders / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
<div class="container-fluid">
    <div class="row">
        <!-- Desktop sidebar (visible on md+) -->
        <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
            <?php include 'sidebar.php'; ?>
        </div>
        <!-- Offcanvas sidebar for small screens (moved to end of page) -->
        <div class="col-md-10 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                <h4 class="fw-bold mb-0">Orders</h4>
                <!-- Sidebar toggle button for small screens (opens offcanvas) -->
                <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                    <i class="bi bi-list" style="font-size:1.7rem;"></i>
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold"><i class="bi bi-bag-check me-1"></i> Orders List</div>
                <div class="card-body">
                    <!-- Filters + Search -->
                    <form method="get" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <input type="text" name="search" value="<?= safe($search) ?>" class="form-control" placeholder="Search by ID or Customer">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach (["Pending","Processing","Ready to deliver","On the way","Delivered","Ready to pick up","Received","Cancelled"] as $s): ?>
                                    <option value="<?= safe($s) ?>" <?= $status===$s?'selected':'' ?>><?= safe($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="payment" class="form-select">
                                <option value="">All Payments</option>
                                <option value="Paid" <?= $payment==='Paid'?'selected':'' ?>>Paid</option>
                                <option value="Unpaid" <?= $payment==='Unpaid'?'selected':'' ?>>Unpaid</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button class="btn btn-primary"><i class="bi bi-search"></i> Apply</button>
                        </div>
                    </form>

                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$orders): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= safe($o['Order_ID']) ?></td>
                                    <td><?= safe($db->getCustomerNameById($o['Customer_ID'])) ?></td>
                                    <td><?= safe(date('Y-m-d H:i', strtotime($o['Order_Date']))) ?></td>
                                    <td>₱<?= number_format($o['Order_Amount'],2) ?></td>
                                    <td>
                                            <form class="status-form mb-0" onsubmit="return false;">
                                            <select name="order_status" class="form-select form-select-sm order-status-select" data-order-id="<?= (int)$o['Order_ID'] ?>">
                                                <?php 
                                                    $statuses = [
                                                        'Pending'=>'secondary','Processing'=>'info','Ready to deliver'=>'info','On the way'=>'primary','Delivered'=>'success','Ready to pick up'=>'info','Received'=>'success','Cancelled'=>'danger'
                                                    ];
                                                    foreach ($statuses as $st=>$color): ?>
                                                        <option value="<?= safe($st) ?>" <?= $o['order_status']===$st?'selected':'' ?>><?= safe($st) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            </form>
                                    </td>
                                    <td>
                                        <?php if (($o['payment_status']??'')==='Paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewItemsModal<?= safe($o['Order_ID']) ?>" title="View Items">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php for ($i=1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i==$page?'active':'' ?>">
                                        <a class="page-link" href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&payment=<?= urlencode($payment) ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Static Modals -->
            <?php foreach ($orders as $o): ?>
                <?php
                    try { $items = $db->fetchOrderItems($o['Order_ID']); } catch (Throwable $e) { $items = []; }
                ?>
                <div class="modal fade" id="viewItemsModal<?= safe($o['Order_ID']) ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Order #<?= safe($o['Order_ID']) ?> Items</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!$items): ?>
                                    <p class="text-muted mb-0">No items found.</p>
                                <?php else: ?>
                                    <ul class="list-group mb-3">
                                        <?php $subtotal = 0; foreach ($items as $it):
                                            $qty = (int)$it['Quantity'];
                                            $price = (float)$it['Price'];
                                            $line = $qty * $price; $subtotal += $line; ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-semibold"><?= safe($it['Product_Name']) ?></div>
                                                    <small class="text-muted">Qty: <?= $qty ?> @ ₱<?= number_format($price,2) ?></small>
                                                </div>
                                                <span class="fw-semibold">₱<?= number_format($line,2) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="d-flex justify-content-between border-top pt-2">
                                        <strong>Items Total</strong>
                                        <strong>₱<?= number_format($subtotal,2) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toast(message,type){
    let box=document.getElementById('toastBox');
    if(!box){ box=document.createElement('div'); box.id='toastBox'; box.className='position-fixed top-0 end-0 p-3'; box.style.zIndex=1080; document.body.appendChild(box); }
    const el=document.createElement('div'); el.className='alert alert-'+type+' py-2 px-3 mb-2 shadow-sm'; el.textContent=message; box.appendChild(el); setTimeout(()=>el.remove(),2500);
}
document.addEventListener('change', async (e)=>{
    const sel=e.target.closest('select.order-status-select');
    if(!sel) return;
    const orderId=sel.getAttribute('data-order-id');
    const newStatus=sel.value; const prev=sel.getAttribute('data-prev')||''; if(newStatus===prev) return;
    sel.disabled=true;
    try{
        const res=await fetch('ajax/orders_payments_updates.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:orderId,order_status:newStatus})});
        const j=await res.json();
        if(!j.success){ toast(j.message||'Update failed','danger'); sel.value=prev; }
        else { toast('Status updated','success'); sel.setAttribute('data-prev',newStatus);} }
    catch(err){ sel.value=prev; toast('Network error','danger'); }
    finally{ sel.disabled=false; }
});
document.querySelectorAll('select.order-status-select').forEach(s=>s.setAttribute('data-prev',s.value));
</script>
</body>
</html>
<?php include 'offcanvas_sidebar.php'; ?>
