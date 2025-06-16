<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management | Admin Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom Admin CSS -->
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
                            <a class="nav-link active" href="products.php">
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
                        <h4 class="mb-0 fw-bold">Products Management</h4>
                        <p class="mb-0 text-muted">Manage your products inventory</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="search-container me-3">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" placeholder="Search products...">
                        </div>
                        <button class="btn btn-primary d-flex align-items-center">
                            <i class="bi bi-plus-lg me-2"></i> Add Product
                        </button>
                    </div>
                </div>
                <!-- Stats Cards for Products -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-box-seam"></i>
                            <div class="number">120</div>
                            <div class="label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-check-circle"></i>
                            <div class="number">95</div>
                            <div class="label">In Stock</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-exclamation-circle"></i>
                            <div class="number">25</div>
                            <div class="label">Low Stock</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-x-circle"></i>
                            <div class="number">5</div>
                            <div class="label">Out of Stock</div>
                        </div>
                    </div>
                </div>
                <!-- Products List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Products List</span>
                        <div>
                            <select class="form-select form-select-sm">
                                <option>All Categories</option>
                                <option>Electronics</option>
                                <option>Clothing</option>
                                <option>Books</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 45px; height: 45px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-phone" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Smartphone X</div>
                                                    <div class="text-muted small">ID: P001</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Electronics</td>
                                        <td>$699.99</td>
                                        <td>45</td>
                                        <td><span class="status-badge status-active">In Stock</span></td>
                                        <td>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 45px; height: 45px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-laptop" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Laptop Pro</div>
                                                    <div class="text-muted small">ID: P002</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Electronics</td>
                                        <td>$1299.99</td>
                                        <td>22</td>
                                        <td><span class="status-badge status-active">In Stock</span></td>
                                        <td>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 45px; height: 45px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-tshirt" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Cotton T-Shirt</div>
                                                    <div class="text-muted small">ID: P003</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Clothing</td>
                                        <td>$19.99</td>
                                        <td>3</td>
                                        <td><span class="status-badge status-inactive">Low Stock</span></td>
                                        <td>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3" style="width: 45px; height: 45px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-book" style="font-size: 1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">The Great Novel</div>
                                                    <div class="text-muted small">ID: P004</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Books</td>
                                        <td>$24.99</td>
                                        <td>0</td>
                                        <td><span class="status-badge status-inactive">Out of Stock</span></td>
                                        <td>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-trash"></i></a>
                                            <a href="#" class="action-btn"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
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
                <!-- End Products List -->
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