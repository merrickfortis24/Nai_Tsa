<?php
// Password Reset Handler Page
// URL format: /users/reset_password.php?token=.... (sent from forgot_password.php)

require_once __DIR__ . '/classes/database.php';

// Optional: load mail env (for consistency / future logging base URL etc.)
if (file_exists(__DIR__ . '/../.mail.env.php')) {
    include __DIR__ . '/../.mail.env.php';
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$db = new database();
$pdo = $db->opencon();

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';
$redirectAfter = false; // flag when success triggers redirect

// Basic token sanity check
if ($token && !preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
    $error = 'Invalid reset token format.';
}

// On POST we attempt to update password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Password complexity rules
    $minLen = 8;
    $needUpper = true; $needLower = true; $needDigit = true; $needSpecial = true;

    if (!$token) {
        $error = 'Missing reset token.';
    } elseif (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
        $error = 'Invalid reset token.';
    } elseif (strlen($password) < $minLen) {
        $error = 'Password must be at least ' . $minLen . ' characters.';
    } elseif ($needUpper && !preg_match('/[A-Z]/', $password)) {
        $error = 'Password needs at least one uppercase letter.';
    } elseif ($needLower && !preg_match('/[a-z]/', $password)) {
        $error = 'Password needs at least one lowercase letter.';
    } elseif ($needDigit && !preg_match('/\d/', $password)) {
        $error = 'Password needs at least one number.';
    } elseif ($needSpecial && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password needs at least one special character.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT Customer_ID, reset_expires FROM customer WHERE reset_token = ? LIMIT 1');
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $error = 'Invalid or already used reset link.';
            } elseif ($row['reset_expires'] && strtotime($row['reset_expires']) < time()) {
                $error = 'Reset link has expired. Please request a new one.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare('UPDATE customer SET Customer_Password = ?, reset_token = NULL, reset_expires = NULL WHERE Customer_ID = ?');
                $upd->execute([$hash, $row['Customer_ID']]);
                $success = 'Password updated successfully. Redirecting to login...';
                $redirectAfter = true;
            }
        } catch (Exception $e) {
            error_log('Reset password error: ' . $e->getMessage());
            $error = 'An internal error occurred.';
        }
    }
}

// For GET (initial view) validate token exists and not expired before showing form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$error && !$success) {
    if (!$token) {
        $error = 'Reset token missing.';
    } else {
        $stmt = $pdo->prepare('SELECT reset_expires FROM customer WHERE reset_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'Reset link is invalid or has already been used.';
        } elseif ($row['reset_expires'] && strtotime($row['reset_expires']) < time()) {
            $error = 'Reset link has expired. Please request a new one.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background:#f5f7fa; }
        .card { border:0; box-shadow:0 4px 14px rgba(0,0,0,.07); }
        .btn-primary { background:#ff7a2f; border-color:#ff7a2f; }
        .btn-primary:hover { background:#e5671d; border-color:#e5671d; }
        a.brand { text-decoration:none; color:#ff7a2f; font-weight:600; }
        ul.pw-rules { margin:0 0 8px 18px; padding:0; font-size:12px; color:#555; }
        ul.pw-rules li { list-style:disc; }
    </style>
</head>
<body>
<div class="container" style="max-width:480px; margin:50px auto;">
    <div class="card p-4">
        <h3 class="mb-3">Reset Password</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php if ($error && !$success): ?>
                <p class="small mb-3">You can <a href="forgot_password.php">request a new reset link</a>.</p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
            <noscript>
                <a href="../login.php" class="btn btn-success w-100">Go to Login</a>
            </noscript>
        <?php endif; ?>

        <?php if (!$success && !$error): ?>
        <form method="POST" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
            <div class="mb-3">
                <label class="form-label" for="password">New Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="8" required />
                <div class="form-text">
                    Must include:
                    <ul class="pw-rules">
                        <li>At least 8 characters</li>
                        <li>Upper & lowercase letters</li>
                        <li>At least one number</li>
                        <li>At least one symbol (!@#$ etc.)</li>
                    </ul>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required />
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Password</button>
            <div class="text-center mt-3">
                <a href="../login.php" class="small">Back to Login</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <p class="text-center mt-3 small">&copy; <?= date('Y') ?> Nai Tsa</p>
</div>
<?php if ($redirectAfter): ?>
<script>
    setTimeout(function(){ window.location.href = '../login.php'; }, 3500);
    // Basic password strength meter (optional UX)
</script>
<?php endif; ?>
</body>
</html>
<?php
// End of file
?>
