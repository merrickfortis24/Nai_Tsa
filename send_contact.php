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
// Determine safe return target (default to public index)
$returnTo = 'index.php';
if (isset($_POST['return_to'])) {
    $cand = trim((string)$_POST['return_to']);
    // Allow only relative paths within this site to avoid open redirects
    if ($cand !== '' && !preg_match('~^https?://~i', $cand) && strpos($cand, "\0") === false) {
        // Normalize simple directory traversal attempts
        $cand = ltrim($cand, '/\\');
        if ($cand === 'users/index.php' || $cand === 'index.php') {
            $returnTo = $cand;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $returnTo . '#contact'); exit; }

// 3. Honeypot anti-spam
if (!empty($_POST['website'])) { header('Location: ' . $returnTo . '?contact=success#contact'); exit; }

// 4. Sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { header('Location: ' . $returnTo . '?contact=invalid#contact'); exit; }
if (strlen($name) > 100 || strlen($email) > 150 || strlen($message) > 1000) { header('Location: ' . $returnTo . '?contact=invalid#contact'); exit; }

// 5. Rate limiting removed (allow unlimited submissions)

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
$explicitTo= getenv('MAIL_TO') ?: '';
$ccList    = getenv('MAIL_CC') ?: '';
$bccList   = getenv('MAIL_BCC') ?: '';
$debugFlag = getenv('MAIL_DEBUG');
// IP captured for inclusion in the email body (no rate limiting)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
// Always use the shared mailer like send verification
try {
    require_once __DIR__ . '/utils/mailer.php';
} catch (Throwable $te) {
    error_log('utils/mailer.php missing: ' . $te->getMessage());
    header('Location: ' . $returnTo . '?contact=mailcfg#contact');
    exit;
}

// 8. Send mail
$mail = null;
try {
    $mail = mailer_instance();
    if (isset($_GET['debug_mail']) || $debugFlag) { $mail->SMTPDebug = 2; $mail->Debugoutput = 'error_log'; }

    // Primary recipient
    $primaryTo = $forceTo ?: ($explicitTo ?: ($user ?: $from));
    $mail->addAddress($primaryTo, 'Contact Inbox');
    // Optional CC/BCC additional recipients (comma/semicolon separated)
    $__cc = array_filter(array_map('trim', preg_split('/[;,]+/', (string)$ccList, -1, PREG_SPLIT_NO_EMPTY)));
    foreach ($__cc as $cc) { $mail->addCC($cc); }
    $__bcc = array_filter(array_map('trim', preg_split('/[;,]+/', (string)$bccList, -1, PREG_SPLIT_NO_EMPTY)));
    foreach ($__bcc as $bcc) { $mail->addBCC($bcc); }
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'New Contact Message from ' . $name;
    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $mail->Body = "<h3>New Contact Submission</h3><p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p><p><strong>Message:</strong><br>{$safeMsg}</p><hr><p style='font-size:12px;color:#888'>IP: " . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</p>";
    $mail->AltBody = "Name: {$name}\nEmail: {$email}\nMessage:\n{$message}\nIP: {$ip}";

    $sent = false; $sendErr = null;
    try {
        if (!$mail->send()) { throw new Exception($mail->ErrorInfo ?: 'send failed'); }
        $sent = true;
    } catch (Exception $e1) {
        $sendErr = $e1;
        $low1 = strtolower($e1->getMessage() ?: '');
        $recipientRejected = (strpos($low1,'invalid address')!==false) || (strpos($low1,'recipient rejected')!==false) || (strpos($low1,'data not accepted')!==false) || (strpos($low1,'mailbox unavailable')!==false) || (strpos($low1,'user unknown')!==false) || (strpos($low1,'no such user')!==false) || (strpos($low1,'relay denied')!==false);
        if ($recipientRejected) {
            $fallbackCandidates = [];
            // Try configured alternates first
            if ($explicitTo && strcasecmp($explicitTo, $primaryTo)!==0) { $fallbackCandidates[] = $explicitTo; }
            if ($forceTo && strcasecmp($forceTo, $primaryTo)!==0) { $fallbackCandidates[] = $forceTo; }
            if ($user && strcasecmp($user, $primaryTo)!==0) { $fallbackCandidates[] = $user; }
            if ($from && strcasecmp($from, $primaryTo)!==0) { $fallbackCandidates[] = $from; }
            // Then first CC/BCC, if any
            if (!empty($__cc)) { $fallbackCandidates[] = $__cc[0]; }
            if (!empty($__bcc)) { $fallbackCandidates[] = $__bcc[0]; }
            // As a last resort, try postmaster@<smtp_user_domain>
            $postmaster = '';
            if (strpos($user, '@') !== false) {
                $domain = substr(strrchr($user, '@'), 1);
                if ($domain) { $postmaster = 'postmaster@' . $domain; }
            }
            if ($postmaster && strcasecmp($postmaster, $primaryTo)!==0) { $fallbackCandidates[] = $postmaster; }
            $fallbackCandidates = array_values(array_unique($fallbackCandidates, SORT_STRING));

            foreach ($fallbackCandidates as $fb) {
                try {
                    $fbMailer = mailer_instance();
                    if (isset($_GET['debug_mail']) || $debugFlag) { $fbMailer->SMTPDebug = 2; $fbMailer->Debugoutput = 'error_log'; }
                    $fbMailer->addAddress($fb);
                    $fbMailer->addReplyTo($email, $name);
                    $fbMailer->isHTML(true);
                    $fbMailer->Subject = '[Fallback] New Contact Message from ' . $name;
                    $fbMailer->Body = $mail->Body;
                    $fbMailer->AltBody = $mail->AltBody;
                    if ($fbMailer->send()) { $sent = true; break; }
                } catch (Exception $eFb) {
                    $sendErr = $eFb; // keep last error
                }
            }
        }
        if (!$sent) { throw $sendErr ?: $e1; }
    }

    // Send acknowledgment to the user (separate mail instance for clean headers)
    try {
        $ack = mailer_instance();
        if (isset($_GET['debug_mail']) || $debugFlag) { $ack->SMTPDebug = 0; }
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

    header('Location: ' . $returnTo . '?contact=success#contact');
} catch (Exception $e) {
    $msg = $e->getMessage();
    error_log('Contact form mail error: ' . $msg);
    $code = 'sendfail';
    $low = strtolower($msg);
    if (strpos($low, 'authenticate') !== false) {
        $code = 'auth';
    } elseif (strpos($low, 'connect') !== false || strpos($low, 'timed out') !== false) {
        $code = 'connect';
    } elseif (strpos($low, 'certificate') !== false || strpos($low, 'verify failed') !== false) {
        $code = 'cert';
    } elseif (strpos($low, 'invalid address') !== false || strpos($low, 'data not accepted') !== false || strpos($low, 'recipient rejected') !== false) {
        $code = 'addr';
    }
    header('Location: ' . $returnTo . '?contact=' . $code . '#contact');
}
exit;
