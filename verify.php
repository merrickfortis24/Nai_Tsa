<?php
require_once __DIR__ . '/database/database.php';
$db = new database();
$ok = false;
$message = '';

$token = $_GET['token'] ?? '';
if ($token) {
    $ok = $db->verifyByToken($token);
    if ($ok) {
        $message = 'Your email has been verified. You can now log in.';
    } else {
        $message = 'Invalid or already used verification link.';
    }
} else {
    $message = 'Missing verification token.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Email Verification | Nai Tsa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>
  <section class="login-section d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="login-card text-center" style="max-width:480px;">
      <img class="login-logo" src="assets/naitsalogo.jpg" alt="Nai Tsa Logo">
      <h3 class="mt-3 mb-2">Email Verification</h3>
      <p class="mb-3"><?php echo htmlspecialchars($message); ?></p>
      <a href="login.php" class="btn btn-soft-orange">Go to Login</a>
    </div>
  </section>
</body>
</html>
