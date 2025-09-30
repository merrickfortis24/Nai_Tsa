<?php
session_start();
// If there's a rememberme cookie, remove DB row
if (isset($_COOKIE['rememberme'])) {
	$cookie = $_COOKIE['rememberme'];
	$parts = explode(':', $cookie, 2) + [null, null];
	$selector = $parts[0];
	if ($selector) {
		require_once __DIR__ . '/../database/database.php';
		$db = new database();
		$db->deleteRememberToken($selector);
	}
	setcookie('rememberme', '', time() - 3600, '/');
}

session_unset();
session_destroy();
header("Location: ../login.php");
exit;
?>