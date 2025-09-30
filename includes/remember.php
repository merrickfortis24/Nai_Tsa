<?php
// include this file at the top of protected pages (after including database class and session_start())
// It will attempt to restore a session from a valid rememberme cookie.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['customer_id']) || isset($_SESSION['admin_id'])) {
    // already logged in
    return;
}

if (!isset($_COOKIE['rememberme'])) {
    return;
}

$cookie = $_COOKIE['rememberme'];
$parts = explode(':', $cookie, 2) + [null, null];
$selector = $parts[0];
$token = $parts[1];
if (!$selector || !$token) {
    setcookie('rememberme', '', time() - 3600, '/');
    return;
}

require_once __DIR__ . '/../database/database.php';
$db = new database();
$row = $db->getRememberTokenBySelector($selector);
if (!$row) {
    setcookie('rememberme', '', time() - 3600, '/');
    return;
}

// check expiry
if (strtotime($row['expires_at']) < time()) {
    $db->deleteRememberToken($selector);
    setcookie('rememberme', '', time() - 3600, '/');
    return;
}

// verify token
$hash = hash('sha256', $token);
if (!hash_equals($row['token_hash'], $hash)) {
    // possible theft or tampering
    $db->deleteRememberToken($selector);
    setcookie('rememberme', '', time() - 3600, '/');
    return;
}

// Valid token; recreate session for the user
session_regenerate_id(true);
$user = $db->getUserById((int)$row['user_id'], $row['account_type']);
if (!$user) {
    $db->deleteRememberToken($selector);
    setcookie('rememberme', '', time() - 3600, '/');
    return;
}

if ($row['account_type'] === 'admin') {
    $_SESSION['admin_id'] = $user['Admin_ID'];
    $_SESSION['admin_name'] = $user['Admin_Name'];
    $_SESSION['admin_role'] = $user['Admin_Role'];
} else {
    $_SESSION['customer_id'] = $user['Customer_ID'];
    $_SESSION['customer_name'] = $user['Customer_Name'];
    $_SESSION['customer_email'] = $user['Customer_Email'];
}

// Rotate token: replace DB row with new selector/token and set new cookie
$newSelector = bin2hex(random_bytes(9));
$newToken = bin2hex(random_bytes(33));
$newHash = hash('sha256', $newToken);
$newExpires = date('Y-m-d H:i:s', time() + 86400 * 30);
$db->updateRememberToken($selector, $newSelector, $newHash, $newExpires);
setcookie('rememberme', $newSelector . ':' . $newToken, [
    'expires' => time() + 86400 * 30,
    'path' => '/',
    // 'domain' => '.naitsa.online',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// update last_used (optional)
// handled by updateRememberToken which sets last_used = NOW()

?>
