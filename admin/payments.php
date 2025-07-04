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
                <div class="pt-3">
                    <div class="d-flex align-items-center mb-4 px-3">
                        <div class="bg-white p-2 rounded me-2">
                            <i class="bi bi-shield-lock text-primary fs-4"></i>
                        </div>
                        <div class="logo-text fw-bold fs-5">AdminPanel</div>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admins.php">
                                <i class="bi bi-people-fill"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="bi bi-cart4"></i>
                                <span>Orders</span>
                                <!-- Orders badge (optional, see below for how to count) -->
                                <?php if (!empty($pendingOrders) && $pendingOrders > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $pendingOrders ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="payments.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Payments</span>
                                <?php if ($unpaidPayments > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $unpaidPayments ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="bi bi-box-seam"></i>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="bi bi-tags"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
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
                                    <tr>
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
    </script>
</body>
</html>