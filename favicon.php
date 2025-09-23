<?php
// Lightweight favicon responder - serves a tiny embedded 16x16 PNG.
// Place this file in your webroot and point <link rel="icon" href="/favicon.php"> to it.
// This avoids a 404 for /favicon.ico when no physical file is present.

// 16x16 red dot PNG (very small) encoded as base64
$png_base64 = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAAH0lEQVR4AWNQ0tL/8z8GJgYGBg+P/4z8GJgYGBgYAEAQYAAe8KQ2cAAAAASUVORK5CYII=';
$png = base64_decode($png_base64);

// If request is for /favicon.ico many browsers still accept PNG with correct header
header('Content-Type: image/png');
header('Content-Length: ' . strlen($png));
// Cache for 1 day
header('Cache-Control: public, max-age=86400');
echo $png;
exit;
