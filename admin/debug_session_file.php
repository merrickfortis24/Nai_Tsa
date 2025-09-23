<?php
// Temporary: check PHP session.save_path and whether the current session file exists on disk.
// Remove this file after debugging.
header('Content-Type: application/json; charset=UTF-8');
session_start();

$sid = session_id();
$savePath = ini_get('session.save_path') ?: sys_get_temp_dir();

$response = [
  'server_time' => date('c'),
  'session_id' => $sid,
  'session_save_path' => $savePath,
  'session_file' => null,
  'file_exists' => false,
  'file_size' => null,
  'file_mtime' => null,
];

// PHP session files are commonly named 'sess_<id>' when using files
$sessFile = rtrim($savePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $sid;
$response['session_file'] = $sessFile;

if (file_exists($sessFile)) {
  $response['file_exists'] = true;
  $response['file_size'] = filesize($sessFile);
  $response['file_mtime'] = date('c', filemtime($sessFile));
}

// Also include current $_SESSION (may be empty)
$response['session'] = $_SESSION;

echo json_encode($response, JSON_PRETTY_PRINT);

?>
