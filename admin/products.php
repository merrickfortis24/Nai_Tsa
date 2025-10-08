<?php
session_start();
// Attempt to restore session from remember-me cookie if present
require_once __DIR__ . '/../includes/remember.php';
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
    <style>
      /* Clamp long descriptions to avoid tall rows / overflow */
      .prod-desc-clamp{max-width:260px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.25rem;font-size:.875rem;}
      @media (min-width:1400px){.prod-desc-clamp{max-width:340px;}}
    </style>
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
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <span class="fw-semibold">Products List</span>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="get" class="d-flex align-items-center gap-2 mb-0">
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
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                  <i class="bi bi-plus-circle me-1"></i> Add Product
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#manageSizesModal">
                  <i class="bi bi-arrows-expand me-1"></i> Manage Sizes
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#manageVariantsModal">
                  <i class="bi bi-sliders me-1"></i> Manage Variants (New)
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addPriceModal">
                  <i class="bi bi-cash-coin me-1"></i> Add Price
                </button>
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
                    <!-- Allergens column removed -->
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Admin Name</th>
                    <th>Category</th>
                    <th>Price / Size</th>
                    <th>Primary Size</th>
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
                    <td>
                      <?php 
                        $descRaw = trim($product['Product_desc'] ?? '');
                        if($descRaw===''){ echo '<span class="text-muted">—</span>'; }
                        else {
                          $safeFull = htmlspecialchars($descRaw, ENT_QUOTES, 'UTF-8');
                          echo '<div class="prod-desc-clamp" data-bs-toggle="tooltip" data-bs-title="'.$safeFull.'">'.$safeFull.'</div>';
                        }
                      ?>
                    </td>
                    <!-- Allergens data removed -->
                    <td><?= date('F d, Y h:i A', strtotime($product['Created_at'])) ?></td>
                    <td><?= date('F d, Y h:i A', strtotime($product['Updated_at'])) ?></td>
                    <td><?= htmlspecialchars($product['Admin_Name']) ?></td>
                    <td><?= htmlspecialchars($product['Category_Name']) ?></td>
                    <td>
                      <?php
                        $dispPrice = $product['Size_Display_Price'] ?? $product['Base_Price_Amount'] ?? $product['Price_Amount'] ?? null;
                        $mode = $product['Size_Display_Mode'] ?? null;
                        $code = $product['Size_Display_Code'] ?? null;
                        $fullName = $product['Size_Display_Name'] ?? $code;
                        $baseComponent = $product['Size_Display_Base'] ?? null;
                        $deltaComponent = $product['Size_Display_Delta'] ?? null;
                        if($dispPrice !== null){
                          if($mode === 'DELTA' && $baseComponent !== null && $deltaComponent !== null){
                            echo '<span class=\'fw-semibold\'>'.htmlspecialchars(number_format($baseComponent,2)).' + '.htmlspecialchars(number_format($deltaComponent,2)).' = '.htmlspecialchars(number_format($dispPrice,2)).'</span>';
                          } else {
                            echo '<span class=\'fw-semibold\'>'.htmlspecialchars(number_format((float)$dispPrice,2)).'</span>';
                          }
                          if($code){
                            $tooltip = htmlspecialchars(($fullName?:$code).' ('.($mode?:'BASE').')');
                            echo ' <span class="badge bg-secondary" data-bs-toggle="tooltip" data-bs-title="'.$tooltip.'">'.htmlspecialchars($code).'</span>';
                            if($mode==='DELTA'){
                              echo ' <span class="text-muted small">(base + delta)</span>';
                            }
                          }
                        } else {
                          echo '<span class="text-muted">n/a</span>';
                        }
                      ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-outline-primary set-primary-size-btn" data-product-id="<?= (int)$product['Product_ID'] ?>">Set</button>
                    </td>
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
            <!-- Allergens input removed -->

            <input type="hidden" id="product_id" name="product_id" value="">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Product</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Price Modal (restored) -->
    <div class="modal fade" id="addPriceModal" tabindex="-1" aria-labelledby="addPriceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form class="modal-content" id="addPriceForm">
          <div class="modal-header py-2">
            <h6 class="modal-title" id="addPriceModalLabel">Add Price</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="mb-2">
                  <label class="form-label small mb-1">Amount (₱)</label>
                  <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="price_amount" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small mb-1">Effective From</label>
                  <input type="date" class="form-control form-control-sm" name="effective_from" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small mb-1">Effective To (optional)</label>
                  <input type="date" class="form-control form-control-sm" name="effective_to">
                  <div class="form-text small">Leave blank for open-ended pricing.</div>
                </div>
                <div class="alert alert-info p-2 small mb-0">New prices appear in dropdowns instantly.</div>
              </div>
              <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong class="small mb-0">Existing Prices</strong>
                  <div class="btn-group btn-group-sm" role="group" aria-label="Pagination" id="pricePager" data-page="1">
                    <button type="button" class="btn btn-outline-secondary" id="pricePrev" disabled>&laquo;</button>
                    <button type="button" class="btn btn-outline-secondary" id="priceNext" disabled>&raquo;</button>
                  </div>
                </div>
                <div class="table-responsive border rounded" style="max-height:300px; overflow:auto;">
                  <table class="table table-sm mb-0 align-middle" id="priceListTable">
                    <thead class="table-light sticky-top">
                      <tr>
                        <th style="width:70px;">ID</th>
                        <th>Amount</th>
                        <th>Effective Range</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><td colspan="3" class="text-center small text-muted">Loading...</td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="small text-muted mt-1" id="priceMeta"></div>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Set Primary Size Modal -->
    <div class="modal fade" id="primarySizeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" id="primarySizeForm">
          <div class="modal-header py-2">
            <h6 class="modal-title">Set Primary Size</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="product_id" id="primaryProductId">
            <div class="mb-2">
              <label class="form-label small">Choose size variant to display as primary:</label>
              <select class="form-select form-select-sm" id="primarySizeSelect" name="size_id" required>
                <option value="">Loading...</option>
              </select>
            </div>
            <div class="form-text small">The primary size controls which size code & computed price show in the Products table.</div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Manage Sizes Modal -->
    <div class="modal fade" id="manageSizesModal" tabindex="-1" aria-labelledby="manageSizesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="manageSizesModalLabel">Product Size Variants</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info small mb-3">
              <strong>How pricing works now:</strong> Base product price + (Delta) or overridden by (Absolute) size price. If a product has no size variants, only its base price is used. To add or change pricing, add a size variant here. The old standalone "Add Price" feature was removed for clarity.
            </div>
            <form id="addSizeForm" class="row g-3 align-items-end mb-3">
              <div class="col-md-4">
                <label class="form-label small">Product</label>
                <select class="form-select form-select-sm" name="product_id" required id="sizeProductSelect">
                  <option value="">Select...</option>
                  <?php foreach($products as $p): ?>
                    <option value="<?= (int)$p['Product_ID'] ?>" data-category="<?= htmlspecialchars($p['Category_ID']) ?>">
                      <?= htmlspecialchars($p['Product_Name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small">Size Code</label>
                <input type="text" maxlength="32" placeholder="e.g. 16oz or small" class="form-control form-control-sm" name="size_code" required>
              </div>
              <div class="col-md-2">
                <label class="form-label small">Display Name</label>
                <input type="text" maxlength="64" placeholder="Shown to users" class="form-control form-control-sm" name="display_name">
              </div>
              <div class="col-md-2">
                <label class="form-label small">Price</label>
                <select class="form-select form-select-sm" name="price_id" id="addSizePriceId" required>
                  <option value="">Select Price</option>
                  <?php foreach ($prices_list as $pr): ?>
                    <option value="<?= htmlspecialchars($pr['Price_ID']) ?>">₱<?= number_format($pr['Price_Amount'],2) ?> (<?= date('F d, Y', strtotime($pr['Effective_From'])) ?><?= $pr['Effective_To'] ? ' to '.date('F d, Y', strtotime($pr['Effective_To'])) : ' and onwards' ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small">Mode</label>
                <select class="form-select form-select-sm" name="is_absolute" required>
                  <option value="1">Absolute</option>
                  <option value="0" selected>Delta (+)</option>
                </select>
              </div>
              <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-circle"></i></button>
              </div>
            </form>
            <div class="table-responsive border rounded" style="max-height:360px; overflow:auto;">
              <table class="table table-sm table-hover align-middle mb-0" id="sizesTable">
                <thead class="table-light position-sticky top-0">
                  <tr>
                    <th style="width:40px;">#</th>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Mode</th>
                    <th>Amount</th>
                    <th>Sort</th>
                    <th>Updated</th>
                    <th style="width:70px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="8" class="text-center text-muted small">Loading...</td></tr>
                </tbody>
              </table>
            </div>
            <!-- Edit Size Modal -->
            <div class="modal fade" id="editSizeModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-sm">
                <form class="modal-content" id="editSizeForm">
                  <div class="modal-header py-2">
                    <h6 class="modal-title">Edit Size Variant</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="mapping_id" id="editMappingId">
                    <div class="mb-2">
                      <label class="form-label small mb-1">Size Code</label>
                      <input type="text" class="form-control form-control-sm" name="size_code" id="editSizeCode" required maxlength="32">
                    </div>
                    <div class="mb-2">
                      <label class="form-label small mb-1">Display Name</label>
                      <input type="text" class="form-control form-control-sm" name="display_name" id="editDisplayName" maxlength="64">
                    </div>
                    <div class="row g-2">
                      <div class="col-6">
                        <label class="form-label small mb-1">Mode</label>
                        <select class="form-select form-select-sm" name="price_mode" id="editPriceMode" required>
                          <option value="ABS">Absolute</option>
                          <option value="DELTA">Delta (+)</option>
                        </select>
                      </div>
                      <div class="col-6">
                        <label class="form-label small mb-1">Price</label>
                        <select class="form-select form-select-sm" name="price_id" id="editPriceId" required>
                          <option value="">Select Price</option>
                          <?php foreach ($prices_list as $pr): ?>
                            <option value="<?= htmlspecialchars($pr['Price_ID']) ?>">₱<?= number_format($pr['Price_Amount'],2) ?> (<?= date('F d, Y', strtotime($pr['Effective_From'])) ?><?= $pr['Effective_To'] ? ' to '.date('F d, Y', strtotime($pr['Effective_To'])) : ' and onwards' ?>)</option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="mt-2">
                      <label class="form-label small mb-1">Sort Order (optional)</label>
                      <input type="number" class="form-control form-control-sm" name="sort_order" id="editSortOrder" min="0">
                    </div>
                  </div>
                  <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                  </div>
                </form>
              </div>
            </div>
            <div class="form-text small mt-2">Absolute = full price overrides base product price. Delta = add amount to base price when selected.</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
  const currentCategoryFilter = "<?= $categoryFilter ? (int)$categoryFilter : '' ?>"; // Selected category from page filter
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

  // Add Price form handler (restored)
  document.getElementById('addPriceForm').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fetch('ajax/add_price.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
      if(data.success){
        Swal.fire({icon:'success',title:'Added',text:data.message, timer:1500, showConfirmButton:false});
        // Refresh all price dropdowns if server returned updated list
        if(Array.isArray(data.prices)){
          refreshPriceDropdowns(data.prices);
        }
        bootstrap.Modal.getInstance(document.getElementById('addPriceModal')).hide();
        this.reset();
      } else {
        Swal.fire({icon:'error',title:'Failed',text:data.message||'Could not add price'});
      }
    }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Request failed'}));
  });

  function refreshPriceDropdowns(prices){
    const productPriceSel = document.getElementById('price_id');
    const addSizePriceSel = document.getElementById('addSizePriceId');
    const editSizePriceSel = document.getElementById('editPriceId');
    [productPriceSel, addSizePriceSel, editSizePriceSel].forEach(sel=>{
      if(!sel) return;
      const currentVal = sel.value;
      // Preserve first placeholder option
      const placeholder = sel.querySelector('option[value=""]');
      sel.innerHTML = '';
      if(placeholder){ sel.appendChild(placeholder); } else {
        const opt = document.createElement('option'); opt.value=''; opt.textContent='Select Price'; sel.appendChild(opt);
      }
      prices.forEach(p=>{
        const opt = document.createElement('option');
        opt.value = p.Price_ID;
        const fromTxt = formatDate(p.Effective_From);
        const toTxt = p.Effective_To ? ' to '+formatDate(p.Effective_To) : ' and onwards';
        opt.textContent = `${p.Price_Amount} (${fromTxt}${toTxt})`;
        sel.appendChild(opt);
      });
      // Attempt to restore selection
      if(currentVal){ sel.value = currentVal; }
    });
  }

  function formatDate(str){
  // Load price list when Add Price modal opens
  document.getElementById('addPriceModal').addEventListener('shown.bs.modal', function(){
    loadPricePage(1);
  });

  document.getElementById('pricePrev').addEventListener('click', function(){
    const pager = document.getElementById('pricePager');
    const cur = parseInt(pager.getAttribute('data-page'))||1;
    if(cur>1) loadPricePage(cur-1);
  });
  document.getElementById('priceNext').addEventListener('click', function(){
    const pager = document.getElementById('pricePager');
    const cur = parseInt(pager.getAttribute('data-page'))||1;
    loadPricePage(cur+1);
  });

  function loadPricePage(page){
    const tbody = document.querySelector('#priceListTable tbody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center small text-muted">Loading...</td></tr>';
    fetch(`ajax/list_prices.php?page=${page}`)
      .then(r=>r.json())
      .then(data=>{
        if(!data.success){ tbody.innerHTML = `<tr><td colspan=3 class='text-danger small text-center'>${data.message||'Error'}</td></tr>`; return; }
        const rows = data.rows;
        if(!rows.length){ tbody.innerHTML = '<tr><td colspan="3" class="text-center small text-muted">No prices found.</td></tr>'; }
        else {
          tbody.innerHTML = '';
          rows.forEach(rw=>{
            const tr = document.createElement('tr');
            const effFrom = formatDate(rw.Effective_From);
            const effTo = rw.Effective_To ? formatDate(rw.Effective_To) : 'Open';
            tr.innerHTML = `<td>${rw.Price_ID}</td><td>₱${Number(rw.Price_Amount).toFixed(2)}</td><td>${effFrom} - ${effTo}</td>`;
            tbody.appendChild(tr);
          });
        }
        // Update pager state
        const pager = document.getElementById('pricePager');
        pager.setAttribute('data-page', data.current_page);
        document.getElementById('pricePrev').disabled = (data.current_page <= 1);
        document.getElementById('priceNext').disabled = (data.current_page >= data.total_pages);
        document.getElementById('priceMeta').textContent = `Page ${data.current_page} of ${data.total_pages} • Total Prices: ${data.total}`;
      })
      .catch(()=>{
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger small">Load failed.</td></tr>';
      });
  }
    if(!str) return '';
    // Expecting YYYY-MM-DD or datetime
    const d = new Date(str);
    if(isNaN(d.getTime())) return str;
    const mo = d.toLocaleString('en-US',{month:'long'});
    const day = String(d.getDate()).padStart(2,'0');
    const yr = d.getFullYear();
    return `${mo} ${day}, ${yr}`;
  }

    // Load sizes when modal opens (with category filtering)
    const manageSizesModal = document.getElementById('manageSizesModal');
    manageSizesModal.addEventListener('shown.bs.modal', function(){
      // Filter product dropdown options by category if selected
      const select = document.getElementById('sizeProductSelect');
      Array.from(select.options).forEach(opt=>{
        if(!opt.value) return; // skip placeholder
        const cat = opt.getAttribute('data-category');
        if(currentCategoryFilter){
          opt.hidden = (cat !== currentCategoryFilter);
        } else {
          opt.hidden = false;
        }
      });
      // Reset selection if current hidden
      if(currentCategoryFilter){
        const sel = select.options[select.selectedIndex];
        if(sel && sel.hidden){ select.value = ''; }
      }
      loadSizes();
    });

    function loadSizes(){
      const params = new URLSearchParams();
      if(currentCategoryFilter){ params.append('category_id', currentCategoryFilter); }
      fetch('ajax/list_sizes.php' + (params.toString()?('?'+params.toString()):''))
      .then(r=>r.json()).then(data=>{
        const tbody = document.querySelector('#sizesTable tbody');
        tbody.innerHTML='';
  if(!data.success){ tbody.innerHTML = `<tr><td colspan="8" class="text-danger small text-center">${data.message||'Failed to load'}</td></tr>`; return; }
  if(!data.rows.length){ tbody.innerHTML = '<tr><td colspan="8" class="text-muted small text-center">No size variants yet.</td></tr>'; return; }
        // Group rows by product so product name appears once with rowspan
        const groups = new Map();
        data.rows.forEach(row => {
          const pid = row.Product_ID || row.product_id || row.PID || (row.Product_Name||'__NOPROD');
          if(!groups.has(pid)) groups.set(pid, { productName: row.Product_Name||row.product_name||'Unknown', variants: [] });
          groups.get(pid).variants.push(row);
        });

        let displayIndex = 1;
        groups.forEach(group => {
          // Sort variants by Sort_Order then Size_Code for consistency
            group.variants.sort((a,b)=>{
              const soA = a.Sort_Order!==undefined?Number(a.Sort_Order):9999;
              const soB = b.Sort_Order!==undefined?Number(b.Sort_Order):9999;
              if(soA!==soB) return soA-soB;
              const scA = (a.Size_Code||a.size_code||'').toString();
              const scB = (b.Size_Code||b.size_code||'').toString();
              return scA.localeCompare(scB, undefined, {numeric:true});
            });
            const rowSpan = group.variants.length;
            group.variants.forEach((row, idx) => {
              const isLegacy = !!row.LEGACY;
              const mappingId = row.Product_Size_Price_ID || null;
              const modeRaw = row.Price_Mode || (row.Is_Absolute==1?'ABS':'DELTA');
              const modeLabel = (modeRaw==='ABS'?'Absolute':'Delta');
              const amountVal = (row.Price_Value!==undefined)? row.Price_Value : row.Price_Amount;
              const sizeCode = row.Size_Code || row.size_code || '';
              const legacyBadge = isLegacy ? '<span class="badge bg-warning text-dark ms-1">LEGACY</span>' : '';
              const sortOrder = row.Sort_Order !== undefined ? row.Sort_Order : '';
              const editBtnHtml = (!isLegacy && mappingId) ? `<button class="btn btn-sm btn-outline-secondary p-0 px-1 edit-size-btn" title="Edit" data-map="${mappingId}" data-code="${escapeHtml(sizeCode)}" data-display="${escapeHtml(row.Display_Name||row.display_name||sizeCode)}" data-mode="${modeRaw}" data-price-id="${row.Price_Source_ID||row.Price_ID||''}" data-amount="${amountVal}" data-sort="${sortOrder}"><i class='bi bi-pencil'></i></button>` : '';
              const delBtnHtml = `<button class="btn btn-sm btn-outline-danger p-0 px-1" data-id="${mappingId||row.ID||''}" title="Delete"><i class="bi bi-x"></i></button>`;
              const tr = document.createElement('tr');
              const productCell = (idx===0) ? `<td rowspan="${rowSpan}" class="align-middle fw-semibold">${escapeHtml(group.productName)}</td>` : '';
              tr.innerHTML = `
                <td>${displayIndex++}</td>
                ${productCell}
                <td><span class="badge bg-info text-dark">${escapeHtml(sizeCode)}${legacyBadge}</span></td>
                <td>${modeLabel}</td>
                <td>₱${Number(amountVal).toFixed(2)}</td>
                <td>${sortOrder}</td>
                <td>${row.Updated_At?escapeHtml(row.Updated_At):''}</td>
                <td class="d-flex gap-1">${editBtnHtml}${delBtnHtml}</td>`;
              tbody.appendChild(tr);
            });
        });
      }).catch(()=>{
        const tbody = document.querySelector('#sizesTable tbody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-danger small text-center">Error loading.</td></tr>';
      });
    }

    document.getElementById('addSizeForm').addEventListener('submit', function(e){
      e.preventDefault();
      const form = this;
      const fd = new FormData();
      fd.append('product_id', form.product_id.value);
      fd.append('size_code', form.size_code.value);
      fd.append('display_name', form.display_name.value);
      fd.append('price_id', form.price_id ? form.price_id.value : '');
      fd.append('is_absolute', form.is_absolute.value);
      fetch('ajax/add_size.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){
          loadSizes(); form.reset();
        } else {
          Swal.fire({icon:'error',title:'Size not added',text:data.message||'Failed'});
        }
      }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Failed to add size.'}));
    });

    document.querySelector('#sizesTable').addEventListener('click', function(e){
      const delBtn = e.target.closest('button[data-id]');
      const editBtn = e.target.closest('.edit-size-btn');
      if(editBtn){
        // Populate modal with canonical identifiers
        document.getElementById('editMappingId').value = editBtn.dataset.map;
        document.getElementById('editSizeCode').value = editBtn.dataset.code;
        document.getElementById('editDisplayName').value = editBtn.dataset.display || editBtn.dataset.code;
        document.getElementById('editPriceMode').value = editBtn.dataset.mode || 'ABS';
        // Prefer direct price id
        if(editBtn.dataset.priceId){
          document.getElementById('editPriceId').value = editBtn.dataset.priceId;
        } else if(editBtn.dataset.amount){
          const amount = Number(editBtn.dataset.amount).toFixed(2);
          const sel = document.getElementById('editPriceId');
          for(const opt of sel.options){ if(opt.value && opt.text.includes('₱'+amount)){ sel.value = opt.value; break; } }
        }
        document.getElementById('editSortOrder').value = editBtn.dataset.sort || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editSizeModal')).show();
      }
      if(delBtn){
        const id = delBtn.getAttribute('data-id');
        if(!id) return;
        Swal.fire({
          title:'Delete size variant?',
          text:'This cannot be undone.',
          icon:'warning',
          showCancelButton:true,
          confirmButtonText:'Delete',
          confirmButtonColor:'#d33'
        }).then(res=>{
          if(!res.isConfirmed) return;
          fetch('ajax/delete_size.php',{method:'POST',body:new URLSearchParams({id})})
            .then(r=>r.json())
            .then(data=>{
              if(data.success){
                Swal.fire({icon:'success',title:'Deleted',timer:1200,showConfirmButton:false});
                loadSizes();
              } else {
                Swal.fire({icon:'error',title:'Delete failed',text:data.message||'Unable to delete'});
              }
            })
            .catch(()=>Swal.fire({icon:'error',title:'Network',text:'Request failed'}));
        });
      }
    });

    document.getElementById('editSizeForm').addEventListener('submit', function(e){
      e.preventDefault();
      const form = this;
      const fd = new FormData();
      fd.append('mapping_id', form.mapping_id.value);
      fd.append('size_code', form.size_code.value);
      fd.append('display_name', form.display_name.value);
      fd.append('price_mode', form.price_mode.value);
      if(form.price_id) fd.append('price_id', form.price_id.value);
      fd.append('sort_order', form.sort_order.value||'');
      fetch('ajax/update_size.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){
          bootstrap.Modal.getInstance(document.getElementById('editSizeModal')).hide();
          loadSizes();
        } else {
          Swal.fire({icon:'error',title:'Update failed',text:data.message||'Error'});
        }
      }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Failed to update'}));
    });

    function escapeHtml(str){ return str.replace(/[&<>"]+/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

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

    // Bootstrap tooltips init
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el=> new bootstrap.Tooltip(el));

    // Open primary size modal
    document.querySelectorAll('.set-primary-size-btn').forEach(btn=>{
      btn.addEventListener('click', function(){
        const pid = this.getAttribute('data-product-id');
        document.getElementById('primaryProductId').value = pid;
        const sel = document.getElementById('primarySizeSelect');
        sel.innerHTML = '<option value="">Loading...</option>';
        fetch('ajax/list_sizes.php?product_id='+encodeURIComponent(pid))
          .then(r=>r.json())
          .then(data=>{
            sel.innerHTML = '';
            if(!data.success || !data.rows.length){ sel.innerHTML='<option value="">No sizes found</option>'; return; }
            const sizes = data.rows.filter(r=>r.Product_ID==pid || r.Product_ID==pid); // ensure only correct product
            sizes.forEach(s=>{
              if(!s.Product_Size_Price_ID) return; // skip legacy
              const opt = document.createElement('option');
              opt.value = s.Size_ID || ''; // may need size id, ensure list_sizes returns it
              opt.textContent = (s.Size_Code||'') + ' - ' + (s.Display_Name||'') + ' ('+ (s.Price_Mode|| (s.Is_Absolute==1?'ABS':'DELTA')) + ')';
              sel.appendChild(opt);
            });
            if(!sel.options.length){ sel.innerHTML='<option value="">No eligible sizes</option>'; }
          }).catch(()=> sel.innerHTML='<option value="">Load failed</option>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('primarySizeModal')).show();
      });
    });

    document.getElementById('primarySizeForm').addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this);
      fetch('ajax/set_primary_size.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
          if(data.success){
            Swal.fire({icon:'success',title:'Primary size set',timer:1200,showConfirmButton:false});
            bootstrap.Modal.getInstance(document.getElementById('primarySizeModal')).hide();
            // Refresh page to show updated primary size result
            setTimeout(()=>location.reload(),600);
          } else {
            Swal.fire({icon:'error',title:'Failed',text:data.message||'Unable to set primary size'});
          }
        }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Request failed'}));
    });
});
    </script>
        <!-- Unified Manage Variants Modal (Sizes & Flavors) -->
        <div class="modal fade" id="manageVariantsModal" tabindex="-1" aria-labelledby="manageVariantsModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header py-2">
                <h5 class="modal-title" id="manageVariantsModalLabel">Manage Variants (Sizes & Flavors)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-2">
                <div class="alert alert-info small mb-2">This new interface stores both size and flavor variants in <code>product_variant</code>. Mark one primary per type. Absolute replaces prior price; Delta adds on top.</div>
                <ul class="nav nav-tabs" id="variantTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sizes-tab" data-bs-toggle="tab" data-bs-target="#sizesPane" type="button" role="tab">Sizes</button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="flavors-tab" data-bs-toggle="tab" data-bs-target="#flavorsPane" type="button" role="tab">Flavors</button>
                  </li>
                </ul>
                <div class="tab-content border border-top-0 p-2 bg-light-subtle" id="variantTabsContent">
                  <!-- Sizes Pane -->
                  <div class="tab-pane fade show active" id="sizesPane" role="tabpanel" aria-labelledby="sizes-tab">
                    <form id="addSizeVariantForm" class="row g-2 align-items-end mb-2">
                      <div class="col-md-3">
                        <label class="form-label small mb-1">Product</label>
                        <select class="form-select form-select-sm" name="product_id" required id="sizeVarProduct">
                          <option value="">Select...</option>
                          <?php foreach($products as $p): ?>
                            <option value="<?= (int)$p['Product_ID'] ?>"><?= htmlspecialchars($p['Product_Name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" maxlength="50" required placeholder="e.g. 16OZ">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Label</label>
                        <input type="text" name="label" class="form-control form-control-sm" maxlength="100" required placeholder="Display name">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Mode</label>
                        <select name="price_mode" class="form-select form-select-sm" required>
                          <option value="ABSOLUTE">ABSOLUTE</option>
                          <option value="DELTA" selected>DELTA (+)</option>
                        </select>
                      </div>
                      <div class="col-md-1">
                        <label class="form-label small mb-1">Value</label>
                        <input type="number" step="0.01" name="price_value" value="0" class="form-control form-control-sm" required>
                      </div>
                      <div class="col-md-1">
                        <label class="form-label small mb-1">Sort</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="form-check mt-4">
                          <input class="form-check-input" type="checkbox" name="is_primary" id="sizePrimaryChk">
                          <label class="form-check-label small" for="sizePrimaryChk">Primary</label>
                        </div>
                      </div>
                      <div class="col-md-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus"></i> Add Size Variant</button>
                        <div id="sizeVarMsg" class="small text-muted"></div>
                      </div>
                    </form>
                    <div class="table-responsive border rounded" style="max-height:320px;overflow:auto;">
                      <table class="table table-sm table-hover align-middle mb-0" id="sizeVariantsTable">
                        <thead class="table-light sticky-top"><tr>
                          <th style="width:40px;">#</th><th>Product</th><th>Code</th><th>Label</th><th>Mode</th><th>Value</th><th>P</th><th>Sort</th><th>Updated</th><th style="width:95px;">Actions</th>
                        </tr></thead>
                        <tbody><tr><td colspan="10" class="text-center small text-muted">Loading...</td></tr></tbody>
                      </table>
                    </div>
                  </div>
                  <!-- Flavors Pane -->
                  <div class="tab-pane fade" id="flavorsPane" role="tabpanel" aria-labelledby="flavors-tab">
                    <form id="addFlavorVariantForm" class="row g-2 align-items-end mb-2">
                      <div class="col-md-3">
                        <label class="form-label small mb-1">Product</label>
                        <select class="form-select form-select-sm" name="product_id" required id="flavorVarProduct">
                          <option value="">Select...</option>
                          <?php foreach($products as $p): ?>
                            <option value="<?= (int)$p['Product_ID'] ?>"><?= htmlspecialchars($p['Product_Name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" maxlength="50" required placeholder="e.g. PORK">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Label</label>
                        <input type="text" name="label" class="form-control form-control-sm" maxlength="100" required placeholder="Display name">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label small mb-1">Mode</label>
                        <select name="price_mode" class="form-select form-select-sm" required>
                          <option value="DELTA" selected>DELTA (+)</option>
                          <option value="ABSOLUTE">ABSOLUTE</option>
                        </select>
                      </div>
                      <div class="col-md-1">
                        <label class="form-label small mb-1">Value</label>
                        <input type="number" step="0.01" name="price_value" value="0" class="form-control form-control-sm" required>
                      </div>
                      <div class="col-md-1">
                        <label class="form-label small mb-1">Sort</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="form-check mt-4">
                          <input class="form-check-input" type="checkbox" name="is_primary" id="flavorPrimaryChk">
                          <label class="form-check-label small" for="flavorPrimaryChk">Primary</label>
                        </div>
                      </div>
                      <div class="col-md-12 d-flex gap-2">
                        <button class="btn btn-sm btn-warning" type="submit"><i class="bi bi-plus"></i> Add Flavor Variant</button>
                        <div id="flavorVarMsg" class="small text-muted"></div>
                      </div>
                    </form>
                    <div class="table-responsive border rounded" style="max-height:320px;overflow:auto;">
                      <table class="table table-sm table-hover align-middle mb-0" id="flavorVariantsTable">
                        <thead class="table-light sticky-top"><tr>
                          <th style="width:40px;">#</th><th>Product</th><th>Code</th><th>Label</th><th>Mode</th><th>Value</th><th>P</th><th>Sort</th><th>Updated</th><th style="width:95px;">Actions</th>
                        </tr></thead>
                        <tbody><tr><td colspan="10" class="text-center small text-muted">Loading...</td></tr></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
    <script>
    // Variant Management JS (Unified)
    document.addEventListener('DOMContentLoaded', ()=>{
      const manageVariantsModal = document.getElementById('manageVariantsModal');
      if(!manageVariantsModal) return;

      const sizeTblBody = document.querySelector('#sizeVariantsTable tbody');
      const flavorTblBody = document.querySelector('#flavorVariantsTable tbody');

      manageVariantsModal.addEventListener('shown.bs.modal', ()=>{
        loadVariantType('size');
        loadVariantType('flavor');
      });

      function loadVariantType(type){
        const targetBody = type==='size'? sizeTblBody : flavorTblBody;
        targetBody.innerHTML = `<tr><td colspan="10" class="text-center small text-muted">Loading...</td></tr>`;
        fetch(`ajax/list_variants.php?type=${type}`)
          .then(r=>r.json())
          .then(data=>{
            if(!data.success){ targetBody.innerHTML = `<tr><td colspan="10" class="text-danger small text-center">${data.error||'Load failed'}</td></tr>`; return; }
            const rows = data.data||[];
            if(!rows.length){ targetBody.innerHTML = `<tr><td colspan="10" class="text-muted small text-center">No ${type} variants found.</td></tr>`; return; }
            // Group by product
            const groups = new Map();
            rows.forEach(v=>{ if(!groups.has(v.Product_ID)) groups.set(v.Product_ID, { product: v.Product_Name, list: []}); groups.get(v.Product_ID).list.push(v); });
            let idx=1; targetBody.innerHTML='';
            groups.forEach(g=>{
              g.list.sort((a,b)=>{ if(a.sort_order!=b.sort_order) return a.sort_order-b.sort_order; return a.label.localeCompare(b.label); });
              const span = g.list.length;
              g.list.forEach((v,i)=>{
                const tr = document.createElement('tr');
                const modeBadge = v.price_mode==='ABSOLUTE'? '<span class="badge bg-danger-subtle text-danger">ABS</span>' : '<span class="badge bg-success-subtle text-success">Δ</span>';
                const primaryStar = v.is_primary==1? '<span class="text-warning" title="Primary">★</span>' : '';
                const productCell = i===0? `<td rowspan="${span}" class="align-middle fw-semibold">${escapeHtml(g.product)}</td>`:'';
                tr.innerHTML = `
                  <td>${idx++}</td>
                  ${productCell}
                  <td><code>${escapeHtml(v.code)}</code></td>
                  <td>${escapeHtml(v.label)}</td>
                  <td>${modeBadge}</td>
                  <td>₱${Number(v.price_value).toFixed(2)}</td>
                  <td>${primaryStar}</td>
                  <td>${v.sort_order}</td>
                  <td>${v.updated_at?escapeHtml(v.updated_at):''}</td>
                  <td class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-secondary px-1 py-0 edit-variant-btn" title="Edit" data-vid="${v.Variant_ID}" data-type="${v.variant_type}" data-pid="${v.Product_ID}" data-code="${escapeHtml(v.code)}" data-label="${escapeHtml(v.label)}" data-mode="${v.price_mode}" data-value="${v.price_value}" data-sort="${v.sort_order}" data-primary="${v.is_primary}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-primary px-1 py-0 make-primary-btn" title="Set Primary" data-vid="${v.Variant_ID}" ${v.is_primary==1?'disabled':''}><i class="bi bi-star"></i></button>
                    <button class="btn btn-sm btn-outline-danger px-1 py-0 delete-variant-btn" title="Delete" data-vid="${v.Variant_ID}"><i class="bi bi-x"></i></button>
                  </td>`;
                targetBody.appendChild(tr);
              });
            });
          })
          .catch(()=> targetBody.innerHTML = `<tr><td colspan="10" class="text-danger small text-center">Error</td></tr>`);
      }

      function submitVariant(formEl, type){
        const msgEl = type==='size'? document.getElementById('sizeVarMsg'): document.getElementById('flavorVarMsg');
        const fd = new FormData(formEl);
        fd.append('variant_type', type);
        fetch('ajax/add_variant.php',{method:'POST',body:fd})
          .then(r=>r.json())
          .then(data=>{
            if(data.success){
              msgEl.textContent = 'Added'; msgEl.classList.remove('text-danger'); msgEl.classList.add('text-success');
              formEl.reset();
              loadVariantType(type);
            } else {
              msgEl.textContent = data.error||'Failed'; msgEl.classList.add('text-danger');
            }
            setTimeout(()=> msgEl.textContent='',1600);
          })
          .catch(()=>{ msgEl.textContent='Error'; msgEl.classList.add('text-danger'); setTimeout(()=> msgEl.textContent='',1600); });
      }

      document.getElementById('addSizeVariantForm')?.addEventListener('submit', e=>{ e.preventDefault(); submitVariant(e.target,'size'); });
      document.getElementById('addFlavorVariantForm')?.addEventListener('submit', e=>{ e.preventDefault(); submitVariant(e.target,'flavor'); });

      // Delegated actions (edit, primary, delete)
      manageVariantsModal.addEventListener('click', e=>{
        const editBtn = e.target.closest('.edit-variant-btn');
        const primaryBtn = e.target.closest('.make-primary-btn');
        const delBtn = e.target.closest('.delete-variant-btn');
        if(editBtn){ openVariantEdit(editBtn); }
        if(primaryBtn){ setPrimaryVariant(primaryBtn.dataset.vid); }
        if(delBtn){ deleteVariant(delBtn.dataset.vid); }
      });

      function setPrimaryVariant(id){
        fetch('ajax/set_primary_variant.php',{method:'POST',body:new URLSearchParams({variant_id:id})})
          .then(r=>r.json()).then(data=>{
            if(data.success){ loadVariantType('size'); loadVariantType('flavor'); } else { Swal.fire({icon:'error',title:'Primary failed',text:data.error||'Error'}); }
          }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Request failed'}));
      }

      function deleteVariant(id){
        Swal.fire({title:'Delete variant?',text:'This cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonText:'Delete'}).then(res=>{
          if(!res.isConfirmed) return;
          fetch('ajax/delete_variant.php',{method:'POST',body:new URLSearchParams({variant_id:id})})
            .then(r=>r.json()).then(data=>{
              if(data.success){ loadVariantType('size'); loadVariantType('flavor'); } else { Swal.fire({icon:'error',title:'Failed',text:data.error||'Delete failed'}); }
            }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Delete failed'}));
        });
      }

      // Inline edit via SweetAlert for speed (lightweight instead of extra modal)
      function openVariantEdit(btn){
        const vid = btn.dataset.vid;
        const cur = {
          code: btn.dataset.code,
          label: btn.dataset.label,
          mode: btn.dataset.mode,
          value: btn.dataset.value,
          sort: btn.dataset.sort,
          primary: btn.dataset.primary
        };
        const html = `<div class='text-start'>
          <label class='form-label small mt-1'>Code</label>
          <input id='vCode' class='form-control form-control-sm' value='${cur.code}' disabled>
          <label class='form-label small mt-1'>Label</label>
          <input id='vLabel' class='form-control form-control-sm' value='${cur.label}'>
          <label class='form-label small mt-1'>Mode</label>
          <select id='vMode' class='form-select form-select-sm'>
            <option value='ABSOLUTE' ${cur.mode==='ABSOLUTE'?'selected':''}>ABSOLUTE</option>
            <option value='DELTA' ${cur.mode==='DELTA'?'selected':''}>DELTA</option>
          </select>
          <label class='form-label small mt-1'>Value</label>
          <input id='vValue' type='number' step='0.01' class='form-control form-control-sm' value='${Number(cur.value).toFixed(2)}'>
          <label class='form-label small mt-1'>Sort</label>
          <input id='vSort' type='number' class='form-control form-control-sm' value='${cur.sort}'>
          <div class='form-check mt-2'>
            <input id='vPrimary' type='checkbox' class='form-check-input' ${cur.primary==1?'checked':''}>
            <label for='vPrimary' class='form-check-label small'>Primary</label>
          </div>
        </div>`;
        Swal.fire({title:'Edit Variant', html, showCancelButton:true, confirmButtonText:'Save', focusConfirm:false,
          preConfirm:()=>{
            return {
              label: document.getElementById('vLabel').value.trim(),
              mode: document.getElementById('vMode').value,
              value: document.getElementById('vValue').value,
              sort: document.getElementById('vSort').value,
              primary: document.getElementById('vPrimary').checked
            };
          }
        }).then(res=>{
          if(!res.isConfirmed) return;
          const payload = new URLSearchParams();
          payload.append('variant_id', vid);
          payload.append('label', res.value.label);
          payload.append('price_mode', res.value.mode);
          payload.append('price_value', res.value.value);
          payload.append('sort_order', res.value.sort);
          if(res.value.primary) payload.append('is_primary','1');
          fetch('ajax/update_variant.php',{method:'POST',body:payload})
            .then(r=>r.json()).then(data=>{
              if(data.success){ loadVariantType('size'); loadVariantType('flavor'); Swal.fire({icon:'success',title:'Saved',timer:1200,showConfirmButton:false}); }
              else Swal.fire({icon:'error',title:'Save failed',text:data.error||'Error'});
            }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Update failed'}));
        });
      }

      function escapeHtml(str=''){ return str.replace(/[&<>"']/g, c=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]||c)); }
    });
    </script>
</body>
</html>