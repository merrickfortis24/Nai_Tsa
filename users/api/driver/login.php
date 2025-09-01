<?php
// Driver login API
// Accepts: JSON or form POST (and GET for dev/testing)
// Returns: { success, token, driver_id, name, expires }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { echo json_encode(['ok' => true]); exit; }

require_once __DIR__ . '/../../classes/database.php';

try {
	$db = new database();
	$con = $db->opencon();
	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'DB connection error']);
	exit;
}

// Parse input: JSON, form, or GET (dev convenience)
$raw = file_get_contents('php://input') ?: '';
$ctype = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$in = [];
if (stripos($ctype, 'application/json') !== false) {
	$in = json_decode($raw, true) ?: [];
} elseif (!empty($_POST)) {
	$in = $_POST;
} elseif ($raw !== '') {
	$maybe = json_decode($raw, true);
	if (is_array($maybe)) { 
		$in = $maybe; 
	} else {
		// Fallback: parse urlencoded body without correct Content-Type
		parse_str($raw, $in);
	}
}

$phone = trim((string)($in['phone'] ?? ($_GET['phone'] ?? '')));
$password = (string)($in['password'] ?? ($_GET['password'] ?? ''));

if ($phone === '' || $password === '') {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Missing credentials']);
	exit;
}

try {
	$stmt = $con->prepare('SELECT Driver_ID, Name, Password_Hash FROM drivers WHERE Phone = :p LIMIT 1');
	$stmt->execute([':p' => $phone]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'Invalid login']);
		exit;
	}

	$stored = (string)$row['Password_Hash'];
	$valid = false;
	if ($stored !== '' && (strpos($stored, '$2y$') === 0 || strpos($stored, '$argon2') === 0)) {
		$valid = password_verify($password, $stored);
	} else {
		// If plain text was stored, accept once and upgrade to hash
		$valid = ($stored !== '') && hash_equals($stored, $password);
		if ($valid) {
			$newHash = password_hash($password, PASSWORD_DEFAULT);
			$con->prepare('UPDATE drivers SET Password_Hash = :h WHERE Driver_ID = :id')
			   ->execute([':h' => $newHash, ':id' => $row['Driver_ID']]);
		}
	}

	if (!$valid) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'Invalid login']);
		exit;
	}

	$token = bin2hex(random_bytes(24));
	$expires = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
	$upd = $con->prepare('UPDATE drivers SET Api_Token = :t, Token_Expires = :e WHERE Driver_ID = :id');
	$upd->execute([':t' => $token, ':e' => $expires, ':id' => $row['Driver_ID']]);

	echo json_encode([
		'success'   => true,
		'token'     => $token,
		'driver_id' => (int)$row['Driver_ID'],
		'name'      => (string)$row['Name'],
		'expires'   => $expires,
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error']);
}

