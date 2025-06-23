<?php
session_start();
require_once('classes/database.php');

$response = ['success' => false, 'message' => ''];

try {
    $db = new database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin_id = $_POST['edit_admin_id'] ?? 0;
        $admin_name = trim($_POST['edit_admin_name'] ?? '');
        $admin_email = trim($_POST['edit_admin_email'] ?? '');
        $admin_role = $_POST['edit_admin_role'] ?? '';
        $status = $_POST['edit_status'] ?? 'Active';
        $new_password = $_POST['edit_admin_password'] ?? '';
        $confirm_password = $_POST['edit_confirm_password'] ?? '';

        $response = $db->updateAdmin(
            $admin_id,
            $admin_name,
            $admin_email,
            $admin_role,
            $status,
            $new_password,
            $confirm_password
        );
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);