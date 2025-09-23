<?php
// Quick test: attempt to write PHP session data to a local directory under admin/tmp_sessions
// Use this to confirm whether switching save_path to a writable project folder makes sessions persist.
header('Content-Type: application/json; charset=UTF-8');

$local = __DIR__ . '/tmp_sessions';
if (!is_dir($local)) {
    @mkdir($local, 0777, true);
}
// Try to make it writable (best-effort)
@chmod($local, 0777);

// Apply local save path and start session
ini_set('session.save_path', $local);
ini_set('session.use_strict_mode', 1);
session_start();

// Set a test value and commit
$_SESSION['__test_write'] = time();
session_write_close();

$sid = session_id();
$sessFile = rtrim($local, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $sid;

$res = [
    'server_time' => date('c'),
    'local_save_path' => $local,
    'session_id' => $sid,
    'session_file' => $sessFile,
    'file_exists' => false,
    'file_size' => null,
];

if (file_exists($sessFile)) {
    $res['file_exists'] = true;
    $res['file_size'] = filesize($sessFile);
    $res['file_mtime'] = date('c', filemtime($sessFile));
    $res['session_contents_preview'] = substr(file_get_contents($sessFile),0,200);
}

echo json_encode($res, JSON_PRETTY_PRINT);

?>
