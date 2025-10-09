<?php
// Simple mailer wrapper around PHPMailer.
// Hostinger-only SMTP configuration (no Mailtrap modes).
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Locate PHPMailer sources in common locations (project layouts may vary)
$candidates = [
    __DIR__ . '/../../PHPMailer-master/src',           // repo root sibling to Nai_Tsa
    __DIR__ . '/../PHPMailer-master/src',              // PHPMailer inside Nai_Tsa
    __DIR__ . '/../vendor/phpmailer/phpmailer/src',    // Composer vendor
    __DIR__ . '/PHPMailer-master/src',                 // same folder (unlikely)
];

$found = null;
foreach ($candidates as $dir) {
    if (is_dir($dir)) { $found = $dir; break; }
}

if ($found === null) {
    // Provide a clear error for setup fixes
    throw new \RuntimeException('PHPMailer sources not found. Place PHPMailer-master/ at project root or install via Composer. Tried: ' . implode(', ', $candidates));
}

require_once $found . '/Exception.php';
require_once $found . '/PHPMailer.php';
require_once $found . '/SMTP.php';

function mailer_instance(): PHPMailer {
    // Optional local config bootstrap: if a file sets env vars, include it.
    // Some hosts skip dotfiles on upload; support alternate names.
    $envCandidates = [
        __DIR__ . '/../.mail.env.php',
        __DIR__ . '/../mail.env.php',
        __DIR__ . '/../config/.mail.env.php',
        __DIR__ . '/../config/mail.env.php',
    ];
    foreach ($envCandidates as $envFile) {
        if (is_file($envFile)) {
            /** @noinspection PhpIncludeInspection */
            include $envFile; // may call putenv("KEY=value")
            break;
        }
    }

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    // Reasonable defaults for network behavior
    $mail->Timeout = (int)(getenv('MAIL_TIMEOUT') ?: 20);
    $mail->SMTPAutoTLS = true;

    // Hostinger SMTP (generic SMTP). Set in .mail.env.php
    $mail->Host     = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
    $mail->Port     = (int)(getenv('SMTP_PORT') ?: 587);
    $mail->Username = getenv('SMTP_USER') ?: 'hello@naitsa.online';
    $mail->Password = getenv('SMTP_PASS') ?: 'Naitsa@123';
    if ($mail->Username === '' || $mail->Password === '') {
        throw new \RuntimeException('SMTP_USER/SMTP_PASS not set. Upload Nai_Tsa/.mail.env.php (or rename to mail.env.php) with your mailbox credentials.');
    }
    $mail->SMTPAuth = true;

    // Pick encryption based on port, with override via SMTP_SECURE env
    $secure = strtolower((string)getenv('SMTP_SECURE'));
    if ($secure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secure === 'tls' || $secure === '') {
        $mail->SMTPSecure = ($mail->Port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    // From header defaults to mailbox if not provided
    $fromAddress = getenv('MAIL_FROM') ?: ($mail->Username ?: 'no-reply@example.com');

    $fromName = getenv('MAIL_FROM_NAME') ?: 'Nai Tsa';
    $mail->setFrom($fromAddress, $fromName);
    // Envelope-from (Return-Path)
    $mail->Sender = $fromAddress;

    if (getenv('MAIL_DEBUG')) {
        // 2 = client and server messages. Logged to error_log by default.
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = 'error_log';
    }

    return $mail;
}

function send_verification_email(string $to, string $token) {
    $mail = mailer_instance();
    try {
        // Optional override to force all outbound test emails to a single mailbox.
        $forcedTo = getenv('MAIL_FORCE_TO');
        $target = $forcedTo ?: $to;
        $mail->addAddress($target);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your Nai Tsa account';
        $baseUrl = getenv('MAIL_BASE_URL');
        if ($baseUrl) {
            $verifyUrl = rtrim($baseUrl, '/') . '/verify.php?token=' . urlencode($token);
        } else {
            $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/verify.php?token=' . urlencode($token);
        }
    $mail->Body = '<p>Welcome to Nai Tsa!</p><p>Please verify your email by clicking the link below:</p><p><a href="' . htmlspecialchars($verifyUrl) . '">Verify my email</a></p><p>If you did not sign up, you can ignore this email.</p>';
        $mail->AltBody = "Visit this link to verify your email: $verifyUrl";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
