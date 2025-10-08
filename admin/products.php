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
                    <!-- Allergens data removed -->
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

    <!-- Legacy Add Price Modal removed -->

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
                <label class="form-label small">Price Amount</label>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price_amount" required>
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
                        <label class="form-label small mb-1">Amount</label>
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

  // Removed legacy add price form handler

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
        data.rows.forEach((row,i)=>{
          const isLegacy = !!row.LEGACY;
          const mappingId = row.Product_Size_Price_ID || null; // null for legacy rows
          const modeLabel = row.Price_Mode ? (row.Price_Mode==='ABS'?'Absolute':'Delta') : (row.Is_Absolute==1?'Absolute':'Delta');
          const amountVal = (row.Price_Value!==undefined)? row.Price_Value : row.Price_Amount;
          const sizeCode = row.Size_Code || row.size_code;
          const legacyBadge = isLegacy ? '<span class="badge bg-warning text-dark ms-1">LEGACY</span>' : '';
          const sortOrder = row.Sort_Order !== undefined ? row.Sort_Order : '';
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${i+1}</td>
            <td>${escapeHtml(row.Product_Name||'')}</td>
            <td><span class="badge bg-info text-dark">${escapeHtml(sizeCode)}${legacyBadge}</span></td>
            <td>${modeLabel}</td>
            <td>₱${Number(amountVal).toFixed(2)}</td>
            <td>${sortOrder}</td>
            <td>${row.Updated_At?escapeHtml(row.Updated_At):''}</td>
            <td class="d-flex gap-1">
              ${!isLegacy && mappingId ? `<button class="btn btn-sm btn-outline-secondary p-0 px-1 edit-size-btn" data-map="${mappingId}" data-code="${escapeHtml(sizeCode)}" data-mode="${row.Price_Mode}" data-amount="${amountVal}" data-sort="${sortOrder}" data-display="${escapeHtml(row.Display_Name||sizeCode)}" title="Edit"><i class='bi bi-pencil'></i></button>` : ''}
              <button class="btn btn-sm btn-outline-danger p-0 px-1" data-id="${mappingId||row.ID}" title="Delete"><i class="bi bi-x"></i></button>
            </td>`;
          tbody.appendChild(tr);
        });
      }).catch(()=>{
        const tbody = document.querySelector('#sizesTable tbody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-danger small text-center">Error loading.</td></tr>';
      });
    }

    document.getElementById('addSizeForm').addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this);
      fetch('ajax/add_size.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success){
          loadSizes(); this.reset();
        } else {
          Swal.fire({icon:'error',title:'Size not added',text:data.message||'Failed'});
        }
      }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Failed to add size.'}));
    });

    document.querySelector('#sizesTable').addEventListener('click', function(e){
      const delBtn = e.target.closest('button[data-id]');
      const editBtn = e.target.closest('.edit-size-btn');
      if(delBtn){
        const id = delBtn.dataset.id;
        Swal.fire({title:'Delete size variant?',icon:'warning',showCancelButton:true}).then(res=>{
          if(res.isConfirmed){
            fetch('ajax/delete_size.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)}).then(r=>r.json()).then(data=>{
              if(data.success){ loadSizes(); } else { Swal.fire({icon:'error',title:'Failed',text:data.message||'Could not delete'}); }
            }).catch(()=>Swal.fire({icon:'error',title:'Network',text:'Delete failed'}));
          }
        });
      } else if(editBtn){
        // Populate modal
        document.getElementById('editMappingId').value = editBtn.dataset.map;
        document.getElementById('editSizeCode').value = editBtn.dataset.code;
        document.getElementById('editDisplayName').value = editBtn.dataset.display || editBtn.dataset.code;
        document.getElementById('editPriceMode').value = editBtn.dataset.mode || 'ABS';
        document.getElementById('editPriceAmount').value = editBtn.dataset.amount || '0';
        document.getElementById('editSortOrder').value = editBtn.dataset.sort || '';
        const m = new bootstrap.Modal(document.getElementById('editSizeModal'));
        m.show();
      }
    });

    document.getElementById('editSizeForm').addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this);
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
});
    </script>
</body>
</html>