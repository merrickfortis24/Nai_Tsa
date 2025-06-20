<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');

// Fetch all orders
$orders = [];
$error = '';
try {
    $db = new database();
    $orders = $db->fetchOrders();
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('classes/database.php');
    $db = new database();
    $con = $db->opencon();

    if (isset($_POST['order_status'], $_POST['order_id'])) {
        $stmt = $con->prepare("UPDATE orders SET order_status=? WHERE Order_ID=?");
        $stmt->execute([$_POST['order_status'], $_POST['order_id']]);
    }
    if (isset($_POST['payment_status'], $_POST['order_id'])) {
        $stmt = $con->prepare("UPDATE payment SET payment_status=? WHERE Order_ID=?");
        $stmt->execute([$_POST['payment_status'], $_POST['order_id']]);
    }
    header("Location: orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Admin Panel</title>
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
                            <a class="nav-link active" href="orders.php">
                                <i class="bi bi-cart4"></i>
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">
                                <i class="bi bi-credit-card"></i>
                                <span>Payments</span>
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
                        <h4 class="mb-0 fw-bold">Orders</h4>
                        <p class="mb-0 text-muted">List of all orders</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Orders List</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <!-- <th>Order ID</th> --> <!-- Removed Order ID column -->
                                        <th>Customer Name</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Street</th>
                                        <th>Barangay</th>
                                        <th>City</th>
                                        <th>Contact Number</th>
                                        <th>Order Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <!-- Customer Name -->
                                        <td>
                                            <?php
                                            $customerName = '';
                                            try {
                                                $customerStmt = $db->opencon()->prepare("SELECT Customer_Name FROM customer WHERE Customer_ID = ?");
                                                $customerStmt->execute([$order['Customer_ID']]);
                                                $customerName = $customerStmt->fetchColumn();
                                            } catch (PDOException $e) {
                                                $customerName = 'Unknown';
                                            }
                                            echo htmlspecialchars($customerName);
                                            ?>
                                        </td>
                                        <!-- Order Date -->
                                        <td><?= date('F j, Y g:i A', strtotime($order['Order_Date'])) ?></td>
                                        <!-- Total -->
                                        <td>$<?= number_format($order['Order_Amount'], 2) ?></td>
                                        <!-- Address and Contact -->
                                        <td><?= htmlspecialchars($order['Street']) ?></td>
                                        <td><?= htmlspecialchars($order['Barangay']) ?></td>
                                        <td><?= htmlspecialchars($order['City']) ?></td>
                                        <td><?= htmlspecialchars($order['Contact_Number']) ?></td>
                                        <!-- Order Status -->
                                        <td>
                                            <form method="post" action="orders.php" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>">
                                                <select name="order_status" onchange="this.form.submit()">
                                                    <?php
                                                    $statuses = ['Pending', 'Processing', 'Delivered'];
                                                    foreach ($statuses as $status) {
                                                        $selected = $order['order_status'] === $status ? 'selected' : '';
                                                        echo "<option value=\"$status\" $selected>$status</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </td>
                                        <!-- Payment Status -->
                                        <td>
                                            <span class="badge <?= ($order['payment_status'] ?? '') === 'Paid' ? 'bg-success' : 'bg-secondary' ?>">
                <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
            </span>
                                        </td>
                                        <!-- Actions -->
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#orderItemsModal<?= $order['Order_ID'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
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
    <!-- Order Items Modals -->
    <?php foreach ($orders as $order): ?>
    <div class="modal fade" id="orderItemsModal<?= $order['Order_ID'] ?>" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Order #<?= $order['Order_ID'] ?> Items</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <ul>
              <?php
                $items = $db->fetchOrderItems($order['Order_ID']);
                foreach ($items as $item):
              ?>
                <li>
                  <?= htmlspecialchars($item['Product_Name']) ?> x <?= $item['Quantity'] ?> @ ₱<?= number_format($item['Price'], 2) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
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
    </script>
</body>
</html>