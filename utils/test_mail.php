<?php
// Quick manual test for the mailer without full app flow.
// Usage (Windows PowerShell):
//   php -d auto_prepend_file=Nai_Tsa/.mail.env.php Nai_Tsa/utils/test_mail.php your@email.com

require __DIR__ . '/mailer.php';

$to = $argv[1] ?? getenv('MAIL_FORCE_TO') ?? null;
if (!$to) {
    fwrite(STDERR, "Provide recipient email as first argument or set MAIL_FORCE_TO in .mail.env.php\n");
    exit(2);
}
$token = bin2hex(random_bytes(16));

$result = send_verification_email($to, $token);
if ($result === true) {
    echo "OK: sent to $to\n";
    exit(0);
}

fwrite(STDERR, "ERROR: $result\n");
exit(1);
