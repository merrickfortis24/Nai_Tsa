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

/**
 * Send a contact message email using site SMTP settings.
 * Kept separate from verification to avoid coupling behaviors.
 * Returns true on success; returns the string 'addr' if all recipients were rejected; otherwise returns error message.
 */
function send_contact_email(string $senderName, string $senderEmail, string $message, array $context = []) {
    $mail = mailer_instance();
    // Resolve primary recipient: MAIL_FORCE_TO → MAIL_TO → SMTP_USER → MAIL_FROM
    $envForce = trim((string)(getenv('MAIL_FORCE_TO') ?: ''));
    $envTo    = trim((string)(getenv('MAIL_TO') ?: ''));
    $envUser  = trim((string)(getenv('SMTP_USER') ?: ''));
    $envFrom  = trim((string)(getenv('MAIL_FROM') ?: ''));
    // Also consider configured PHPMailer props as fallback if envs are empty
    $cfgUser  = trim((string)($mail->Username ?? ''));
    $cfgFrom  = trim((string)($mail->From ?? ''));
    $primaryTo = $envForce ?: ($envTo ?: ($envUser ?: ($envFrom ?: ($cfgUser ?: $cfgFrom))));
    if (filter_var((string)getenv('MAIL_CONTACT_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
        error_log('[contact] primaryTo=' . ($primaryTo ?: 'EMPTY') . ' envForce=' . ($envForce ?: '-') . ' envTo=' . ($envTo ?: '-') . ' envUser=' . ($envUser ?: '-') . ' envFrom=' . ($envFrom ?: '-') . ' cfgUser=' . ($cfgUser ?: '-') . ' cfgFrom=' . ($cfgFrom ?: '-'));
    }
    if (!$primaryTo || !filter_var($primaryTo, FILTER_VALIDATE_EMAIL)) {
        return 'mailcfg';
    }
    try {
        $mail->addAddress($primaryTo);
        $mail->addReplyTo($senderEmail, $senderName !== '' ? $senderName : $senderEmail);
        $mail->isHTML(true);
        $ip  = $context['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ua  = $context['ua'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $now = $context['now'] ?? date('Y-m-d H:i:s');
        $safe = function($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
        $mail->Subject = 'New Contact Message - ' . ($senderName !== '' ? $safe($senderName) : 'Website Visitor');
        $htmlMsg = nl2br($safe($message));
        $mail->Body = '<h3>New Contact Submission</h3>'
            . '<p><strong>Name:</strong> ' . $safe($senderName) . '<br><strong>Email:</strong> ' . $safe($senderEmail) . '</p>'
            . '<p><strong>Message:</strong><br>' . $htmlMsg . '</p>'
            . '<hr><p style="font-size:12px;color:#888">When: ' . $safe($now) . ' | IP: ' . $safe($ip) . '<br><span style="word-break:break-word">UA: ' . $safe($ua) . '</span></p>';
        $mail->AltBody = "Name: $senderName\nEmail: $senderEmail\nWhen: $now\nIP: $ip\nUA: $ua\n\nMessage:\n$message";

        // Send with fallback on RCPT TO rejections
        try {
            if ($mail->send()) { return true; }
            throw new Exception($mail->ErrorInfo ?: 'send failed');
        } catch (Exception $e1) {
            // Build alternates
            $alternates = [];
            $explicitTo = getenv('MAIL_TO') ?: '';
            $forceTo    = getenv('MAIL_FORCE_TO') ?: '';
            $user       = getenv('SMTP_USER') ?: '';
            $from       = getenv('MAIL_FROM') ?: '';
            $ccList     = getenv('MAIL_CC') ?: '';
            $bccList    = getenv('MAIL_BCC') ?: '';
            $push = function($addr) use (&$alternates, $primaryTo) {
                if ($addr && strcasecmp($addr, $primaryTo)!==0 && filter_var($addr, FILTER_VALIDATE_EMAIL)) { $alternates[] = $addr; }
            };
            $push($explicitTo); $push($forceTo); $push($user); $push($from);
            foreach (preg_split('/[;,\s]+/', (string)$ccList, -1, PREG_SPLIT_NO_EMPTY) as $cc) { $push(trim($cc)); }
            foreach (preg_split('/[;,\s]+/', (string)$bccList, -1, PREG_SPLIT_NO_EMPTY) as $bcc) { $push(trim($bcc)); }
            // postmaster@domain fallback
            if (strpos($user, '@') !== false) {
                $domain = substr(strrchr($user, '@'), 1);
                if ($domain) { $push('postmaster@' . $domain); }
            }
            $alternates = array_values(array_unique($alternates, SORT_STRING));

            foreach ($alternates as $alt) {
                try {
                    $fb = mailer_instance();
                    $fb->addAddress($alt);
                    $fb->addReplyTo($senderEmail, $senderName !== '' ? $senderName : $senderEmail);
                    $fb->isHTML(true);
                    $fb->Subject = '[Fallback] ' . $mail->Subject;
                    $fb->Body    = $mail->Body;
                    $fb->AltBody = $mail->AltBody;
                    if ($fb->send()) { return true; }
                } catch (Exception $eFb) {
                    // continue trying
                }
            }

            // All failed; classify as recipient address problem if message indicates typical RCPT issues
            $low = strtolower($e1->getMessage() ?: '');
            $isAddr = (strpos($low,'invalid address')!==false) || (strpos($low,'recipient rejected')!==false) || (strpos($low,'data not accepted')!==false) || (strpos($low,'mailbox unavailable')!==false) || (strpos($low,'user unknown')!==false) || (strpos($low,'no such user')!==false) || (strpos($low,'relay denied')!==false);
            return $isAddr ? 'addr' : ($e1->getMessage() ?: 'send failed');
        }
    } catch (Exception $e) {
        return $e->getMessage() ?: 'send failed';
    }
}

/**
 * Send a short notification to site admin(s) when a new order is placed.
 * Uses MAIL_FORCE_TO -> MAIL_TO -> SMTP_USER as recipient order of preference.
 * Returns true on success or an error string on failure.
 */
function send_new_order_email(int $orderId, array $orderSummary = []): bool|string {
    $mail = mailer_instance();
    $forcedTo = trim((string)(getenv('MAIL_FORCE_TO') ?: ''));
    $envTo    = trim((string)(getenv('MAIL_TO') ?: ''));
    $fallback = trim((string)(getenv('SMTP_USER') ?: ''));
    $primary = $forcedTo ?: ($envTo ?: $fallback);

    // If no recipient is configured via env, try to fetch admin emails from DB as a last resort.
    if (!$primary || !filter_var($primary, FILTER_VALIDATE_EMAIL)) {
        try {
            // getDB() helper returns a PDO instance (defined in database/database.php)
            if (function_exists('getDB')) {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT Admin_Email FROM admin WHERE Status = 'Active' AND Admin_Email IS NOT NULL AND Admin_Email != ''");
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $emails = [];
                foreach ($rows as $r) {
                    $e = trim((string)($r['Admin_Email'] ?? ''));
                    if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $emails[] = $e;
                }
                // Prefer gmail addresses if present (many admins use Gmail in this project)
                if (!empty($emails)) {
                    $g = array_values(preg_grep('/@gmail\.com$/i', $emails));
                    $chosen = !empty($g) ? $g : $emails;
                    // join as comma-separated list for PHPMailer addAddress calls below
                    $primary = implode(',', array_unique($chosen, SORT_STRING));
                }
            }
        } catch (Exception $e) {
            // ignore DB errors and fallthrough to no-recipient error
            error_log('[mailer] admin email fallback error: ' . $e->getMessage());
        }
    }

    if (!$primary || !filter_var(explode(',', $primary)[0], FILTER_VALIDATE_EMAIL)) {
        return 'no-recipient-configured';
    }
    try {
        // Support multiple recipients (comma or semicolon separated)
        $parts = preg_split('/[;,\s]+/', (string)$primary, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p && filter_var($p, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($p);
            }
        }
        $mail->isHTML(true);
        $mail->Subject = 'New Order Received #' . $orderId;
        $safe = function($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
        $when = date('M d, Y H:i:s');
        $body = '<h3>New Order Received</h3>';
        $body .= '<p>Order ID: <strong>' . $safe($orderId) . '</strong></p>';
        if (!empty($orderSummary['customer_email'])) $body .= '<p>Customer: ' . $safe($orderSummary['customer_email']) . '</p>';
        if (!empty($orderSummary['amount'])) $body .= '<p>Amount: ₱' . number_format((float)$orderSummary['amount'], 2) . '</p>';
        if (!empty($orderSummary['items']) && is_array($orderSummary['items'])) {
            $body .= '<h4>Items</h4><ul>';
            foreach($orderSummary['items'] as $it) {
                $body .= '<li>' . $safe($it['name'] ?? ($it['Product_Name'] ?? 'Item')) . ' x' . intval($it['qty'] ?? ($it['Quantity'] ?? 1)) . '</li>';
            }
            $body .= '</ul>';
        }
        $body .= '<p style="font-size:12px;color:#666">Received: ' . $safe($when) . '</p>';
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['</li>','<li>','<ul>','</ul>'],' ', $body));
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
