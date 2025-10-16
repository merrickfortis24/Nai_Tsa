<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
$require_path = __DIR__ . '/classes/database.php';
require_once $require_path;
$db = new database();
$con = $db->opencon();

// Handle Add/Edit/Delete
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name']);
    $gmail = trim($_POST['gmail']);
        $pass = $_POST['password'];
        if ($name && $gmail && $pass) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $con->prepare("INSERT INTO drivers (Name, Gmail, Password_Hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $gmail, $hash]);
            $msg = "Driver added.";
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['driver_id'];
        $name = trim($_POST['name']);
    $gmail = trim($_POST['gmail']);
        $pass = $_POST['password'];
        if ($id && $name && $gmail) {
            if ($pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $con->prepare("UPDATE drivers SET Name=?, Gmail=?, Password_Hash=? WHERE Driver_ID=?");
                $stmt->execute([$name, $gmail, $hash, $id]);
            } else {
                $stmt = $con->prepare("UPDATE drivers SET Name=?, Gmail=? WHERE Driver_ID=?");
                $stmt->execute([$name, $gmail, $id]);
            }
            $msg = "Driver updated.";
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['driver_id'];
        if ($id) {
            $stmt = $con->prepare("DELETE FROM drivers WHERE Driver_ID=?");
            $stmt->execute([$id]);
            $msg = "Driver deleted.";
        }
    }
}

// Fetch all drivers
$drivers = $con->query("SELECT * FROM drivers ORDER BY Driver_ID DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drivers - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
            <div class="header d-flex justify-content-between align-items-center mt-3">
                <!-- Mobile burger: toggles the sidebar collapse on small screens -->
                <button class="btn btn-sm btn-outline-secondary d-md-none me-2 mobile-burger"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target=".sidebar"
                        aria-controls="sidebar"
                        aria-expanded="false"
                        aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h4 class="mb-0 fw-bold">Drivers</h4>
                    <p class="mb-0 text-muted">Manage delivery drivers</p>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Add Driver</div>
                <div class="card-body">
                    <form class="row g-2" method="post">
                        <input type="hidden" name="add" value="1">
                        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Name" required></div>
                        <div class="col-md-3"><input type="email" name="gmail" class="form-control" placeholder="Gmail" required></div>
                        <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-primary">Add Driver</button></div>
                    </form>
                </div>
            </div>
            <?php if ($msg): ?>
                <div class="alert alert-success mt-3"><?=htmlspecialchars($msg)?></div>
            <?php endif; ?>
            <div class="card mt-4">
                <div class="card-header">Driver List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Gmail</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($drivers as $d): ?>
                                <tr>
                                    <td><?= $d['Driver_ID'] ?></td>
                                    <td><?= htmlspecialchars($d['Name']) ?></td>
                                    <td><?= htmlspecialchars($d['Gmail']) ?></td>
                                    <td>
                                        <form method="post" style="display:inline-block">
                                            <input type="hidden" name="driver_id" value="<?= $d['Driver_ID'] ?>">
                                            <input type="hidden" name="name" value="<?= htmlspecialchars($d['Name']) ?>">
                                            <input type="hidden" name="gmail" value="<?= htmlspecialchars($d['Gmail']) ?>">
                                            <button type="button" class="btn btn-sm btn-warning" onclick="showEdit(this.form)"><i class="bi bi-pencil"></i></button>
                                        </form>
                                        <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this driver?')">
                                            <input type="hidden" name="driver_id" value="<?= $d['Driver_ID'] ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Driver</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="edit" value="1">
                                <input type="hidden" name="driver_id" id="edit_id">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gmail</label>
                                    <input type="email" class="form-control" name="gmail" id="edit_gmail" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" name="password">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showEdit(form) {
    document.getElementById('edit_id').value = form.driver_id.value;
    document.getElementById('edit_name').value = form.name.value;
    document.getElementById('edit_gmail').value = form.gmail.value;
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}
</script>
</body>
</html>
