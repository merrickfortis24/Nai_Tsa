<?php

session_start();
// Attempt to restore session from remember-me cookie if present
require_once __DIR__ . '/../includes/remember.php';
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
            <!-- Desktop sidebar (visible on md+) -->
            <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
                <?php include 'sidebar.php'; ?>
            </div>
            <!-- Offcanvas sidebar for small screens (moved to end of page) -->
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content">
                <div class="header d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <h4 class="mb-0 fw-bold">Admins</h4>
                        <p class="mb-0 text-muted">List of all administrators</p>
                    </div>
                    <!-- Sidebar toggle button for small screens (opens offcanvas) -->
                    <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                        <i class="bi bi-list" style="font-size:1.7rem;"></i>
                    </button>
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
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th class="text-end">Actions</th>
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
                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary me-1 btn-edit-admin"
                                                    data-admin='<?= json_encode([
                                                        'Admin_ID' => $admin['Admin_ID'],
                                                        'Admin_Name' => $admin['Admin_Name'],
                                                        'Admin_Email' => $admin['Admin_Email'],
                                                        'Admin_Role' => $admin['Admin_Role'],
                                                        'Status' => $admin['Status']
                                                    ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-admin" data-id="<?= (int)$admin['Admin_ID'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
            </div>
        </div>
    </div>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Edit Admin Modal -->
        <div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editAdminForm">
                            <input type="hidden" name="edit_admin_id" id="edit_admin_id">
                            <div class="mb-2">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="edit_admin_name" id="edit_admin_name" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="edit_admin_email" id="edit_admin_email" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="edit_admin_role" id="edit_admin_role" required>
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Manager">Manager</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="edit_status" id="edit_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <label class="form-label">New Password <span class="text-muted small">(optional)</span></label>
                                <input type="password" class="form-control" name="edit_admin_password" id="edit_admin_password" autocomplete="new-password">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="edit_confirm_password" id="edit_confirm_password" autocomplete="new-password">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveAdminBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

                function toast(message, type='info', ms=2500){
                    const timeout = typeof ms === 'number'? ms: 2500;
                    const wrap = document.createElement('div');
                    wrap.className = 'position-fixed top-0 end-0 p-3';
                    wrap.style.zIndex = 1080;
                    wrap.innerHTML = `<div class="alert alert-${type} py-2 px-3 shadow-sm mb-0" role="alert">${message}</div>`;
                    document.body.appendChild(wrap);
                    setTimeout(()=> wrap.remove(), timeout);
                }

                // Edit: open modal with row data
                document.querySelectorAll('.btn-edit-admin').forEach(btn => {
                    btn.addEventListener('click', () => {
                        try {
                            const data = JSON.parse(btn.getAttribute('data-admin'));
                            document.getElementById('edit_admin_id').value = data.Admin_ID || '';
                            document.getElementById('edit_admin_name').value = data.Admin_Name || '';
                            document.getElementById('edit_admin_email').value = data.Admin_Email || '';
                            document.getElementById('edit_admin_role').value = data.Admin_Role || 'Manager';
                            document.getElementById('edit_status').value = data.Status || 'Active';
                            document.getElementById('edit_admin_password').value = '';
                            document.getElementById('edit_confirm_password').value = '';
                            new bootstrap.Modal(document.getElementById('editAdminModal')).show();
                        } catch(e){ toast('Failed to open editor','danger'); }
                    });
                });

                // Save changes via AJAX
                document.getElementById('saveAdminBtn').addEventListener('click', async () => {
                    const form = document.getElementById('editAdminForm');
                    const fd = new FormData(form);
                    try {
                        const res = await fetch('update_admin.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                        const j = await res.json();
                        if (!j.success) { toast(j.message || 'Update failed', 'danger', 4000); return; }
                        toast('Admin updated successfully.', 'success', 3000);
                        // simple reload to reflect changes
                        setTimeout(()=> location.reload(), 800);
                    } catch(e){ toast('Network error', 'danger'); }
                });

                // Delete admin with confirmation
                document.querySelectorAll('.btn-delete-admin').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const id = btn.getAttribute('data-id');
                        if (!id) return;
                        if (!confirm('Are you sure you want to delete this admin? This action cannot be undone.')) return;
                        const fd = new FormData(); fd.append('admin_id', id);
                        try {
                            const res = await fetch('ajax/delete_admin.php', { method:'POST', body: fd, credentials:'same-origin' });
                            const j = await res.json();
                            if (!j.success) { toast(j.message || 'Delete failed', 'danger', 4000); return; }
                            toast('Admin deleted successfully.', 'success', 2500);
                            // Remove row from UI
                            const tr = btn.closest('tr'); if (tr) tr.remove();
                        } catch(e){ toast('Network error', 'danger'); }
                    });
                });
    </script>
</body>
</html>
<?php include 'offcanvas_sidebar.php'; ?>