<?php
session_start();
require_once('classes/database.php');

$response = ['success' => false, 'message' => ''];

try {
    $db = new database();
    $conn = $db->opencon();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin_id = $_POST['edit_admin_id'] ?? 0;
        $admin_name = trim($_POST['edit_admin_name'] ?? '');
        $admin_email = trim($_POST['edit_admin_email'] ?? '');
        $admin_role = $_POST['edit_admin_role'] ?? '';
        $status = $_POST['edit_status'] ?? 'Active';
        $new_password = $_POST['edit_admin_password'] ?? '';
        $confirm_password = $_POST['edit_confirm_password'] ?? '';

        // Validate inputs
        if (empty($admin_id)) {
            throw new Exception("Admin ID is missing");
        }

        if (empty($admin_name) || empty($admin_email) || empty($admin_role)) {
            throw new Exception("All fields are required!");
        }

        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format!");
        }

        if (!empty($new_password) && $new_password !== $confirm_password) {
            throw new Exception("Passwords do not match!");
        }

        // Prepare base SQL
        $sql = "UPDATE Admin SET 
                Admin_Name = :name, 
                Admin_Email = :email, 
                Admin_Role = :role, 
                Status = :status,
                Updated_At = NOW()
                WHERE Admin_ID = :id";

        $params = [
            ':name' => $admin_name,
            ':email' => $admin_email,
            ':role' => $admin_role,
            ':status' => $status,
            ':id' => $admin_id
        ];

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE Admin SET 
                    Admin_Name = :name, 
                    Admin_Email = :email, 
                    Admin_Role = :role, 
                    Status = :status, 
                    Admin_Password = :password,
                    Updated_At = NOW()
                    WHERE Admin_ID = :id";
            $params[':password'] = $hashed_password;
        } 

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Admin updated successfully!';
        } else {
            $response['message'] = 'No changes made or admin not found.';
        }
    } 
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);