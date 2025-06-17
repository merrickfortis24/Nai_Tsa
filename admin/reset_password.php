<?php
require_once('classes/database.php');
$message = '';
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = new database();
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT Admin_ID, Reset_Expires FROM Admin WHERE Reset_Token = ?");
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && strtotime($admin['Reset_Expires']) > time()) {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if ($password && $password === $confirm) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $con->prepare("UPDATE Admin SET Admin_Password = ?, Reset_Token = NULL, Reset_Expires = NULL WHERE Admin_ID = ?");
                $update->execute([$hash, $admin['Admin_ID']]);
                $message = "Password reset successful! <a href='login.php'>Login here</a>.";
                $showForm = false;
            } else {
                $message = "Passwords do not match.";
            }
        }
    } else {
        $message = "Invalid or expired token.";
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