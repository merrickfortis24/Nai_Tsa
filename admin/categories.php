<?php
session_start();
require_once __DIR__ . '/../includes/remember.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');
include 'sidebar_counts.php'; // For sidebar badges/counters

$db = new database();

$error = '';
try {
    $categories = $db->getAllCategories();
} catch (PDOException $e) {
    $error = 'Database Error: ' . $e->getMessage();
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Admin Panel</title>
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
            <!-- Desktop sidebar (visible on md+) -->
            <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
                <?php include 'sidebar.php'; ?>
            </div>
            <!-- Offcanvas sidebar for small screens -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="--bs-offcanvas-width:260px;">
                <div class="offcanvas-body p-0">
                    <?php include 'sidebar.php'; ?>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content">
                <!-- Header -->
                <div class="header d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <h4 class="mb-0 fw-bold">Categories</h4>
                        <p class="mb-0 text-muted">List of all product categories</p>
                    </div>
                    <!-- Sidebar toggle button for small screens (opens offcanvas) -->
                    <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                        <i class="bi bi-list" style="font-size:1.7rem;"></i>
                    </button>
                </div>

                <!-- Categories Card -->
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Categories List</span>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg me-2"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category ID</th>
                                        <th>Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['Category_ID']) ?></td>
                                        <td><?= htmlspecialchars($category['Category_Name']) ?></td>
                                        <td>
                                            <a href="#" class="action-btn edit-category-btn"
                                               data-category-id="<?= htmlspecialchars($category['Category_ID']) ?>"
                                               data-category-name="<?= htmlspecialchars($category['Category_Name']) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="action-btn delete-category-btn"
                                               data-category-id="<?= htmlspecialchars($category['Category_ID']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
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
                <!-- End Categories Card -->
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="addCategoryForm" class="modal-content" action="ajax/add_category.php" method="POST">
          <input type="hidden" id="edit_category_id" name="category_id" value="">
          <div class="modal-header">
            <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="category_name" class="form-label">Category Name</label>
              <input type="text" class="form-control" id="category_name" name="category_name" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Category</button>
          </div>
        </form>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);

    fetch('ajax/add_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Category Added',
                text: data.message
            }).then(() => {
                var modal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
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
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while adding the category.'
        });
    });
});

document.querySelectorAll('.edit-category-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('category_name').value = this.dataset.categoryName;
        document.getElementById('edit_category_id').value = this.dataset.categoryId;
        document.getElementById('addCategoryModalLabel').innerText = 'Edit Category';
        document.querySelector('#addCategoryForm button[type="submit"]').innerText = 'Update Category';
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal'));
        modal.show();
    });
});

document.querySelectorAll('.delete-category-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const categoryId = this.dataset.categoryId;
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the category.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/delete_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'category_id=' + encodeURIComponent(categoryId)
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

// Reset modal on close
document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('addCategoryModalLabel').innerText = 'Add Category';
    document.querySelector('#addCategoryForm button[type="submit"]').innerText = 'Add Category';
    document.getElementById('addCategoryForm').reset();
    document.getElementById('edit_category_id').value = '';
});


    </script>
</body>
</html>