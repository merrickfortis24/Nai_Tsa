<?php
require_once "classes/database.php";
// PHPMailer includes (adjust if PHPMailer folder name differs)
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
// Load mail environment variables (Hostinger SMTP)
if (file_exists(__DIR__ . '/../.mail.env.php')) {
  include __DIR__ . '/../.mail.env.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $db = new database();
        $result = $db->createPasswordResetToken($email);

        if ($result['success']) {
            $token = $result['token'];
      // Build reset link using configured base URL or current host
      $baseUrl = getenv('MAIL_BASE_URL');
      if (!$baseUrl) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Assume app root is domain root; adjust if deployed under subfolder
        $baseUrl = $scheme . '://' . $host;
      }
      $resetLink = rtrim($baseUrl, '/') . '/users/reset_password.php?token=' . urlencode($token);

            $mail = new PHPMailer(true);
            try {
        $mail->isSMTP();
        $host   = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
        $port   = (int)(getenv('SMTP_PORT') ?: 587);
        $secure = strtolower(getenv('SMTP_SECURE') ?: 'tls'); // tls or ssl
        $user   = getenv('SMTP_USER') ?: '';
        $pass   = getenv('SMTP_PASS') ?: '';
        $from   = getenv('MAIL_FROM') ?: $user;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Nai Tsa';

        if (!$user || !$pass || !$from) {
          throw new Exception('SMTP configuration incomplete');
        }
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        if ($secure === 'ssl' || $secure === 'smtps') {
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
          if ($port === 587) { $port = 465; }
        } else {
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = $port;

        $mail->setFrom($from, $fromName);
        $mail->addAddress($email);

                $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $escapedLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $mail->Body = "<div style='font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333;line-height:1.5;'>"
          . "<p>Hello,</p>"
          . "<p>You recently requested to reset your password. Click the button below to continue:</p>"
          . "<p style='text-align:center;margin:28px 0;'>"
          . "<a href='{$escapedLink}' style='display:inline-block;background:#ff7a2f;color:#ffffff !important;text-decoration:none;padding:14px 26px;border-radius:6px;font-weight:600;font-size:16px;font-family:Arial,Helvetica,sans-serif;' target='_blank'>Reset Password</a>"
          . "</p>"
          . "<p>If the button doesn't work, copy and paste this link into your browser:<br>"
          . "<a href='{$escapedLink}' style='color:#ff7a2f;' target='_blank'>{$escapedLink}</a></p>"
          . "<p style='font-size:13px;color:#777;'>If you did not request this reset, you can safely ignore this email. For security reasons the link will expire shortly.</p>"
          . "<hr style='border:none;border-top:1px solid #eee;margin:26px 0;'>"
          . "<p style='font-size:12px;color:#999;margin-top:0;'>© " . date('Y') . " Nai Tsa. All rights reserved.</p>"
          . "</div>";
        $mail->AltBody = "Password Reset Request\n\nVisit this link to reset: $resetLink\nIf you didn't request this, ignore the email.";

                $mail->send();
                $message = "If this email is registered, a password reset link will be sent.";
            } catch (Exception $e) {
        error_log('Forgot password mail error: ' . $e->getMessage());
        $message = "Failed to send reset email.";
            }
        } else {
            $message = "If this email is registered, a password reset link will be sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container" style="max-width:400px;margin:40px auto;padding:30px;background:#fff;border-radius:10px;box-shadow:0 0 10px #b2e0df;">
    <h2>Forgot Password</h2>
    <?php if ($message): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Enter your email address</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Send Reset Link</button>
      <a href="../login.php" class="btn btn-link">Back to Login</a>
    </form>
  </div>
</body>
</html>