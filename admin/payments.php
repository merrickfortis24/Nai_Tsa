<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');

$db = new database();

// Update payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_status'], $_POST['payment_id'])) {
    $db->updatePaymentStatus($_POST['payment_id'], $_POST['payment_status']);
    header("Location: payments.php");
    exit;
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_status'], $_POST['order_id'])) {
    $db->updateOrderStatus($_POST['order_id'], $_POST['order_status']);
    header("Location: orders.php");
    exit;
}

// Fetch all payments
$error = '';
try {
    $payments = $db->getAllPayments();
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// Count unpaid payments
$unpaidPayments = $db->countUnpaidPayments();

// Count pending/processing orders
$pendingOrders = $db->countPendingOrProcessingOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 d-md-block sidebar collapse">
                <?php include 'sidebar.php'; ?>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content">
                <div class="header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold">Payments</h4>
                        <p class="mb-0 text-muted">List of all payments</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Payments List</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <!-- <th>Actions</th> --> <!-- Removed Actions column -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr<?= (isset($payment['order_status']) && $payment['order_status'] === 'Cancelled') ? ' class="table-danger"' : '' ?>>
                                        <td>
                                            <?php
$customerName = $db->getCustomerNameByOrderId($payment['Order_ID']);
echo htmlspecialchars($customerName);
?>
                                        </td>
                                        <td>$<?= number_format($payment['Payment_Amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($payment['Payment_Method']) ?></td>
                                        <td><?= date('F j, Y g:i A', strtotime($payment['Payment_Date'])) ?></td>
                                        <td>
                                            <?php if (isset($payment['order_status']) && $payment['order_status'] === 'Cancelled'): ?>
                                                <span class="badge bg-danger">Cancelled</span>
                                            <?php else: ?>
                                            <form method="post" action="payments.php" style="display:inline;">
                                                <input type="hidden" name="payment_id" value="<?= $payment['Payment_ID'] ?>">
                                                <select
                                                    name="payment_status"
                                                    class="form-select payment-status-select
                                                        <?php
                                                            if (($payment['payment_status'] ?? '') === 'Unpaid') echo ' bg-warning text-dark';
                                                            elseif (($payment['payment_status'] ?? '') === 'Paid') echo ' bg-success text-white';
                                                        ?>"
                                                    onchange="this.form.submit()"
                                                    style="min-width:100px;"
                                                >
                                                    <?php
                                                    $pstatuses = ['Unpaid', 'Paid'];
                                                    foreach ($pstatuses as $status) {
                                                        $selected = ($payment['payment_status'] ?? '') === $status ? 'selected' : '';
                                                        echo "<option value=\"$status\" $selected>$status</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                        <!-- <td>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                        </td> -->
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination (optional) -->
                        <nav>
                            <ul class="pagination justify-content-end">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
        <div id="notifToastContainer"></div>
    </div>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

document.querySelectorAll('.payment-status-select').forEach(function(select) {
    select.addEventListener('change', function() {
        select.classList.remove('bg-warning', 'bg-success', 'text-dark', 'text-white');
        if (select.value === 'Unpaid') {
            select.classList.add('bg-warning', 'text-dark');
        } else if (select.value === 'Paid') {
            select.classList.add('bg-success', 'text-white');
        }
    });
});

// Notification polling and toast display
(function(){
    const container = document.getElementById('notifToastContainer');
    if (!container) return;

    function showToast(title, message) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
        <div class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="7000">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br/>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>`;
        const toastEl = wrap.firstElementChild;
        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        return toastEl;
    }

    async function poll() {
        try {
            const res = await fetch('ajax/notifications_list.php', { cache: 'no-store' });
            if (!res.ok) throw new Error('net');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data) && data.data.length) {
                const ids = [];
                data.data.forEach(n => {
                    ids.push(n.Notification_ID);
                    showToast(n.Title || 'Notification', n.Message || '');
                });
                // Mark read
                fetch('ajax/notifications_mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids })
                }).catch(()=>{});
            }
        } catch (e) {
            // ignore
        } finally {
            setTimeout(poll, 6000);
        }
    }
    poll();
})();
    </script>
</body>
</html>