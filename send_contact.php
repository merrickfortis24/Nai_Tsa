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
    $now = date('Y-m-d H:i:s');

    $mail = mailer_instance();

    $to = mail_primary_to();
    if (!$to) {
        error_log('Contact mail: No valid recipient resolved.');
        contact_redirect('mailcfg');
    }
    $mail->addAddress($to);

    $mail->Subject = 'New Contact Message - ' . ($name ?: 'Website Visitor');
    $plainBody = "New contact message from naitsa.online\n\n"
               . "Name: {$name}\n"
               . "Email: {$email}\n"
               . "When: {$now}\n"
               . "IP: {$ip}\n"
               . "User-Agent: {$ua}\n\n"
               . "Message:\n" . safe_ellipsize($message, 4000);
    $mail->Body    = $plainBody;
    $mail->AltBody = $plainBody;
    $mail->addReplyTo($email, $name ?: $email);

    $sendOk = false;
    try {
        $sendOk = $mail->send();
    } catch (Exception $e) {
        error_log('Contact mail send error (primary): ' . $mail->ErrorInfo);
        $mail->clearAddresses();

        $alternates = [];
        $envTo  = getenv('MAIL_TO') ?: '';
        $envFTo = getenv('MAIL_FORCE_TO') ?: '';
        $envUser= getenv('SMTP_USER') ?: '';
        $envFrom= getenv('MAIL_FROM') ?: '';
        $envCC  = getenv('MAIL_CC') ?: '';
        $envBCC = getenv('MAIL_BCC') ?: '';

        foreach ([$envTo, $envFTo, $envUser, $envFrom] as $addr) {
            if ($addr && $addr !== $to && filter_var($addr, FILTER_VALIDATE_EMAIL)) $alternates[] = $addr;
        }
        foreach (mail_parse_list($envCC) as $cc)  { if ($cc  && $cc  !== $to) $alternates[] = $cc; }
        foreach (mail_parse_list($envBCC) as $bc) { if ($bc  && $bc  !== $to) $alternates[] = $bc; }

        if ($envUser && strpos($envUser, '@') !== false) {
            $domain = substr(strrchr($envUser, '@'), 1);
            $postmaster = 'postmaster@' . $domain;
            if (filter_var($postmaster, FILTER_VALIDATE_EMAIL)) $alternates[] = $postmaster;
        }
        $alternates = array_values(array_unique($alternates));

        if (!empty($alternates)) {
            foreach ($alternates as $alt) { $mail->addAddress($alt); }
            try { $sendOk = $mail->send(); } catch (Exception $e2) {
                error_log('Contact mail send error (alternates): ' . $mail->ErrorInfo);
            }
        }
    }

    if ($sendOk) {
        try {
            $ack = mailer_instance();
            $ack->addAddress($email, $name ?: $email);
            $ack->Subject = 'We received your message';
            $ack->Body    = "Hi {$name},\n\nThanks for reaching out to Nai Tsa! We received your message and will get back to you soon.\n\n— Nai Tsa";
            $ack->AltBody = $ack->Body;
            $ack->send();
        } catch (Exception $e) {
            error_log('Contact ack send error: ' . ($e->getMessage() ?: 'unknown'));
        }
        contact_redirect('success');
    } else {
        contact_redirect('addr');
    }

} catch (Throwable $t) {
    error_log('Contact handler fatal: ' . $t->getMessage());
    contact_redirect('sendfail');
}
