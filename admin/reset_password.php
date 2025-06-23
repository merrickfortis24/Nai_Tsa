<?php
require_once('classes/database.php');
$message = '';
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = new database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $result = $db->resetAdminPasswordByToken($token, $password, $confirm);
        $message = $result['message'];
        $showForm = !$result['success'];
    } else {
        // Use OOP method for token validation
        if ($db->isValidAdminResetToken($token)) {
            $showForm = true;
        } else {
            $message = "Invalid or expired token.";
        }
    }
} else {
    $message = "Invalid reset link.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($showForm): ?>
        <form method="POST">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>