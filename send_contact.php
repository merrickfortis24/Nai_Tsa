<?php
// Contact form handler using PHPMailer + Hostinger credentials via .mail.env.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper: safe ellipsize without requiring mbstring
function safe_ellipsize($str, $max = 500, $ellipsis = '...') {
    if ($max <= 0) return '';
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($str, 0, $max, $ellipsis, 'UTF-8');
    }
    if (strlen($str) <= $max) return $str;
    $cut = max(0, $max - strlen($ellipsis));
    return substr($str, 0, $cut) . $ellipsis;
}

// 1. Locate PHPMailer sources
$phpmailerSrc = null;
foreach ([
    __DIR__ . '/PHPMailer-master/src',
    __DIR__ . '/../PHPMailer-master/src',
    __DIR__ . '/PHPMailer/src',
    __DIR__ . '/../PHPMailer/src',
] as $cand) {
    if (is_dir($cand) && file_exists($cand . '/PHPMailer.php')) { $phpmailerSrc = $cand; break; }
}
if (!$phpmailerSrc) { http_response_code(500); echo 'PHPMailer missing'; exit; }
require_once $phpmailerSrc . '/Exception.php';
require_once $phpmailerSrc . '/PHPMailer.php';
require_once $phpmailerSrc . '/SMTP.php';

// 2. Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php#contact'); exit; }

// 3. Honeypot anti-spam
if (!empty($_POST['website'])) { header('Location: index.php?contact=success#contact'); exit; }

// 4. Sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { header('Location: index.php?contact=error#contact'); exit; }
if (strlen($name) > 100 || strlen($email) > 150 || strlen($message) > 1000) { header('Location: index.php?contact=error#contact'); exit; }

// 5. Simple rate limit (per IP)
function rate_limited($ip, $limit = 5, $windowSeconds = 3600) {
    $file = sys_get_temp_dir() . '/contact_rate_' . preg_replace('/[^A-Fa-f0-9:]/','_', $ip);
    $now = time();
    $data = ['start'=>$now,'count'=>0];
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw) { $d = json_decode($raw, true); if (isset($d['start'],$d['count'])) $data = $d; }
    }
    if (($now - $data['start']) > $windowSeconds) { $data = ['start'=>$now,'count'=>0]; }
    if ($data['count'] >= $limit) return true;
    $data['count']++;
    file_put_contents($file, json_encode($data));
    return false;
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (rate_limited($ip)) { header('Location: index.php?contact=error#contact'); exit; }

// 6. Load .mail.env.php if present (sets putenv values)
if (file_exists(__DIR__ . '/.mail.env.php')) { include __DIR__ . '/.mail.env.php'; }

// 7. Read env config
$host      = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
$port      = (int)(getenv('SMTP_PORT') ?: 587);
$secure    = strtolower(getenv('SMTP_SECURE') ?: 'tls'); // tls or ssl
$user      = getenv('SMTP_USER') ?: '';
$pass      = getenv('SMTP_PASS') ?: '';
$from      = getenv('MAIL_FROM') ?: $user;
$fromName  = getenv('MAIL_FROM_NAME') ?: 'Website';
$forceTo   = getenv('MAIL_FORCE_TO') ?: '';
$debugFlag = getenv('MAIL_DEBUG');
// Fallback: if env config is missing, try the shared utils\mailer.php
$usingUtilsMailer = false;
if (!$user || !$pass || !$from) {
    try {
        require_once __DIR__ . '/utils/mailer.php';
        $usingUtilsMailer = true; // we'll construct PHPMailer via mailer_instance() below
    } catch (Throwable $te) {
        error_log('Mail config missing and utils/mailer.php not available: ' . $te->getMessage());
        header('Location: index.php?contact=error#contact');
        exit;
    }
}

// 8. Send mail
$mail = null;
try {
    if ($usingUtilsMailer) {
        // Use centralized mailer config
        $mail = mailer_instance();
    } else {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host     = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        if ($secure === 'ssl' || $secure === 'smtps') { $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; if ($port === 587) { $port = 465; } }
        else { $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; }
        $mail->Port    = $port;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, $fromName);
    }
    if (isset($_GET['debug_mail']) || $debugFlag) { $mail->SMTPDebug = 2; $mail->Debugoutput = 'error_log'; }

    if ($forceTo) { $mail->addAddress($forceTo, 'Forced Recipient'); }
    else { $mail->addAddress($from, $fromName); }
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'New Contact Message from ' . $name;
    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $mail->Body = "<h3>New Contact Submission</h3><p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p><p><strong>Message:</strong><br>{$safeMsg}</p><hr><p style='font-size:12px;color:#888'>IP: " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</p>";
    $mail->AltBody = "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}\nIP: {$ip}";

    if (!$mail->send()) { throw new Exception($mail->ErrorInfo); }

    // Send acknowledgment to the user (separate mail instance for clean headers)
    try {
        $ack = $usingUtilsMailer ? mailer_instance() : new PHPMailer(true);
        if (!$usingUtilsMailer) {
            if (isset($_GET['debug_mail']) || $debugFlag) { $ack->SMTPDebug = 0; }
            $ack->isSMTP();
            $ack->Host       = $host;
            $ack->SMTPAuth   = true;
            $ack->Username   = $user;
            $ack->Password   = $pass;
            if ($secure === 'ssl' || $secure === 'smtps') { $ack->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $ack->Port = $port; }
            else { $ack->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $ack->Port = $port; }
            $ack->CharSet = 'UTF-8';
            $ack->setFrom($from, $fromName);
        }
        $ack->addAddress($email, $name);
        $ack->Subject = 'Thank you for contacting ' . $fromName;
        $ackBody = "<p>Hi " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p>"
            . "<p>Thank you for your message. We've received it and will get back to you shortly.</p>"
            . "<p><strong>Your Message (summary):</strong><br>" . nl2br(htmlspecialchars(safe_ellipsize($message,500,'...'), ENT_QUOTES, 'UTF-8')) . "</p>"
            . "<p style='font-size:12px;color:#888'>This is an automated acknowledgement. Please do not reply directly; use the website contact form if needed.</p>";
        $ack->isHTML(true);
        $ack->Body    = $ackBody;
        $ack->AltBody = "Thank you for contacting $fromName. We received your message.\n\nMessage snippet:\n" . safe_ellipsize($message,500,'...');
        // Ignore failure of acknowledgment to user (no throw)
        $ack->send();
    } catch (Exception $e2) {
        error_log('Ack mail failed: ' . $e2->getMessage());
    }

    header('Location: index.php?contact=success#contact');
} catch (Exception $e) {
    error_log('Contact form mail error: ' . $e->getMessage());
    header('Location: index.php?contact=error#contact');
}
exit;
