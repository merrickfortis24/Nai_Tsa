<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');
include 'sidebar_counts.php'; // This makes $pendingProcessingCount and $unpaidPayments available

$db = new database();

$error = '';
try {
    $products = $db->getAllProducts();
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

try {
    $categories_list = $db->getAllCategories();
} catch (PDOException $e) {
    $categories_list = [];
}

try {
    $prices_list = $db->getAllPrices();
} catch (PDOException $e) {
    $prices_list = [];
}

// Pagination logic
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 10; // Adjust as needed
$categoryFilter = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;

$totalProducts = $db->getProductsCount($categoryFilter);
$totalPages = ceil($totalProducts / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

$products = $db->getAllProducts($itemsPerPage, $offset, $categoryFilter);
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
      <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
        <?php include 'sidebar.php'; ?>
      </div>
      <!-- Main Content -->
      <div class="col-md-10 col-lg-10 main-content">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center mt-3">
          <div>
            <h4 class="mb-0 fw-bold">Products</h4>
            <p class="mb-0 text-muted">Manage your products</p>
          </div>
          <!-- Sidebar toggle button for small screens -->
          <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list" style="font-size:1.7rem;"></i>
          </button>
        </div>

        <!-- Products List Card -->
        <div class="card mt-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Products List</span>
            <div>
              <form method="get" class="d-inline">
                <select class="form-select form-select-sm" name="category" onchange="this.form.submit()">
                  <option value="">All Categories</option>
                  <?php foreach ($categories_list as $category): ?>
                    <option value="<?= htmlspecialchars($category['Category_ID']) ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['Category_ID']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($category['Category_Name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($_GET['page'])): ?>
                  <input type="hidden" name="page" value="<?= (int)$_GET['page'] ?>">
                <?php endif; ?>
              </form>
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
                    <th><strong>Allergens</strong></th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Admin Name</th>
                    <th>Category</th>
                    <th>Price</th>
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
                    <td><?= htmlspecialchars($product['Product_allergens'] ?? '') ?></td>
                    <td><?= date('F d, Y h:i A', strtotime($product['Created_at'])) ?></td>
                    <td><?= date('F d, Y h:i A', strtotime($product['Updated_at'])) ?></td>
                    <td><?= htmlspecialchars($product['Admin_Name']) ?></td>
                    <td><?= htmlspecialchars($product['Category_Name']) ?></td>
                    <td><?= htmlspecialchars($product['Price_Amount']) ?></td>
                    <td>
                      <a href="#" class="action-btn edit-product-btn"
                         data-product-id="<?= htmlspecialchars($product['Product_ID']) ?>"
                         data-product-name="<?= htmlspecialchars($product['Product_Name']) ?>"
                         data-product-desc="<?= htmlspecialchars($product['Product_desc']) ?>"
                         data-category-id="<?= htmlspecialchars($product['Category_ID']) ?>"
                         data-price-id="<?= htmlspecialchars($product['Price_ID']) ?>"
                         data-image="<?= htmlspecialchars($product['Product_Image']) ?>"
                         data-effective-from="<?= htmlspecialchars($product['Effective_From'] ?? '') ?>"
                         data-effective-to="<?= htmlspecialchars($product['Effective_To'] ?? '') ?>">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="#" class="action-btn delete-product-btn" data-product-id="<?= htmlspecialchars($product['Product_ID']) ?>">
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
                <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $currentPage == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>">Next</a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
        <!-- End Products List Card -->
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
                    (
                      <?= date('F d, Y', strtotime($price['Effective_From'])) ?>
                      <?php if ($price['Effective_To']): ?>
                        to <?= date('F d, Y', strtotime($price['Effective_To'])) ?>
                      <?php else: ?>
                        and onwards
                      <?php endif; ?>
                    )
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="Product_Allergens" class="form-label">Allergen Content</label>
              <select class="form-select" id="Product_Allergens" name="Product_Allergens[]" multiple>
                <option value="Milk">Milk</option>
                <option value="Eggs">Eggs</option>
                <option value="Peanuts">Peanuts</option>
                <option value="Soy">Soy</option>
                <option value="Wheat">Wheat</option>
                <option value="Tree nuts">Tree nuts</option>
                <option value="Fish">Fish</option>
                <option value="Shellfish">Shellfish</option>
                <!-- Add more as needed -->
              </select>
              <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</div>
            </div>

            <input type="hidden" id="product_id" name="product_id" value="">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Product</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Price Modal -->
    <div class="modal fade" id="addPriceModal" tabindex="-1" aria-labelledby="addPriceModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" id="addPriceForm" action="ajax/add_price.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="addPriceModalLabel">Add Price</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="price_amount" class="form-label">Price Amount</label>
              <input type="text" pattern="^\d+(\.\d{1,2})?$" class="form-control" id="price_amount" name="price_amount" required>
            </div>
            <div class="mb-3">
              <label for="effective_from" class="form-label">Effective From</label>
              <input type="date" class="form-control" id="effective_from" name="effective_from" required>
            </div>
            <div class="mb-3">
              <label for="effective_to" class="form-label">Effective To</label>
              <input type="date" class="form-control" id="effective_to" name="effective_to">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Price</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
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

    document.getElementById('addPriceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        fetch('ajax/add_price.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Price Added',
                    text: data.message
                }).then(() => {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addPriceModal'));
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
                text: 'An error occurred while adding the price.'
            });
        });
    });

    document.querySelectorAll('.edit-product-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('product_name').value = this.dataset.productName;
            document.getElementById('product_desc').value = this.dataset.productDesc;
            document.getElementById('category_id').value = this.dataset.categoryId;
            document.getElementById('price_id').value = this.dataset.priceId;
            document.getElementById('product_id').value = this.dataset.productId;
            document.getElementById('addProductModalLabel').innerText = 'Edit Product';
            document.querySelector('#addProductForm button[type="submit"]').innerText = 'Update Product';
            var modalEl = document.getElementById('addProductModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    });

    document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addProductModalLabel').innerText = 'Add Product';
        document.querySelector('#addProductForm button[type="submit"]').innerText = 'Add Product';
        document.getElementById('addProductForm').reset();
        document.getElementById('product_id').value = '';
    });

    document.querySelectorAll('.delete-product-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the product.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('ajax/delete_product.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'product_id=' + encodeURIComponent(productId)
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
        });
    });
});
    </script>
</body>
</html>