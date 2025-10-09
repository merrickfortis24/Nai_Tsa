<?php
// Contact form handler using PHPMailer + Hostinger credentials via .mail.env.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/utils/mailer.php';

// Helper: safe ellipsize without requiring mbstring
function safe_ellipsize($str, $max = 500, $ellipsis = '...') {
    $str = trim((string)$str);
    if ($max <= 0) return '';
    if (strlen($str) <= $max) return $str;
    return substr($str, 0, $max - strlen($ellipsis)) . $ellipsis;
}

// Redirect back to landing or users page
function contact_redirect(string $code, string $anchor = '#contact'): void {
    $returnTo = 'index.php';
    if (!empty($_POST['return_to'])) {
        $rt = trim($_POST['return_to']);
        if ($rt === 'index.php' || $rt === 'users/index.php') $returnTo = $rt;
    }
    header('Location: ' . $returnTo . '?contact=' . urlencode($code) . $anchor);
    exit;
}

try {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $hp      = trim($_POST['website'] ?? '');

    if ($hp !== '' || $name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        contact_redirect('invalid');
    }

    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Nai Tsa';
    // 8. Send mail using dedicated contact sender (kept separate from verification)
    try {
        $result = send_contact_email($name, $email, $message, [
            'ip' => $ip,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'now' => date('Y-m-d H:i:s')
        ]);

        if ($result === true) {
            // Acknowledgment to the user (best-effort)
            try {
                $ack = mailer_instance();
                $ack->addAddress($email, $name);
                $ack->Subject = 'Thank you for contacting ' . $fromName;
                $ackBody = "<p>Hi " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p>"
                    . "<p>Thank you for your message. We've received it and will get back to you shortly.</p>"
                    . "<p><strong>Your Message (summary):</strong><br>" . nl2br(htmlspecialchars(safe_ellipsize($message,500,'...'), ENT_QUOTES, 'UTF-8')) . "</p>"
                    . "<p style='font-size:12px;color:#888'>This is an automated acknowledgement. Please do not reply directly; use the website contact form if needed.</p>";
                $ack->isHTML(true);
                $ack->Body    = $ackBody;
                $ack->AltBody = "Thank you for contacting $fromName. We received your message.\n\nMessage snippet:\n" . safe_ellipsize($message,500,'...');
                $ack->send();
            } catch (Exception $e2) { error_log('Ack mail failed: ' . $e2->getMessage()); }
            contact_redirect('success');
        }

        // Map result codes/messages to UI
        $code = 'sendfail';
        if ($result === 'mailcfg') {
            $code = 'mailcfg';
        } elseif ($result === 'addr') {
            $code = 'addr';
        } elseif (is_string($result)) {
            $low = strtolower($result);
            if (strpos($low, 'authenticate') !== false) {
                $code = 'auth';
            } elseif (strpos($low, 'connect') !== false || strpos($low, 'timed out') !== false) {
                $code = 'connect';
            } elseif (strpos($low, 'certificate') !== false || strpos($low, 'verify failed') !== false) {
                $code = 'cert';
            } elseif (strpos($low, 'invalid address') !== false || strpos($low, 'data not accepted') !== false || strpos($low, 'recipient rejected') !== false) {
                $code = 'addr';
            }
        }
        contact_redirect($code);
    } catch (Exception $e) {
        error_log('Contact form handler error: ' . $e->getMessage());
        contact_redirect('sendfail');
    }
    // end outer try
