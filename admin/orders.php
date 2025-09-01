
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');

$db = new database();

// Fetch all orders
$error = '';
try {
    $orders = $db->fetchOrders();
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// Derived display status helper
function admin_display_status($row) {
    if (isset($row['Driver_Status']) && in_array($row['Driver_Status'], ['on_the_way','picked_up'], true)) {
        return 'Out for delivery';
    }
    if (!empty($row['order_status']) && $row['order_status'] === 'Processing') {
        return 'Preparing';
    }
    return $row['order_status'] ?? 'Pending';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_status'], $_POST['order_id'])) {
        $db->updateOrderStatus($_POST['order_id'], $_POST['order_status']);
    }

    if (isset($_POST['payment_status'], $_POST['order_id'])) {
        $db->updatePaymentStatusByOrder($_POST['order_id'], $_POST['payment_status']);
    }
    // ...existing code...
}
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
                <div class="card mt-3">
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
                                        <th>Status</th>
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
                                            <?= htmlspecialchars($db->getCustomerNameById($order['Customer_ID'])) ?>
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
                                        <!-- Derived Display Status -->
                                        <td><?= htmlspecialchars(admin_display_status($order)) ?></td>
                                        <!-- Order Status (editable) -->
                                        <td>
                                            <form method="post" action="orders.php" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>">
                                                <select
                                                    name="order_status"
                                                    class="form-select order-status-select
                                                        <?php
                                                            if ($order['order_status'] === 'Pending') echo ' bg-warning text-dark';
                                                            elseif ($order['order_status'] === 'Processing') echo ' bg-info text-dark';
                                                            elseif ($order['order_status'] === 'Delivered') echo ' bg-success text-white';
                                                            elseif ($order['order_status'] === 'Cancelled') echo ' bg-danger text-white';
                                                        ?>"
                                                    onchange="this.form.submit()"
                                                    style="min-width:150px;"
                                                >
                                                    <?php
                                                    // Allowed options shown; backend still enforces rules
                                                    $options = ['Pending', 'Processing', 'Delivered', 'Cancelled'];
                                                    foreach ($options as $opt) {
                                                        $selected = $order['order_status'] === $opt ? 'selected' : '';
                                                        echo "<option value=\"$opt\" $selected>$opt</option>";
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

    document.querySelectorAll('.order-status-select').forEach(function(select) {
        select.addEventListener('change', function() {
            select.classList.remove('bg-warning', 'bg-info', 'bg-success', 'bg-danger', 'text-dark', 'text-white');
            if (select.value === 'Pending') {
                select.classList.add('bg-warning', 'text-dark');
            } else if (select.value === 'Processing') {
                select.classList.add('bg-info', 'text-dark');
            } else if (select.value === 'Delivered') {
                select.classList.add('bg-success', 'text-white');
            } else if (select.value === 'Cancelled') {
                select.classList.add('bg-danger', 'text-white');
            }
        });
    });
</script>
</body>
</html>