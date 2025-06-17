<?php
// Start session to check if user is logged in
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

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
        try {
            // Hash password
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

            // Create database connection
            $db = new database();
            $conn = $db->opencon();

            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO Admin (Admin_Name, Admin_Password, Admin_Email, Admin_Role, Created_At, Status) 
                                   VALUES (:name, :password, :email, :role, NOW(), :status)");

            // Bind parameters
            $stmt->bindParam(':name', $admin_name);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $admin_email);
            $stmt->bindParam(':role', $admin_role);
            $stmt->bindParam(':status', $status);

            // Execute the query
            if ($stmt->execute()) {
                $success = "Admin added successfully!";
                // Reset form fields
                $admin_name = '';
                $admin_email = '';
                $admin_role = '';
            } else {
                $error = "Error adding admin: " . implode(" ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// After processing the form submission, before the HTML
$admins = [];
$total_admins = 0;
$active_admins = 0;
$inactive_admins = 0;
$super_admins = 0;

try {
    $db = new database();
    $conn = $db->opencon();

    // Get all admins
    $stmt = $conn->prepare("SELECT * FROM Admin ORDER BY Created_At DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total admins
    $stmt_total = $conn->query("SELECT COUNT(*) FROM Admin");
    $total_admins = $stmt_total->fetchColumn();

    // Get active admins
    $stmt_active = $conn->query("SELECT COUNT(*) FROM Admin WHERE Status = 'Active'");
    $active_admins = $stmt_active->fetchColumn();

    // Get inactive admins
    $stmt_inactive = $conn->query("SELECT COUNT(*) FROM Admin WHERE Status = 'Inactive'");
    $inactive_admins = $stmt_inactive->fetchColumn();

    // Get super admins
    $stmt_super = $conn->query("SELECT COUNT(*) FROM Admin WHERE Admin_Role = 'Super Admin'");
    $super_admins = $stmt_super->fetchColumn();

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
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
                            <a class="nav-link" href="staff.php">
                                <i class="bi bi-person-badge"></i>
                                <span>Staff</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
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
                        <button class="btn btn-primary d-flex align-items-center">
                            <i class="bi bi-plus-lg me-2"></i> Add Admin
                        </button>
                    </div>
                </div>
                
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
                
                <!-- Admin List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Administrators List</span>
                        <div>
                            <select class="form-select form-select-sm">
                                <option>All Roles</option>
                                <option>Super Admin</option>
                                <option>Manager</option>
                                <option>Staff</option>
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
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
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
                <div class="card">
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
                                        <input type="password" name="admin_password" id="admin_password" class="form-control" placeholder="Create password">
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
                                        <option value="Staff" <?= $admin_role === 'Staff' ? 'selected' : '' ?>>Staff</option>
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
                                    <button type="submit" class="btn btn-primary px-4">Add Administrator</button>
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
                                <option value="Staff">Staff</option>
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
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
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
                    // Close modal and reload page
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editAdminModal'));
                    editModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the admin.');
            });
        });
    </script>
</body>
</html>