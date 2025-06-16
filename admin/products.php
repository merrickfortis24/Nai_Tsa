<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');

// Fetch all products
$products = [];
$error = '';
try {
    $db = new database();
    $conn = $db->opencon();
    $stmt = $conn->prepare("SELECT p.*, pp.Price_Amount
FROM product p
LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
ORDER BY p.Created_at DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// Fetch all categories for the dropdown
$categories_list = [];
try {
    $stmt = $conn->prepare("SELECT Category_ID, Category_Name FROM category ORDER BY Category_Name ASC");
    $stmt->execute();
    $categories_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optionally handle error
}

// Fetch all prices for the dropdown
$prices_list = [];
try {
    $stmt = $conn->prepare("SELECT Price_ID, Price_Amount FROM product_price ORDER BY Price_ID ASC");
    $stmt->execute();
    $prices_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optionally handle error
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
                            <a class="nav-link active" href="products.php">
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
                        <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-lg me-2"></i> Add Product
                        </button>
                    </div>
                </div>
                <!-- Stats Cards for Products -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <i class="bi bi-box-seam"></i>
                            <div class="number"><?= count($products) ?></div>
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
                                <!-- You can populate categories here if needed -->
                                <?php foreach ($categories_list as $category): ?>
                                    <option value="<?= htmlspecialchars($category['Category_ID']) ?>"><?= htmlspecialchars($category['Category_Name']) ?></option>
                                <?php endforeach; ?>
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
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Admin ID</th>
                                        <th>Category ID</th>
                                        <th>Price ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['Product_Image'])): ?>
                                                <img src="uploads/products/<?= htmlspecialchars($product['Product_Image']) ?>" alt="Product Image" style="width:45px; height:45px; object-fit:cover; border-radius:8px;">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($product['Product_Name']) ?></td>
                                        <td><?= htmlspecialchars($product['Product_desc']) ?></td>
                                        <td><?= htmlspecialchars($product['Created_at']) ?></td>
                                        <td><?= htmlspecialchars($product['Updated_at']) ?></td>
                                        <td><?= htmlspecialchars($product['Admin_ID']) ?></td>
                                        <td><?= htmlspecialchars($product['Category_ID']) ?></td>
                                        <td><?= htmlspecialchars($product['Price_ID']) ?></td>
                                        <td>
                                            <a href="#" class="action-btn"><i class="bi bi-pencil"></i></a>
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
                <!-- End Products List -->
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="product_name" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="product_name" name="product_name" required>
            </div>
            <div class="mb-3">
              <label for="product_desc" class="form-label">Description</label>
              <textarea class="form-control" id="product_desc" name="product_desc" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label for="product_image" class="form-label">Product Image</label>
              <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
            </div>
            <div class="mb-3">
              <label for="category_id" class="form-label">Category</label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories_list as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['Category_ID']) ?>">
                    <?= htmlspecialchars($cat['Category_Name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="price_id" class="form-label">Price</label>
              <select class="form-select" id="price_id" name="price_id" required>
                <option value="">Select Price</option>
                <?php foreach ($prices_list as $price): ?>
                  <option value="<?= htmlspecialchars($price['Price_ID']) ?>">
                    <?= htmlspecialchars($price['Price_Amount']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Add more fields as needed -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Product</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var form = this;
            var formData = new FormData(form);

            fetch('ajax/add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Product Added',
                        text: data.message
                    }).then(() => {
                        // Hide modal and reload page to show new product
                        var modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                        modal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while adding the product.'
                });
            });
        });
    </script>
</body>
</html>