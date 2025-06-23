<?php
// Start session to check if user is logged in
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'sidebar_counts.php';

// Include the database class
require_once('classes/database.php');

// Initialize variables
$success = '';
$error = '';
$admin_name = '';
$admin_email = '';
$admin_role = '';
$status = 'Active';

// Process add admin form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_role = $_POST['admin_role'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validate inputs
    if (empty($admin_name) || empty($admin_email) || empty($admin_password) || empty($admin_role)) {
        $error = "All fields are required!";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($admin_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $db = new database();
        $result = $db->addAdmin($admin_name, $admin_email, $admin_password, $admin_role, $status);
        if ($result['success']) {
            $_SESSION['success'] = "Admin added successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// After processing the form submission, before the HTML
$db = new database();
$admins = $db->getAllAdmins();
$stats = $db->getAdminStats();
$total_admins = $stats['total'];
$active_admins = $stats['active'];
$inactive_admins = $stats['inactive'];
$super_admins = $stats['super'];

$sales = [];
try {
    $db = new database();
    $sales = $db->viewSales();
} catch (PDOException $e) {
    // Optionally handle error
    $sales = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management System</title>
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
            <div class="col-md-2 col-lg-2 sidebar collapse d-lg-block" id="sidebarCollapse">
                <div class="pt-3">
                    <div class="d-flex align-items-center mb-4 px-3">
                        <div class="bg-white p-2 rounded me-2">
                            <i class="bi bi-shield-lock text-primary fs-4"></i>
                        </div>
                        <div class="logo-text fw-bold fs-5">AdminPanel</div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
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
                            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? ' active' : '' ?>" href="orders.php">
                                <i class="bi bi-cart4"></i>
                                <span>Orders</span>
                                <?php if ($pendingProcessingCount > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $pendingProcessingCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? ' active' : '' ?>" href="payments.php">
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
                <!-- Header -->
                <div class="header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold">Admin Management</h4>
                        <p class="mb-0 text-muted">Manage administrators and their roles</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="search-container me-3">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" placeholder="Search admins...">
                        </div>
                        <button id="scrollToAddAdminBtn" class="btn btn-primary d-flex align-items-center">
                            <i class="bi bi-plus-lg me-2"></i> Add Admin
                        </button>
                    </div>
                </div>
                
                <!-- Add this button inside your .header div, preferably at the start or left side -->
                <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list" style="font-size:1.7rem;"></i>
                </button>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-people-fill"></i>
                            <div class="number"><?= $total_admins ?></div>
                            <div class="label">Total Admins</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-person-check"></i>
                            <div class="number"><?= $active_admins ?></div>
                            <div class="label">Active Admins</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-person-x"></i>
                            <div class="number"><?= $inactive_admins ?></div>
                            <div class="label">Inactive Admins</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-shield-lock"></i>
                            <div class="number"><?= $super_admins ?></div>
                            <div class="label">Super Admins</div>
                        </div>
                    </div>
                </div>

                <!-- Add this button above your sales chart card -->
                <a href="export_sales_pdf.php" class="btn btn-outline-primary mb-2" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Export Sales to PDF
                </a>

                <!-- Recent Sales Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="bi bi-bar-chart"></i> Recent Sales
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>

                                <!-- Sales Stats Cards -->
                <?php
$total_sales = count($sales);
$total_revenue = array_sum(array_column($sales, 'Total_Amount'));
?>
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="bi bi-cash-stack"></i>
            <div class="number"><?= $total_sales ?></div>
            <div class="label">Total Sales</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="bi bi-currency-dollar"></i>
            <div class="number">₱<?= number_format($total_revenue, 2) ?></div>
            <div class="label">Total Revenue</div>
        </div>
    </div>
</div>
                
                <!-- Admin List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Administrators List</span>
                        <div>
                            <select class="form-select form-select-sm">
                                <option>All Roles</option>
                                <option>Super Admin</option>
                                <option>Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="admin-avatar me-3"><?= strtoupper(substr($admin['Admin_Name'], 0, 2)) ?></div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($admin['Admin_Name']) ?></div>
                                                    <div class="text-muted small">ID: <?= htmlspecialchars($admin['Admin_ID']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($admin['Admin_Email']) ?></td>
                                        <td><span class="role-badge"><?= htmlspecialchars($admin['Admin_Role']) ?></span></td>
                                        <td><span class="status-badge <?= $admin['Status'] === 'Active' ? 'status-active' : 'status-inactive' ?>"><?= htmlspecialchars($admin['Status']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($admin['Created_At'])) ?></td>
                                        <td>
                                            <a href="#" class="action-btn edit-admin-btn"
                                               data-id="<?= $admin['Admin_ID'] ?>"
                                               data-name="<?= htmlspecialchars($admin['Admin_Name']) ?>"
                                               data-email="<?= htmlspecialchars($admin['Admin_Email']) ?>"
                                               data-role="<?= htmlspecialchars($admin['Admin_Role']) ?>"
                                               data-status="<?= htmlspecialchars($admin['Status']) ?>">
                                               <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="action-btn delete-admin-btn" data-admin-id="<?= $admin['Admin_ID'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
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
                
                <!-- Add Admin Form -->
                <div class="card" id="addAdminFormSection">
                    <div class="card-header">
                        Add New Administrator
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="admin_name" class="form-control" placeholder="Enter full name" value="<?= htmlspecialchars($admin_name) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="admin_email" class="form-control" placeholder="Enter email" value="<?= htmlspecialchars($admin_email) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="password-container">
                                        <input type="password" name="admin_password" id="admin_password" class="form-control" placeholder="Create password"
    pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$"
    title="At least 6 characters, one uppercase, one number, one special character">
                                        <span class="password-toggle" onclick="togglePassword('admin_password')">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="password-container">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password">
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Admin Role <span class="text-danger">*</span></label>
                                    <select name="admin_role" class="form-select">
                                        <option value="" <?= $admin_role === '' ? 'selected' : '' ?>>Select role</option>
                                        <option value="Super Admin" <?= $admin_role === 'Super Admin' ? 'selected' : '' ?>>Super Admin</option>
                                        <option value="Manager" <?= $admin_role === 'Manager' ? 'selected' : '' ?>>Manager</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <div class="d-flex">
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="radio" name="status" id="active" value="Active" <?= $status === 'Active' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="active">Active</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" id="inactive" value="Inactive" <?= $status === 'Inactive' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="inactive">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="add_admin" class="btn btn-primary px-4">Add Administrator</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Edit Admin Modal -->
                <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <form id="editAdminForm" method="POST" action="update_admin.php">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="editAdminModalLabel">Edit Administrator</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="edit_admin_id" id="edit_admin_id">
                          <div class="row">
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Full Name <span class="text-danger">*</span></label>
                              <input type="text" name="edit_admin_name" id="edit_admin_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Email Address <span class="text-danger">*</span></label>
                              <input type="email" name="edit_admin_email" id="edit_admin_email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Admin Role <span class="text-danger">*</span></label>
                              <select name="edit_admin_role" id="edit_admin_role" class="form-select" required>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Manager">Manager</option>
               
                              </select>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Status <span class="text-danger">*</span></label>
                              <div class="d-flex">
                                <div class="form-check me-3">
                                  <input class="form-check-input" type="radio" name="edit_status" id="edit_status_active" value="Active">
                                  <label class="form-check-label" for="edit_status_active">Active</label>
                                </div>
                                <div class="form-check">
                                  <input class="form-check-input" type="radio" name="edit_status" id="edit_status_inactive" value="Inactive">
                                  <label class="form-check-label" for="edit_status_inactive">Inactive</label>
                                </div>
                              </div>
                            </div>
                            <div class="col-12 mb-2">
                              <small class="text-muted">Leave password fields blank to keep the current password.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">New Password</label>
                              <div class="password-container">
                                <input type="password" name="edit_admin_password" id="edit_admin_password" class="form-control" placeholder="New password">
                                <span class="password-toggle" onclick="togglePassword('edit_admin_password')">
                                  <i class="bi bi-eye"></i>
                                </span>
                              </div>
                            </div>
                            <div class="col-md-6 mb-3">
                              <label class="form-label">Confirm New Password</label>
                              <div class="password-container">
                                <input type="password" name="edit_confirm_password" id="edit_confirm_password" class="form-control" placeholder="Confirm new password">
                                <span class="password-toggle" onclick="togglePassword('edit_confirm_password')">
                                  <i class="bi bi-eye"></i>
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Update Administrator</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
                <!-- End Edit Admin Modal -->
                

                

            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            // Find the icon inside the same password-container
            const container = passwordInput.closest('.password-container');
            const toggleIcon = container.querySelector('.password-toggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Edit button click handler (CORRECTED)
        document.querySelectorAll('.edit-admin-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                // Get admin data from data attributes (CORRECTED)
                const adminId = this.getAttribute('data-id');
                const adminName = this.getAttribute('data-name');
                const adminEmail = this.getAttribute('data-email');
                const adminRole = this.getAttribute('data-role');
                const adminStatus = this.getAttribute('data-status');

                // Populate the form
                document.getElementById('edit_admin_id').value = adminId;
                document.getElementById('edit_admin_name').value = adminName;
                document.getElementById('edit_admin_email').value = adminEmail;
                document.getElementById('edit_admin_role').value = adminRole;

                // Set status radio button
                if (adminStatus === 'Active') {
                    document.getElementById('edit_status_active').checked = true;
                } else {
                    document.getElementById('edit_status_inactive').checked = true;
                }

                // Clear password fields
                document.getElementById('edit_admin_password').value = '';
                document.getElementById('edit_confirm_password').value = '';

                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                editModal.show();
            });
        });

        // Handle edit form submission via AJAX
        document.getElementById('editAdminForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('update_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
                    editModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Admin updated successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to update admin.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the admin.');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="admin_email"]');
            const emailWarning = document.createElement('div');
            emailWarning.className = 'text-danger mb-2';
            emailInput.parentNode.appendChild(emailWarning);

            emailInput.addEventListener('blur', function() {
                const email = emailInput.value.trim();
                if (email) {
                    fetch('ajax/check_email.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'email=' + encodeURIComponent(email)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            emailWarning.textContent = 'This email is already taken.';
                        } else {
                            emailWarning.textContent = '';
                        }
                    });
                } else {
                    emailWarning.textContent = '';
                }
            });

            // Prevent form submission if email is taken
            const addAdminForm = document.querySelector('form[action=""]');
            addAdminForm.addEventListener('submit', function(e) {
                if (emailWarning.textContent) {
                    e.preventDefault();
                    emailInput.focus();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-container input[type="text"]');
            const tbody = document.querySelector('table tbody');

            searchInput.addEventListener('input', function() {
                const keyword = this.value;
                fetch('ajax/search_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'keyword=' + encodeURIComponent(keyword)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear current rows
                        tbody.innerHTML = '';
                        // Add new rows
                        data.admins.forEach(admin => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="admin-avatar me-3">${admin.Admin_Name.substr(0,2).toUpperCase()}</div>
                                            <div>
                                                <div class="fw-bold">${admin.Admin_Name}</div>
                                                <div class="text-muted small">ID: ${admin.Admin_ID}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${admin.Admin_Email}</td>
                                    <td><span class="role-badge">${admin.Admin_Role}</span></td>
                                    <td><span class="status-badge ${admin.Status === 'Active' ? 'status-active' : 'status-inactive'}">${admin.Status}</span></td>
                                    <td>${new Date(admin.Created_At).toLocaleDateString()}</td>
                                    <td>
                                        <a href="#" class="action-btn edit-admin-btn"
                                           data-id="${admin.Admin_ID}"
                                           data-name="${admin.Admin_Name}"
                                           data-email="${admin.Admin_Email}"
                                           data-role="${admin.Admin_Role}"
                                           data-status="${admin.Status}">
                                           <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="#" class="action-btn delete-admin-btn" data-admin-id="${admin.Admin_ID}">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[name="admin_password"]');
            const confirmInput = document.querySelector('input[name="confirm_password"]');
            const addAdminForm = document.querySelector('form[action=""]');

            // Create warning element if not exists
            let pwWarning = document.getElementById('pw-warning');
            if (!pwWarning) {
                pwWarning = document.createElement('div');
                pwWarning.className = 'text-danger mb-2';
                pwWarning.id = 'pw-warning';
                confirmInput.parentNode.appendChild(pwWarning);
            }

            // Password regex
            const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/;

            function checkPasswords() {
                if (passwordInput.value && !passwordRegex.test(passwordInput.value)) {
                    pwWarning.textContent = 'Password must be at least 6 characters, include an uppercase letter, a number, and a special character.';
                    return false;
                } else if (passwordInput.value && confirmInput.value && passwordInput.value !== confirmInput.value) {
                    pwWarning.textContent = 'Passwords do not match.';
                    return false;
                } else {
                    pwWarning.textContent = '';
                    return true;
                }
            }

            passwordInput.addEventListener('input', checkPasswords);
            confirmInput.addEventListener('input', checkPasswords);

            addAdminForm.addEventListener('submit', function(e) {
                if (!checkPasswords()) {
                    e.preventDefault();
                    confirmInput.focus();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('table tbody');
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-admin-btn');
        if (btn) {
            e.preventDefault();
            const adminId = btn.getAttribute('data-admin-id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the admin.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('ajax/delete_admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'admin_id=' + encodeURIComponent(adminId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Network error: ' + error.message, 'error');
                    });
                }
            });
        }
    });
});

<?php if ($success): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: <?= json_encode($success) ?>,
        timer: 2500,
        showConfirmButton: false
    });
});
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: <?= json_encode($error) ?>,
        timer: 3500,
        showConfirmButton: false
    });
});
</script>
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    const scrollBtn = document.getElementById('scrollToAddAdminBtn');
    const formSection = document.getElementById('addAdminFormSection');
    if (scrollBtn && formSection) {
        scrollBtn.addEventListener('click', function(e) {
            e.preventDefault();
            formSection.scrollIntoView({ behavior: 'smooth' });
        });
    }
});

// Sales chart
document.addEventListener('DOMContentLoaded', function() {
    const salesData = <?= json_encode($sales) ?>;
const labels = salesData.map(sale => sale.Sale_Date.substr(0, 10)); // e.g. '2024-06-18'
const amounts = salesData.map(sale => parseFloat(sale.Total_Amount));

const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar', // or 'line'
    data: {
        labels: labels,
        datasets: [{
            label: 'Total Amount',
            data: amounts,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            x: { title: { display: true, text: 'Date' } },
            y: { title: { display: true, text: 'Sales (₱)' }, beginAtZero: true }
        }
    }
});
});
    </script>
</body>
</html>