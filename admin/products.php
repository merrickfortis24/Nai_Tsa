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

// Prices dropdown removed: admins will type price amounts manually now

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
      /* Compact multi-line truncation for long descriptions in the table */
      .table .desc-clamp{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;white-space:normal;word-break:break-word;}
      .table .desc-expanded{display:block;-webkit-line-clamp:unset;max-height:none;}
      .desc-toggle{cursor:pointer;}
      /* Uniform modal header fields */
      .uniform-fields .form-label{margin-bottom:4px}
      .uniform-fields .form-control.form-control-sm,
      .uniform-fields .form-select.form-select-sm,
      .uniform-fields .input-group.input-group-sm>.form-control,
      .uniform-fields .input-group.input-group-sm>.input-group-text{height:36px}
      .uniform-fields .input-group-text{min-width:38px;justify-content:center}
      @media (min-width: 992px){ /* lg */
        .uniform-fields .col-md-3{max-width:25%}
        .uniform-fields .col-md-2{max-width:20%}
      }
    </style>
</head>
<body class="dashboard-page">
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <!-- Desktop sidebar (visible on md+) -->
      <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
        <?php include 'sidebar.php'; ?>
      </div>
      <!-- Offcanvas sidebar for small screens: treat the offcanvas itself as the sidebar so styles apply consistently -->
      <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="--bs-offcanvas-width:260px;">
        <div class="offcanvas-body p-0">
          <?php include 'sidebar.php'; ?>
        </div>
      </div>
      <!-- Main Content -->
      <div class="col-md-10 col-lg-10 main-content">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center mt-3">
          <div>
            <h4 class="mb-0 fw-bold">Products</h4>
            <p class="mb-0 text-muted">Manage your products</p>
          </div>
          <!-- Sidebar toggle button for small screens (opens offcanvas) -->
          <button class="btn btn-outline-primary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manageVariantsModal">
                      <i class="bi bi-ui-checks-grid me-1"></i> Manage Flavors
                    </button>
                <!-- Global Add Price modal/button removed per new pricing flow -->
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
                      <div class="desc-clamp" id="desc-<?= (int)$product['Product_ID'] ?>">
                        <?= htmlspecialchars($product['Product_desc']) ?>
                      </div>
                      <?php if (!empty($product['Product_desc']) && strlen($product['Product_desc']) > 120): ?>
                        <a href="#" class="desc-toggle small text-primary" data-target="desc-<?= (int)$product['Product_ID'] ?>" aria-expanded="false">Show more</a>
                      <?php endif; ?>
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
            <!-- Current image preview (shown only when editing) -->
            <div class="mb-3" id="currentImageGroup" style="display:none;">
              <label class="form-label">Current Image</label>
              <div class="d-flex align-items-center gap-2">
                <img id="currentImagePreview" src="" alt="Current image" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;">
                <button type="button" class="btn btn-sm btn-outline-danger" id="removeCurrentImageBtn">Remove</button>
              </div>
            </div>
            <input type="hidden" id="product_image_existing" name="product_image_existing" value="">
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
              <label for="base_price" class="form-label">Base Price (₱)</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="base_price" name="base_price" placeholder="e.g. 70.00" required>
              <div class="form-text">Type the product's base price. This will be logged in price history.</div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label for="effective_from" class="form-label">Effective From</label>
                <input type="date" class="form-control" id="effective_from" name="effective_from" value="<?= date('Y-m-d'); ?>" required>
              </div>
              <div class="col-md-6">
                <label for="effective_to" class="form-label">Effective To (optional)</label>
                <input type="date" class="form-control" id="effective_to" name="effective_to">
              </div>
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

    <!-- Manage Flavors (Variants) Modal -->
    <div class="modal fade" id="manageVariantsModal" tabindex="-1" aria-labelledby="manageVariantsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="manageVariantsModalLabel">Product Flavor Variants</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info small mb-3">
              Add and manage flavor variants per product. Flavor prices support Absolute (full price) or Delta (added to the product's base price).
            </div>
            <form id="addVariantForm" class="row g-3 align-items-end mb-3 uniform-fields">
              <div class="col-md-3 col-sm-6">
                <label class="form-label small">Product</label>
                <select class="form-select form-select-sm" name="product_id" required id="variantProductSelect">
                  <option value="">Select...</option>
                  <?php foreach($products as $p): ?>
                    <option value="<?= (int)$p['Product_ID'] ?>" data-category="<?= htmlspecialchars($p['Category_ID']) ?>">
                      <?= htmlspecialchars($p['Product_Name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Code</label>
                <input type="text" maxlength="32" placeholder="e.g. CHOCO" class="form-control form-control-sm" name="code" required>
              </div>
              <div class="col-md-3 col-sm-6">
                <label class="form-label small">Label</label>
                <input type="text" maxlength="64" placeholder="Shown to users" class="form-control form-control-sm" name="label" required>
              </div>
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Mode</label>
                <select class="form-select form-select-sm" name="price_mode" required>
                  <option value="ABSOLUTE" selected>Absolute</option>
                  <option value="DELTA">Delta (+)</option>
                </select>
              </div>
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Amount</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" min="0" placeholder="0.00" class="form-control form-control-sm text-end bg-white text-dark" name="price_value" required>
                </div>
              </div>
              <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="variantPrimaryCheck" name="is_primary">
                  <label class="form-check-label small" for="variantPrimaryCheck">Set as Primary</label>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <label class="form-label small mb-0" for="variantSort">Sort</label>
                  <input id="variantSort" type="number" class="form-control form-control-sm" style="width:90px;" name="sort_order" min="0" value="0">
                </div>
                <div class="ms-auto">
                  <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-circle"></i></button>
                </div>
              </div>
            </form>
            <div class="table-responsive border rounded" style="max-height:360px; overflow:auto;">
              <table class="table table-sm table-hover align-middle mb-0" id="variantsTable">
                <thead class="table-light position-sticky top-0">
                  <tr>
                    <th style="width:40px;">#</th>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Mode</th>
                    <th>Amount</th>
                    <th>Primary</th>
                    <th>Sort</th>
                    <th style="width:70px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="9" class="text-center text-muted small">Select a product to view flavors.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Flavor Variant Modal -->
    <div class="modal fade" id="editVariantModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" id="editVariantForm">
          <div class="modal-header py-2">
            <h6 class="modal-title">Edit Flavor Variant</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="variant_id" id="editVariantId">
            <div class="mb-2"><small class="text-muted">Product</small><div id="editVariantProductName" class="fw-semibold"></div></div>
            <div class="row g-2">
              <div class="col-4">
                <label class="form-label small mb-1">Code</label>
                <input type="text" class="form-control form-control-sm" id="editVariantCode" disabled>
              </div>
              <div class="col-8">
                <label class="form-label small mb-1">Label</label>
                <input type="text" class="form-control form-control-sm" name="label" id="editVariantLabel" required maxlength="64">
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col-6">
                <label class="form-label small mb-1">Mode</label>
                <select class="form-select form-select-sm" name="price_mode" id="editVariantMode" required>
                  <option value="ABSOLUTE">Absolute</option>
                  <option value="DELTA">Delta (+)</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small mb-1">Amount</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="price_value" id="editVariantAmount" required>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3 mt-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="editVariantPrimary" name="is_primary">
                <label class="form-check-label small" for="editVariantPrimary">Set as Primary</label>
              </div>
              <div class="d-flex align-items-center gap-2">
                <label class="form-label small mb-0" for="editVariantSort">Sort</label>
                <input id="editVariantSort" type="number" class="form-control form-control-sm" style="width:90px;" name="sort_order" min="0" value="0">
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Global Add Price modal removed -->

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
            <div class="alert alert-secondary small mb-3">
              Tip: If you choose <strong>Delta</strong>, the amount shown will be applied relative to the product's base price you entered when creating the product. If you type an absolute price while Delta is selected, the system will automatically convert it to the correct delta (absolute - base price).
            </div>
            <form id="addSizeForm" class="row g-3 align-items-end mb-3 uniform-fields">
              <div class="col-md-3 col-sm-6">
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
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Size Code</label>
                <input type="text" maxlength="32" placeholder="e.g. 16oz or small" class="form-control form-control-sm" name="size_code" required>
              </div>
              <div class="col-md-3 col-sm-6">
                <label class="form-label small">Display Name</label>
                <input type="text" maxlength="64" placeholder="Shown to users" class="form-control form-control-sm" name="display_name">
              </div>
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Amount</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" min="0" placeholder="0.00" class="form-control form-control-sm text-end bg-white text-dark" name="price_amount" id="addSizePriceAmount" required>
                </div>
              </div>
              <div class="col-md-2 col-sm-6">
                <label class="form-label small">Mode</label>
                <select class="form-select form-select-sm" name="is_absolute" required>
                  <option value="1">Absolute</option>
                  <option value="0" selected>Delta (+)</option>
                </select>
              </div>
              <div class="col-12 col-md-1 d-grid">
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
                        <label class="form-label small mb-1">Amount (₱)</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price_amount" id="editPriceAmount" required>
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
        // Determine if this was an update (product_id present) or an add
        var isUpdate = !!document.getElementById('product_id').value;
        var title = isUpdate ? 'Product Updated' : 'Product Added';
        var defaultText = isUpdate ? 'Product successfully updated' : 'Product successfully added';
        Swal.fire({
          icon: 'success',
          title: title,
          text: data.message || defaultText
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

  // Pricing dropdowns and global Add Price modal removed

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
      // Trigger mode change to auto-fill when modal opens (default Absolute expected)
      try{
        const sizeMode = document.querySelector('#manageSizesModal select[name="is_absolute"]');
        if(sizeMode){
          sizeMode.dispatchEvent(new Event('change'));
        }
      }catch(e){}
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
        // Group by Product_ID so Product is shown once using rowspan
        const groups = new Map();
        data.rows.forEach(r=>{
          const pid = (r.Product_ID!==undefined && r.Product_ID!==null) ? String(r.Product_ID) : 'unknown';
          if(!groups.has(pid)) groups.set(pid, { name: r.Product_Name || '', items: [] });
          groups.get(pid).items.push(r);
        });
        let rowCounter = 0;
        for(const [pid, group] of groups.entries()){
          const count = group.items.length;
          group.items.forEach((row, idx)=>{
            rowCounter++;
            const isLegacy = !!row.LEGACY;
            const mappingId = row.Product_Size_Price_ID || null; // null for legacy rows
            const modeLabel = row.Price_Mode ? (row.Price_Mode==='ABS'?'Absolute':'Delta') : (row.Is_Absolute==1?'Absolute':'Delta');
            const amountVal = (row.Price_Value!==undefined)? row.Price_Value : row.Price_Amount;
            const sizeCode = row.Size_Code || row.size_code;
            const legacyBadge = isLegacy ? '<span class="badge bg-warning text-dark ms-1">LEGACY</span>' : '';
            const sortOrder = row.Sort_Order !== undefined ? row.Sort_Order : '';
            const updatedAt = row.Updated_At?escapeHtml(row.Updated_At):'';
            const actionsHtml = `
              ${!isLegacy && mappingId ? `<button class=\"btn btn-sm btn-outline-secondary p-0 px-1 edit-size-btn\" data-map=\"${mappingId}\" data-code=\"${escapeHtml(sizeCode)}\" data-mode=\"${row.Price_Mode}\" data-amount=\"${amountVal}\" data-sort=\"${sortOrder}\" data-display=\"${escapeHtml(row.Display_Name||sizeCode)}\" data-price-id=\"${row.Price_Source_ID || ''}\" title=\"Edit\"><i class='bi bi-pencil'></i></button>` : ''}
              <button class=\"btn btn-sm btn-outline-danger p-0 px-1\" data-id=\"${mappingId||row.ID}\" title=\"Delete\"><i class=\"bi bi-x\"></i></button>
            `;
            const tr = document.createElement('tr');
            if(idx === 0){
              tr.innerHTML = `
                <td>${rowCounter}</td>
                <td rowspan="${count}">${escapeHtml(group.name||'')}</td>
                <td><span class="badge bg-info text-dark">${escapeHtml(sizeCode)}${legacyBadge}</span></td>
                <td>${modeLabel}</td>
                <td>₱${Number(amountVal).toFixed(2)}</td>
                <td>${sortOrder}</td>
                <td>${updatedAt}</td>
                <td class="d-flex gap-1">${actionsHtml}</td>
              `;
            } else {
              tr.innerHTML = `
                <td>${rowCounter}</td>
                <td><span class="badge bg-info text-dark">${escapeHtml(sizeCode)}${legacyBadge}</span></td>
                <td>${modeLabel}</td>
                <td>₱${Number(amountVal).toFixed(2)}</td>
                <td>${sortOrder}</td>
                <td>${updatedAt}</td>
                <td class="d-flex gap-1">${actionsHtml}</td>
              `;
            }
            tbody.appendChild(tr);
          });
        }
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
  if(form.price_amount) fd.append('price_amount', form.price_amount.value);
      fd.append('is_absolute', form.is_absolute.value);
      fetch('ajax/add_size.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){
          loadSizes(); form.reset();
        } else {
          Swal.fire({icon:'error',title:'Size not added',text:data.message||'Failed'});
        }
      }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Failed to add size.'}));
    });

    // Auto-fill Amount when Absolute is selected: fetch product base price
    async function fetchBasePrice(productId){
      if(!productId) return null;
      try{
        const res = await fetch('ajax/get_product_base_price.php?product_id='+encodeURIComponent(productId));
        const j = await res.json();
        if(j.success) return j.price; else return null;
      } catch(e){ return null; }
    }

    // When product selection changes in add size form, update placeholder
    document.querySelector('select[name="is_absolute"][name]')?.addEventListener?.('change', ()=>{}); // noop to keep consistent
    const sizeProductSelectEl = document.getElementById('sizeProductSelect');
    const sizeModeSelect = document.querySelector('#manageSizesModal select[name="is_absolute"]');
    const sizeAmountInput = document.querySelector('#manageSizesModal input[name="price_amount"]');
    if(sizeModeSelect && sizeProductSelectEl && sizeAmountInput){
      sizeModeSelect.addEventListener('change', async function(){
        const mode = this.value; // '1' = Absolute, '0' = Delta
        if(mode === '1'){
          const pid = sizeProductSelectEl.value;
          const base = await fetchBasePrice(pid);
          if(base !== null){
            // Auto-populate value only if empty to avoid overwriting admin input
            if(!sizeAmountInput.value || Number(sizeAmountInput.value) === 0){
              sizeAmountInput.value = Number(base).toFixed(2);
            }
            sizeAmountInput.placeholder = Number(base).toFixed(2);
          }
        } else {
          // For delta mode, clear only placeholder but keep value if admin set
          sizeAmountInput.placeholder = '0.00';
        }
      });
      // Also update when product select changes while Absolute active
      sizeProductSelectEl.addEventListener('change', async function(){
        if(sizeModeSelect.value === '1'){
          const base = await fetchBasePrice(this.value);
          if(base !== null){
            if(!sizeAmountInput.value || Number(sizeAmountInput.value) === 0){
              sizeAmountInput.value = Number(base).toFixed(2);
            }
            sizeAmountInput.placeholder = Number(base).toFixed(2);
          }
        }
      });
    }

    // Variants: similar behavior
    const variantProductSelectEl = document.getElementById('variantProductSelect');
    const variantModeSelect = document.querySelector('#manageVariantsModal select[name="price_mode"]');
    const variantAmountInput = document.querySelector('#manageVariantsModal input[name="price_value"]');
    if(variantModeSelect && variantProductSelectEl && variantAmountInput){
      variantModeSelect.addEventListener('change', async function(){
        const mode = (this.value||'').toUpperCase();
        if(mode === 'ABSOLUTE'){
          const pid = variantProductSelectEl.value;
          const base = await fetchBasePrice(pid);
          if(base !== null){
            if(!variantAmountInput.value || Number(variantAmountInput.value) === 0){
              variantAmountInput.value = Number(base).toFixed(2);
            }
            variantAmountInput.placeholder = Number(base).toFixed(2);
          }
        } else {
          variantAmountInput.placeholder = '0.00';
        }
      });
      variantProductSelectEl.addEventListener('change', async function(){
        if((variantModeSelect.value||'').toUpperCase() === 'ABSOLUTE'){
          const base = await fetchBasePrice(this.value);
          if(base !== null){
            if(!variantAmountInput.value || Number(variantAmountInput.value) === 0){
              variantAmountInput.value = Number(base).toFixed(2);
            }
            variantAmountInput.placeholder = Number(base).toFixed(2);
          }
        }
      });
    }

    // Manage Flavor Variants
    const manageVariantsModal = document.getElementById('manageVariantsModal');
    const variantsTableBody = document.querySelector('#variantsTable tbody');
    const variantProductSelect = document.getElementById('variantProductSelect');
    manageVariantsModal.addEventListener('shown.bs.modal', function(){
      // Filter product dropdown options by category if selected
      Array.from(variantProductSelect.options).forEach(opt=>{
        if(!opt.value) return;
        const cat = opt.getAttribute('data-category');
        if(currentCategoryFilter){ opt.hidden = (cat !== currentCategoryFilter); } else { opt.hidden = false; }
      });
      // Clear selection if current selection is now hidden due to filter
      if(currentCategoryFilter){
        const sel = variantProductSelect.options[variantProductSelect.selectedIndex];
        if(sel && sel.hidden){ variantProductSelect.value = ''; }
      }
      // Trigger the variant mode handler so Amount auto-fills (default Absolute)
      try{
        const vMode = document.querySelector('#manageVariantsModal select[name="price_mode"]');
        if(vMode) vMode.dispatchEvent(new Event('change'));
      }catch(e){}
      // Like Manage Sizes: load all flavors without requiring a product selection
      variantsTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted small">Loading...</td></tr>';
      loadVariants(null);
    });

    variantProductSelect.addEventListener('change', function(){
      const pid = this.value;
      if(!pid){
        // No specific product selected: show all flavors again
        variantsTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted small">Loading...</td></tr>';
        loadVariants(null);
        return;
      }
      loadVariants(pid);
    });

    function loadVariants(productId){
      variantsTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted small">Loading...</td></tr>';
      const params = new URLSearchParams();
      params.append('type','flavor');
      if(productId){ params.append('product_id', productId); }
      if(currentCategoryFilter){ params.append('category_id', currentCategoryFilter); }
      fetch('ajax/list_variants.php?' + params.toString())
        .then(r=>r.json())
        .then(data=>{
          if(!data.success){ variantsTableBody.innerHTML = `<tr><td colspan="9" class="text-danger small text-center">${data.error||'Failed to load'}</td></tr>`; return; }
          let rows = data.data||[];
          // If no specific product and a category is active, try client-side filter using visible product IDs
          if(!productId && currentCategoryFilter){
            const allowedIds = new Set(Array.from(variantProductSelect.options).filter(o=>o.value && !o.hidden).map(o=>String(o.value)));
            rows = rows.filter(r => r.Product_ID !== undefined ? allowedIds.has(String(r.Product_ID)) : true);
          }
          if(!rows.length){ variantsTableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted small">No flavors yet.</td></tr>'; return; }
          // Group flavors by product so Product appears once with rowspan
          const groups = new Map();
          rows.forEach(r => {
            const pid = (r.Product_ID!==undefined && r.Product_ID!==null) ? String(r.Product_ID) : 'unknown';
            if(!groups.has(pid)) groups.set(pid, { name: r.Product_Name || '', items: [] });
            groups.get(pid).items.push(r);
          });
          variantsTableBody.innerHTML = '';
          let rowIndex = 0;
          for(const [pid, group] of groups.entries()){
            const count = group.items.length;
            group.items.forEach((row, idx) => {
              rowIndex++;
              const tr = document.createElement('tr');
              if(idx === 0){
                tr.innerHTML = `
                  <td>${rowIndex}</td>
                  <td rowspan="${count}">${escapeHtml(group.name||'')}</td>
                  <td><span class="badge bg-secondary">${escapeHtml(row.code||'')}</span></td>
                  <td>${escapeHtml(row.label||'')}</td>
                  <td>${row.price_mode||''}</td>
                  <td>₱${Number(row.price_value||0).toFixed(2)}</td>
                  <td>${row.is_primary==1?'<i class="bi bi-star-fill text-warning"></i>':'-'}</td>
                  <td>${row.sort_order||0}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary p-0 px-1 edit-variant-btn"
                      data-variant-id="${row.Variant_ID}"
                      data-product-id="${row.Product_ID||''}"
                      data-product-name="${escapeHtml(group.name||'')}"
                      data-code="${escapeHtml(row.code||'')}"
                      data-label="${escapeHtml(row.label||'')}"
                      data-mode="${row.price_mode||''}"
                      data-amount="${row.price_value||0}"
                      data-primary="${row.is_primary==1?1:0}"
                      data-sort="${row.sort_order||0}"
                      title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger p-0 px-1" data-variant-id="${row.Variant_ID}" title="Delete"><i class="bi bi-x"></i></button>
                  </td>
                `;
              } else {
                tr.innerHTML = `
                  <td>${rowIndex}</td>
                  <td><span class="badge bg-secondary">${escapeHtml(row.code||'')}</span></td>
                  <td>${escapeHtml(row.label||'')}</td>
                  <td>${row.price_mode||''}</td>
                  <td>₱${Number(row.price_value||0).toFixed(2)}</td>
                  <td>${row.is_primary==1?'<i class="bi bi-star-fill text-warning"></i>':'-'}</td>
                  <td>${row.sort_order||0}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary p-0 px-1 edit-variant-btn"
                      data-variant-id="${row.Variant_ID}"
                      data-product-id="${row.Product_ID||''}"
                      data-product-name="${escapeHtml(group.name||'')}"
                      data-code="${escapeHtml(row.code||'')}"
                      data-label="${escapeHtml(row.label||'')}"
                      data-mode="${row.price_mode||''}"
                      data-amount="${row.price_value||0}"
                      data-primary="${row.is_primary==1?1:0}"
                      data-sort="${row.sort_order||0}"
                      title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger p-0 px-1" data-variant-id="${row.Variant_ID}" title="Delete"><i class="bi bi-x"></i></button>
                  </td>
                `;
              }
              variantsTableBody.appendChild(tr);
            });
          }
        })
        .catch(()=> variantsTableBody.innerHTML = '<tr><td colspan="9" class="text-danger small text-center">Error loading.</td></tr>');
    }

    document.getElementById('addVariantForm').addEventListener('submit', function(e){
      e.preventDefault();
      const form = this;
      const pid = form.product_id.value;
      const fd = new FormData(form);
      fd.append('variant_type','flavor');
      fetch('ajax/add_variant.php',{method:'POST',body:fd})
        .then(r=>r.json()).then(data=>{
          if(data.success){
            form.reset();
            if(pid){ loadVariants(pid); } else { loadVariants(null); }
          } else {
            Swal.fire({icon:'error',title:'Flavor not added',text:data.error||'Failed'});
          }
        }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Failed to add flavor'}));
    });

    document.querySelector('#variantsTable').addEventListener('click', function(e){
      // Edit handler
      const editBtn = e.target.closest('button.edit-variant-btn');
      if(editBtn){
        const id = editBtn.getAttribute('data-variant-id');
        document.getElementById('editVariantId').value = id;
        document.getElementById('editVariantProductName').textContent = editBtn.getAttribute('data-product-name') || '';
        document.getElementById('editVariantCode').value = editBtn.getAttribute('data-code') || '';
        document.getElementById('editVariantLabel').value = editBtn.getAttribute('data-label') || '';
        const mode = (editBtn.getAttribute('data-mode')||'').toUpperCase();
        document.getElementById('editVariantMode').value = (mode==='ABSOLUTE' || mode==='DELTA') ? mode : 'DELTA';
        const amt = parseFloat(editBtn.getAttribute('data-amount')||'0');
        document.getElementById('editVariantAmount').value = amt.toFixed(2);
        document.getElementById('editVariantPrimary').checked = editBtn.getAttribute('data-primary')==='1';
        document.getElementById('editVariantSort').value = editBtn.getAttribute('data-sort')||'0';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editVariantModal')).show();
        return;
      }
      // Delete handler
      const delBtn = e.target.closest('button[data-variant-id]:not(.edit-variant-btn)');
      if(!delBtn) return;
      const id = delBtn.getAttribute('data-variant-id');
      Swal.fire({title:'Delete flavor?',icon:'warning',showCancelButton:true}).then(res=>{
        if(!res.isConfirmed) return;
        const fd = new FormData(); fd.append('variant_id', id);
        fetch('ajax/delete_variant.php',{method:'POST', body:fd}).then(r=>r.json()).then(data=>{
          if(data.success){
            const pid = variantProductSelect.value; if(pid){ loadVariants(pid); } else { loadVariants(null); }
          } else {
            Swal.fire({icon:'error',title:'Delete failed',text:data.error||'Error'});
          }
        }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Failed to delete'}));
      });
    });

    // Submit Edit Flavor form
    document.getElementById('editVariantForm').addEventListener('submit', function(e){
      e.preventDefault();
      const form = this;
      const fd = new FormData(form);
      fetch('ajax/update_variant.php',{method:'POST', body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){
          bootstrap.Modal.getInstance(document.getElementById('editVariantModal')).hide();
          const pid = variantProductSelect.value; if(pid){ loadVariants(pid); } else { loadVariants(null); }
        } else {
          Swal.fire({icon:'error',title:'Update failed',text:data.error||'Error'});
        }
      }).catch(()=> Swal.fire({icon:'error',title:'Network',text:'Failed to update'}));
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
        // Fill amount directly (if provided), otherwise clear so auto-fill can run
        const editPriceAmountEl = document.getElementById('editPriceAmount');
        if(editBtn.dataset.amount){
          editPriceAmountEl.value = Number(editBtn.dataset.amount).toFixed(2);
        } else {
          editPriceAmountEl.value = '';
        }
        document.getElementById('editSortOrder').value = editBtn.dataset.sort || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editSizeModal')).show();
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
  if(form.price_amount) fd.append('price_amount', form.price_amount.value);
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

    // Edit modals: auto-fill and overwrite when Absolute selected
    const editSizeModalEl = document.getElementById('editSizeModal');
    const editSizeModeEl = document.getElementById('editPriceMode');
    const editSizeAmountEl = document.getElementById('editPriceAmount');
    const editSizeProductIdEl = document.getElementById('editMappingProductId'); // optional hidden input if exists
    if(editSizeModalEl){
      editSizeModalEl.addEventListener('shown.bs.modal', async function(){
        // If mode is ABS, always overwrite amount with base price
        try{
          const pid = (document.getElementById('editMappingProductId') && document.getElementById('editMappingProductId').value) ? document.getElementById('editMappingProductId').value : document.getElementById('sizeProductSelect')?.value;
          if((editSizeModeEl && editSizeModeEl.value === 'ABS') && pid){
            const base = await fetchBasePrice(pid);
            if(base !== null) editSizeAmountEl.value = Number(base).toFixed(2);
          }
        } catch(e){}
      });
      if(editSizeModeEl){
        editSizeModeEl.addEventListener('change', async function(){
          if(this.value === 'ABS'){
            const pid = document.getElementById('sizeProductSelect')?.value;
            const base = await fetchBasePrice(pid);
            if(base !== null) editSizeAmountEl.value = Number(base).toFixed(2);
          }
        });
      }
    }

    const editVariantModalEl = document.getElementById('editVariantModal');
    const editVariantModeEl = document.getElementById('editVariantMode');
    const editVariantAmountEl = document.getElementById('editVariantAmount');
    if(editVariantModalEl){
      editVariantModalEl.addEventListener('shown.bs.modal', async function(){
        try{
          const pid = document.getElementById('editVariantProductId') ? document.getElementById('editVariantProductId').value : document.getElementById('variantProductSelect')?.value;
          if((editVariantModeEl && (editVariantModeEl.value||'').toUpperCase() === 'ABSOLUTE') && pid){
            const base = await fetchBasePrice(pid);
            if(base !== null) editVariantAmountEl.value = Number(base).toFixed(2);
          }
        } catch(e){}
      });
      if(editVariantModeEl){
        editVariantModeEl.addEventListener('change', async function(){
          if((this.value||'').toUpperCase() === 'ABSOLUTE'){
            const pid = document.getElementById('variantProductSelect')?.value;
            const base = await fetchBasePrice(pid);
            if(base !== null) editVariantAmountEl.value = Number(base).toFixed(2);
          }
        });
      }
    }

    function escapeHtml(str){ return str.replace(/[&<>"]+/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

    document.querySelectorAll('.edit-product-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('product_name').value = this.dataset.productName;
            document.getElementById('product_desc').value = this.dataset.productDesc;
            document.getElementById('category_id').value = this.dataset.categoryId;
            document.getElementById('base_price').value = '';
            document.getElementById('effective_from').value = '<?= date('Y-m-d'); ?>';
            document.getElementById('effective_to').value = '';
            document.getElementById('product_id').value = this.dataset.productId;
            // Set current image preview and hidden existing field
            const imgName = this.dataset.image || '';
            const imgGroup = document.getElementById('currentImageGroup');
            const imgPrev = document.getElementById('currentImagePreview');
            const existingInput = document.getElementById('product_image_existing');
            existingInput.value = imgName;
            if(imgName){
              imgPrev.src = 'uploads/products/' + imgName;
              imgGroup.style.display = '';
            } else {
              imgPrev.src = '';
              imgGroup.style.display = 'none';
            }
            // Ensure file input cleared for fresh selection
            const fileInput = document.getElementById('product_image');
            if(fileInput){ fileInput.value = ''; }
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
        document.getElementById('product_image_existing').value = '';
        const imgGroup = document.getElementById('currentImageGroup');
        const imgPrev = document.getElementById('currentImagePreview');
        if(imgPrev){ imgPrev.src = ''; }
        if(imgGroup){ imgGroup.style.display = 'none'; }
    });

    // Allow removing current image when editing
    document.getElementById('removeCurrentImageBtn').addEventListener('click', function(){
      // Clear the preview and hidden existing value to signal removal on save
      const imgPrev = document.getElementById('currentImagePreview');
      const existingInput = document.getElementById('product_image_existing');
      if(imgPrev){ imgPrev.src = ''; }
      if(existingInput){ existingInput.value = ''; }
      const fileInput = document.getElementById('product_image');
      if(fileInput){ fileInput.value = ''; }
      document.getElementById('currentImageGroup').style.display = 'none';
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

    // Toggle long description show more/less
    document.querySelectorAll('.desc-toggle').forEach(link => {
      link.addEventListener('click', function(e){
        e.preventDefault();
        const targetId = this.getAttribute('data-target');
        const target = document.getElementById(targetId);
        if(!target) return;
        const expanded = this.getAttribute('aria-expanded') === 'true';
        if(expanded){
          target.classList.remove('desc-expanded');
          target.classList.add('desc-clamp');
          this.setAttribute('aria-expanded','false');
          this.textContent = 'Show more';
        } else {
          target.classList.remove('desc-clamp');
          target.classList.add('desc-expanded');
          this.setAttribute('aria-expanded','true');
          this.textContent = 'Show less';
        }
      });
    });
});
    </script>
</body>
</html>