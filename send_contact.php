<?php
// Simple contact form handler using Hostinger email (PHPMailer)
// Adjust SMTP credentials below. Keep this file outside public repo if storing secrets.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Attempt to locate PHPMailer library in multiple possible deployment paths
$phpmailerSrc = null;
$candidates = [
    __DIR__ . '/PHPMailer-master/src',          // same directory (if copied inside Nai_Tsa or public_html)
    __DIR__ . '/../PHPMailer-master/src',       // parent directory (original repo layout)
    __DIR__ . '/PHPMailer/src',                 // alternative folder name
    __DIR__ . '/../PHPMailer/src',
];
foreach ($candidates as $cand) {
    if (is_dir($cand) && file_exists($cand . '/PHPMailer.php')) { $phpmailerSrc = $cand; break; }
}
if (!$phpmailerSrc) {
    http_response_code(500);
    echo 'Mail library missing. Upload PHPMailer-master folder to the same directory (public_html) or parent.';
    exit;
}
require_once $phpmailerSrc . '/Exception.php';
require_once $phpmailerSrc . '/PHPMailer.php';
require_once $phpmailerSrc . '/SMTP.php';

// Basic rate limit (per IP) using temp file (simple, not bulletproof)
function rate_limited($ip, $limit = 5, $windowSeconds = 3600) {
    $file = sys_get_temp_dir() . '/contact_rate_' . preg_replace('/[^A-Fa-f0-9:]/','_', $ip);
    $now = time();
    $data = ['start'=>$now,'count'=>0];
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw) {
            $d = json_decode($raw,true);
            if (isset($d['start'],$d['count'])) {
                $data = $d;
            }
        }
    }
    if (($now - $data['start']) > $windowSeconds) {
        $data = ['start'=>$now,'count'=>0];
    }
    if ($data['count'] >= $limit) return true;
    $data['count']++;
    file_put_contents($file, json_encode($data));
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#contact');
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    header('Location: index.php?contact=success#contact'); // pretend success
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?contact=error#contact');
    exit;
}

if (strlen($name) > 100 || strlen($email) > 150 || strlen($message) > 1000) {
    header('Location: index.php?contact=error#contact');
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (rate_limited($ip)) {
    header('Location: index.php?contact=error#contact');
    exit;
}

$mail = new PHPMailer(true);
try {
    // SMTP settings (Hostinger typical values)
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'no-reply@naitsa.online'; // TODO: change to your mailbox (existing Hostinger email)
    // Use environment variable for security; set NAITSA_SMTP_PASS in hosting panel
    $mail->Password   = getenv('NAITSA_SMTP_PASS') ?: 'CHANGE_ME_SECURE_PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS with port 465
    $mail->Port       = 587; // use 465 if using SMTPS
    $mail->CharSet    = 'UTF-8';

    // From & To
    $mail->setFrom('no-reply@naitsa.online', 'Nai Tsa Website');
    $mail->addAddress('support@naitsa.online', 'Nai Tsa Support'); // where you receive
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'New Contact Message from ' . $name;
    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $mail->Body = "<h3>New Contact Submission</h3><p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p><p><strong>Message:</strong><br>{$safeMsg}</p><hr><p style='font-size:12px;color:#888'>IP: " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</p>";
    $mail->AltBody = "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}\nIP: {$ip}";

    $mail->send();
    header('Location: index.php?contact=success#contact');
} catch (Exception $e) {
    // Log error (optional) - avoid exposing details
    error_log('Contact form mail error: ' . $e->getMessage());
    header('Location: index.php?contact=error#contact');
}
exit;
