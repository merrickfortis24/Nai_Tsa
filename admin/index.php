<?php
// Include the database class
require_once('classes/database.php');

// Initialize variables
$success = '';
$error = '';
$admin_name = '';
$admin_email = '';
$admin_role = '';
$status = 'Active';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            height: 100vh;
            position: fixed;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            border-radius: 5px;
            margin: 5px 0;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(76, 201, 240, 0.15);
            color: #4cc9f0;
        }
        
        .status-inactive {
            background-color: rgba(247, 37, 133, 0.15);
            color: #f72585;
        }
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-container input {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        
        .table th {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            margin: 0 3px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .role-badge {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px 15px;
        }
        
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .stats-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar .logo-text, .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link {
                text-align: center;
                padding: 15px 5px;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar {
                width: 0;
                display: none;
            }
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
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
                            <a class="nav-link" href="#">
                                <i class="bi bi-people-fill"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-person-badge"></i>
                                <span>Staff</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-cart4"></i>
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-credit-card"></i>
                                <span>Payments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-box-seam"></i>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-tags"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="#">
                                <i class="bi bi-gear"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
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
                                               data-edit='<?= json_encode($admin, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
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
                    <form method="POST" id="editAdminForm">
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
        
        // Populate edit admin modal
        const editAdminModal = document.getElementById('editAdminModal');
        editAdminModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const adminId = button.getAttribute('data-admin-id');
            const adminName = button.getAttribute('data-admin-name');
            const adminEmail = button.getAttribute('data-admin-email');
            const adminRole = button.getAttribute('data-admin-role');
            const status = button.getAttribute('data-status');
            
            const modalTitle = editAdminModal.querySelector('.modal-title');
            const adminIdField = editAdminModal.querySelector('#edit_admin_id');
            const adminNameField = editAdminModal.querySelector('#edit_admin_name');
            const adminEmailField = editAdminModal.querySelector('#edit_admin_email');
            const adminRoleField = editAdminModal.querySelector('#edit_admin_role');
            const statusActiveField = editAdminModal.querySelector('#edit_status_active');
            const statusInactiveField = editAdminModal.querySelector('#edit_status_inactive');
            
            modalTitle.textContent = `Edit Administrator - ${adminName}`;
            adminIdField.value = adminId;
            adminNameField.value = adminName;
            adminEmailField.value = adminEmail;
            adminRoleField.value = adminRole;
            statusActiveField.checked = (status === 'Active');
            statusInactiveField.checked = (status === 'Inactive');
        });
        
        // Edit button click handler
        document.querySelectorAll('.edit-admin-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const admin = JSON.parse(this.getAttribute('data-edit'));
                document.getElementById('edit_admin_id').value = admin.Admin_ID;
                document.getElementById('edit_admin_name').value = admin.Admin_Name;
                document.getElementById('edit_admin_email').value = admin.Admin_Email;
                document.getElementById('edit_admin_role').value = admin.Admin_Role;
                if (admin.Status === 'Active') {
                    document.getElementById('edit_status_active').checked = true;
                } else {
                    document.getElementById('edit_status_inactive').checked = true;
                }
                // Clear password fields
                document.getElementById('edit_admin_password').value = '';
                document.getElementById('edit_confirm_password').value = '';
                // Show modal
                var editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                editModal.show();
            });
        });
    </script>
</body>
</html>