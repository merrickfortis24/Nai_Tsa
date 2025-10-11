<?php
// Lightweight endpoint to detect if newer orders exist than the page currently has.
// Returns JSON: { success: true, last_id: <int>, has_new: <bool> }

declare(strict_types=1);
header('Content-Type: application/json');
session_start();

try {
	// Require admin session
	if (!isset($_SESSION['admin_id'])) {
		http_response_code(403);
		echo json_encode(['success' => false, 'message' => 'Unauthorized']);
		exit;
	}

	require_once __DIR__ . '/../classes/database.php';
	$db = new database();
	$con = $db->opencon();

	$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

	// Get the most recent Order_ID
	$stmt = $con->query("SELECT MAX(Order_ID) AS max_id FROM orders");
	$maxId = (int)($stmt->fetchColumn() ?: 0);

	$hasNew = $maxId > $lastId;
	echo json_encode([
		'success' => true,
		'last_id' => $maxId,
		'has_new' => $hasNew,
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error']);
}

