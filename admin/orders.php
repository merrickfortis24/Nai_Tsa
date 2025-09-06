<?php
// Simplified Orders page with static per-order modals (no AJAX) per user request
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'classes/database.php';
$db = new database();

// Fetch all orders (you can later re-add filtering/pagination if needed)
try {
        $orders = $db->fetchOrders();
} catch (Throwable $e) {
        $orders = [];
}

function safe($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
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
        <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="col-md-10 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                <h4 class="fw-bold mb-0">Orders</h4>
                <button class="btn btn-outline-primary d-lg-none" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse"><i class="bi bi-list"></i></button>
            </div>
            <div class="card shadow-sm">
                <div class="card-header fw-semibold"><i class="bi bi-bag-check me-1"></i> Orders List</div>
                <div class="card-body">
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
                                    <td><span class="badge bg-secondary"><?= safe($o['order_status']) ?></span></td>
                                    <td><span class="badge <?= ($o['payment_status']??'')==='Paid'?'bg-success':'bg-warning text-dark' ?>"><?= safe($o['payment_status'] ?? 'Unpaid') ?></span></td>
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
                </div>
            </div>

            <?php // Static modals for each order ?>
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
</body>
</html>
