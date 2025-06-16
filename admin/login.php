<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Include database class
require_once('classes/database.php');

// Initialize variables
$email = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Both email and password are required!";
    } else {
        try {
            // Create database connection
            $db = new database();
            $conn = $db->opencon();
            
            // Prepare SQL statement
            $stmt = $conn->prepare("SELECT Admin_ID, Admin_Name, Admin_Password, Admin_Role, Status FROM Admin WHERE Admin_Email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            // Check if user exists
            if ($stmt->rowCount() === 1) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $admin['Admin_Password'])) {
                    // Check account status
                    if ($admin['Status'] === 'Active') {
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['Admin_ID'];
                        $_SESSION['admin_name'] = $admin['Admin_Name'];
                        $_SESSION['admin_role'] = $admin['Admin_Role'];
                        
                        // Redirect to dashboard
                        header('Location: index.php');
                        exit();
                    } else {
                        $error = "Your account is inactive. Please contact the system administrator.";
                    }
                } else {
                    $error = "Invalid email or password!";
                }
            } else {
                $error = "Invalid email or password!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Admin Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #6c757d;
            margin: 20px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        
        .divider::before {
            margin-right: 10px;
        }
        
        .divider::after {
            margin-left: 10px;
        }
        
        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2>Admin Portal</h2>
            <p class="mb-0">Sign in to your account</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
                    <div class="form-text">Enter your registered email address</div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <div class="form-text">
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-login btn-primary text-white">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>
                
                <div class="divider">or</div>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account? <a href="#" class="forgot-link">Contact Administrator</a></p>
                </div>
            </form>
        </div>
        
        <div class="p-3 border-top">
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help Center</a>
            </div>
            <div class="text-center mt-2 text-muted">
                &copy; 2023 Admin Management System. All rights reserved.
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Add animation to the login button on hover
        const loginBtn = document.querySelector('.btn-login');
        loginBtn.addEventListener('mouseenter', () => {
            loginBtn.style.transform = 'translateY(-2px)';
            loginBtn.style.boxShadow = '0 5px 15px rgba(67, 97, 238, 0.3)';
        });
        
        loginBtn.addEventListener('mouseleave', () => {
            loginBtn.style.transform = 'translateY(0)';
            loginBtn.style.boxShadow = 'none';
        });
    </script>
</body>
</html>