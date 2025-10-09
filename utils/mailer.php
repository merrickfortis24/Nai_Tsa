<?php
// Simple mailer wrapper around PHPMailer.
// Hostinger SMTP configuration with env loader + safe recipient helpers.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to locate PHPMailer (vendor or bundled)
$candidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../PHPMailer/vendor/autoload.php',
    __DIR__ . '/../PHPMailer/src/PHPMailer.php',
];

$found = null;
foreach ($candidates as $c) {
    if (file_exists($c)) { $found = $c; break; }
}
if ($found && substr($found, -13) === 'autoload.php') {
    require_once $found;
} else {
    $base = dirname($found ?: __DIR__ . '/../PHPMailer/src/PHPMailer.php');
    @require_once $base . '/PHPMailer.php';
    @require_once $base . '/SMTP.php';
    @require_once $base . '/Exception.php';
}

// Load .mail.env.php if present (once per request)
function mail_env_load(): void {
    static $loaded = false;
    if ($loaded) return;
    $paths = [
        __DIR__ . '/../.mail.env.php',   // project root
        __DIR__ . '/.mail.env.php',      // utils folder (fallback)
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) { require_once $p; break; }
    }
    $loaded = true;
}

// Parse comma/semicolon/space-separated list
function mail_parse_list(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return [];
    $parts = preg_split('/[,\s;]+/', $raw);
    return array_values(array_filter(array_unique($parts), fn($s) => filter_var($s, FILTER_VALIDATE_EMAIL)));
}

// Resolve primary recipient in a safe order
function mail_primary_to(): ?string {
    $candidates = [
        getenv('MAIL_FORCE_TO') ?: '',
        getenv('MAIL_TO') ?: '',
        getenv('SMTP_USER') ?: '',
        getenv('MAIL_FROM') ?: '',
    ];
    foreach ($candidates as $addr) {
        if ($addr && filter_var($addr, FILTER_VALIDATE_EMAIL)) return $addr;
    }
    return null;
}

// Build and return a configured PHPMailer instance
function mailer_instance(): PHPMailer {
    mail_env_load();

    $debug = strtolower((string)(getenv('MAIL_DEBUG') ?: '0'));
    $debugOn = in_array($debug, ['1','true','yes','on'], true);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
    $mail->Username = getenv('SMTP_USER') ?: '';
    $mail->Password = getenv('SMTP_PASS') ?: '';
    $mail->Port = (int)(getenv('SMTP_PORT') ?: 465);
    $mail->SMTPSecure = getenv('SMTP_SECURE') ?: ($mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 15;
    $mail->SMTPKeepAlive = false;

    if ($debugOn) {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str) { error_log('[SMTP] ' . trim($str)); };
    }

    $from = getenv('MAIL_FROM') ?: ($mail->Username ?: 'no-reply@naitsa.online');
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Nai Tsa';
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = $mail->Username ?: 'no-reply@naitsa.online';
    }
    $mail->setFrom($from, $fromName, false);

    foreach (mail_parse_list((string)(getenv('MAIL_CC') ?: '')) as $cc)   { $mail->addCC($cc); }
    foreach (mail_parse_list((string)(getenv('MAIL_BCC') ?: '')) as $bcc) { $mail->addBCC($bcc); }

    return $mail;
}
