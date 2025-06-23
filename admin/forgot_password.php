<?php
// forgot_password.php
require_once('classes/database.php');
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';

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
            $resetLink = "http://localhost/Nai_Tsa/admin/reset_password.php?token=$token";

            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'live.smtp.mailtrap.io';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'api'; // or 'smtp@mailtrap.io'
                $mail->Password   = 'c8d45abd5fc060ca2c66c324be30ccd6'; // your API token
                $mail->Port       = 587;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

                //Recipients
                $mail->setFrom('hello@demomailtrap.co', 'Nai Tsa Admin');
                $mail->addAddress($email);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Hello,<br><br>Click the following link to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>If you did not request this, please ignore this email.";

                $mail->send();
                $message = "If this email is registered, a password reset link will be sent.";
            } catch (Exception $e) {
                $message = "Failed to send reset email. Mailer Error: {$mail->ErrorInfo}";
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
<body class="login-page">
    <div class="login-container">
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
            <a href="login.php" class="btn btn-link">Back to Login</a>
        </form>
    </div>
</body>
</html>