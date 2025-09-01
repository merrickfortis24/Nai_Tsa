<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');
include 'sidebar_counts.php';

$db = new database();

$error = '';
try {
    $admins = $db->getAllAdmins();
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admins | Admin Panel</title>
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
                        <h4 class="mb-0 fw-bold">Admins</h4>
                        <p class="mb-0 text-muted">List of all administrators</p>
                    </div>
                </div>
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
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
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
    </script>
</body>
</html>