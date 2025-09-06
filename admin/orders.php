<?php
// Professional layout restored with: search, filters, pagination, status update selects, badges.
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'classes/database.php';
$db = new database();

$error = '';
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Handle updates (order status / optional payment status if added later)
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
        header('Location: orders.php' . ($error ? ('?msg=' . urlencode($error)) : '?' . http_build_query(array_diff_key($_GET, ['updated'=>1]))));
        exit;
}

try {
        $allOrders = $db->fetchOrders();
        if ($search) {
                $allOrders = array_filter($allOrders, function($o) use ($db, $search) {
                        $name = strtolower($db->getCustomerNameById($o['Customer_ID']));
                        return strpos($name, strtolower($search)) !== false;
                });
        }
        if ($statusFilter) {
                $allOrders = array_filter($allOrders, fn($o) => isset($o['order_status']) && strcasecmp($o['order_status'], $statusFilter) === 0);
        }
        if ($paymentFilter) {
                $allOrders = array_filter($allOrders, fn($o) => strcasecmp($o['payment_status'] ?? 'Unpaid', $paymentFilter) === 0);
        }
        $totalOrders = count($allOrders);
        $orders = array_slice(array_values($allOrders), $offset, $perPage);
        $totalPages = max(1, (int)ceil($totalOrders / $perPage));
} catch (Throwable $e) {
        $orders = [];
        $totalOrders = 0;
        $totalPages = 1;
        $error = 'Database error: ' . $e->getMessage();
}

function safe($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function admin_display_status($row) {
        $isDelivery = !empty($row['Street']) || !empty($row['City']) || !empty($row['Contact_Number']);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Orders</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="col-md-10 col-lg-10 main-content">
            <div class="header d-flex justify-content-between align-items-center mt-3">
                <div>
                    <h4 class="mb-0 fw-bold">Orders</h4>
                    <p class="mb-0 text-muted small">Manage and track all customer orders</p>
                </div>
                <button class="btn btn-outline-primary d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse"><i class="bi bi-list"></i></button>
            </div>

            <div class="card mt-3 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold"><i class="bi bi-bag-check me-1"></i> Orders List</span>
                    <form method="get" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer..." value="<?= safe($search) ?>" style="max-width:180px;" />
                        <select name="status" class="form-select form-select-sm" onchange="this.form.page && (this.form.page.value=1); this.form.submit();">
                            <option value="">All Status</option>
                            <?php foreach (["Pending","Processing","Ready to deliver","On the way","Delivered","Ready to pick up","Received","Cancelled"] as $s): ?>
                                <option value="<?= safe($s) ?>" <?= $statusFilter===$s?'selected':'' ?>><?= safe($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment" class="form-select form-select-sm" onchange="this.form.page && (this.form.page.value=1); this.form.submit();">
                            <option value="">All Payments</option>
                            <option value="Paid" <?= $paymentFilter==='Paid'?'selected':'' ?>>Paid</option>
                            <option value="Unpaid" <?= $paymentFilter==='Unpaid'?'selected':'' ?>>Unpaid</option>
                        </select>
                        <?php if (isset($_GET['page'])): ?><input type="hidden" name="page" value="<?= (int)$page ?>"><?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary" type="submit" title="Search"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger py-2 mb-3 small"><?= safe($error) ?></div><?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
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
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$orders): ?>
                                <tr><td colspan="11" class="text-center text-muted py-4">No orders found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-semibold"><i class="bi bi-person-circle me-1"></i><?= safe($db->getCustomerNameById($order['Customer_ID'])) ?></td>
                                    <td class="text-muted small"><i class="bi bi-calendar-event me-1"></i><?= safe(date('M j, Y g:i A', strtotime($order['Order_Date']))) ?></td>
                                    <td class="fw-bold text-primary">₱<?= number_format($order['Order_Amount'],2) ?></td>
                                    <td><?= safe($order['Street']) ?></td>
                                    <td><?= safe($order['Barangay']) ?></td>
                                    <td><?= safe($order['City']) ?></td>
                                    <td><?= safe($order['Contact_Number']) ?></td>
                                    <td>
                                        <?php $disp = admin_display_status($order); ?>
                                        <span class="badge bg-<?=
                                            $disp==='Out for delivery'?'primary':(
                                                $disp==='Preparing'?'info':(
                                                    $disp==='Delivered'?'success':(
                                                        $disp==='Cancelled'?'danger':'secondary'
                                                    )
                                                )
                                            )
                                        ?>"><?= safe($disp) ?></span>
                                    </td>
                                    <td>
                                        <form method="post" class="mb-0">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['Order_ID'] ?>">
                                            <select name="order_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:135px;">
                                                <?php
                                                    $isDelivery = !empty($order['Street']) || !empty($order['City']) || !empty($order['Contact_Number']);
                                                    $opts = $isDelivery ? ['Pending','Processing','Ready to deliver','On the way','Delivered','Cancelled'] : ['Pending','Processing','Ready to pick up','Received','Cancelled'];
                                                    foreach ($opts as $opt) {
                                                        $sel = $order['order_status']===$opt?'selected':'';
                                                        echo '<option value="'.safe($opt).'" '.$sel.'>'.safe($opt).'</option>';
                                                    }
                                                ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge px-2 py-1 <?= ($order['payment_status']??'')==='Paid'?'bg-success':'bg-secondary' ?>">
                                            <i class="bi bi-cash-coin"></i> <?= safe($order['payment_status'] ?? 'Unpaid') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewItemsModal<?= (int)$order['Order_ID'] ?>" title="View items"><i class="bi bi-eye"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <li class="page-item <?= $page==1?'disabled':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">Previous</a>
                            </li>
                            <?php for ($i=1;$i<=$totalPages;$i++): ?>
                                <li class="page-item <?= $page==$i?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page==$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <?php // Static modals for each order in current page ?>
            <?php foreach ($orders as $order): ?>
                <?php try { $items = $db->fetchOrderItems($order['Order_ID']); } catch (Throwable $e) { $items = []; } ?>
                <div class="modal fade" id="viewItemsModal<?= (int)$order['Order_ID'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Order #<?= (int)$order['Order_ID'] ?> Items</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!$items): ?>
                                    <p class="text-muted mb-0">No items found.</p>
                                <?php else: ?>
                                    <ul class="list-group mb-3">
                                        <?php $subtotal=0; foreach ($items as $it): $qty=(int)$it['Quantity']; $price=(float)$it['Price']; $line=$qty*$price; $subtotal+=$line; ?>
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
</body>
</html>
