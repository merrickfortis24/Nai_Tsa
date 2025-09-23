<?php
// Temporary session debug endpoint. Remove this file after debugging.
header('Content-Type: application/json; charset=UTF-8');
session_start();

$out = [
  'server_time' => date('c'),
  'logged_in' => isset($_SESSION['admin_id']),
  'admin_id' => $_SESSION['admin_id'] ?? null,
  'session_id' => session_id(),
  'session' => $_SESSION,
  'cookies' => $_COOKIE,
];

// Also include a small hint about cookie flags (best-effort)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
  $out['hint'] = 'Connection over HTTPS — cookies marked Secure will be sent only over HTTPS.';
}

echo json_encode($out, JSON_PRETTY_PRINT);

// Note: this file exposes session data and should be removed after debugging.
?>
