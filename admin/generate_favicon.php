<?php
// One-off helper: generate a tiny 16x16 PNG favicon and write it to the site root as favicon.png and favicon.ico
// Usage: open this script once in the browser: /admin/generate_favicon.php
header('Content-Type: text/plain; charset=UTF-8');

// Destination paths (site root)
$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    echo "ERROR: cannot resolve project root.\n";
    exit(1);
}
$png = $root . DIRECTORY_SEPARATOR . 'favicon.png';
$ico = $root . DIRECTORY_SEPARATOR . 'favicon.ico';

// Check for GD
if (!function_exists('imagecreatetruecolor')) {
    echo "ERROR: PHP GD extension is not available. Please enable GD or upload a favicon manually.\n";
    exit(1);
}

// Create 16x16 with transparent background and a small colored circle
$im = imagecreatetruecolor(16,16);
imagesavealpha($im, true);
$trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefilledrectangle($im, 0, 0, 16, 16, $trans);
$col = imagecolorallocate($im, 67, 97, 238); // a blue-ish color
imagefilledellipse($im, 8, 8, 14, 14, $col);

$wrote = @imagepng($im, $png);
imagedestroy($im);

if ($wrote) {
    // Try to copy PNG to .ico (many browsers accept PNG data at /favicon.ico)
    $copied = @copy($png, $ico);
    echo "OK: favicon generated at:\n - $png\n";
    if ($copied) echo " - $ico\n";
    else echo "Note: could not write $ico (permissions?) — favicon.png exists and should work.\n";
    echo "After verifying, remove this file: admin/generate_favicon.php\n";
    exit(0);
} else {
    echo "ERROR: failed to write $png — check permissions.\n";
    exit(1);
}
