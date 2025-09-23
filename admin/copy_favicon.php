<?php
// One-off helper to copy driver_app/web/favicon.png to site root as favicon.ico
// Usage: visit /admin/copy_favicon.php once in your browser. Delete after use.
header('Content-Type: text/plain; charset=UTF-8');

$source = __DIR__ . '/../../driver_app/web/favicon.png';
$dest = __DIR__ . '/../../favicon.ico';

echo "Source: $source\n";
echo "Destination: $dest\n";

if (!file_exists($source)) {
    echo "ERROR: source file not found.\n";
    exit(1);
}

if (@copy($source, $dest)) {
    echo "OK: copied to site root as favicon.ico\n";
} else {
    echo "ERROR: copy failed. Check file permissions.\n";
}

echo "\nAfter verifying, remove this file for security: admin/copy_favicon.php\n";
