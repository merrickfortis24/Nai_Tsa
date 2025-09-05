<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/database/database.php';
require_once __DIR__ . '/utils/mailer.php';

$email = trim($_POST['email'] ?? '');
$name  = trim($_POST['name'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid email']); exit; }

$db = new database();
$user = $db->getUserByEmail($email, 'customer');
if (!$user) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Email not found']); exit; }
if ((int)($user['is_verified'] ?? 0) === 1) { echo json_encode(['ok'=>true,'already'=>true]); exit; }

$otp = $db->issueEmailOtp($email, 6);
if ($otp === false) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Could not generate code']); exit; }

// Send OTP email via PHPMailer
try {
    $mail = mailer_instance();
    $mail->addAddress($email, $name ?: ($user['Customer_Name'] ?? ''));
    $mail->isHTML(true);
    $mail->Subject = 'Your Nai Tsa verification code';
    $code = htmlspecialchars($otp['code']);
    $mail->Body = '<p>Hi ' . htmlspecialchars($name ?: ($user['Customer_Name'] ?? 'there')) . ',</p>' .
                  '<p>Your verification code is:</p>' .
                  '<p style="font-size:22px;font-weight:700;letter-spacing:3px">' . $code . '</p>' .
                  '<p>This code expires in 5 minutes.</p>';
    $mail->AltBody = "Your verification code is: {$otp['code']} (valid for 5 minutes)";
    $mail->send();
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
