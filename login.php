<?php
session_start();
$login_success = false;
$email_not_found = false;
$wrong_password = false;
$unverified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $account_type = $_POST['account_type'] ?? 'customer';
    $remember = isset($_POST['remember']);

    require_once("database/database.php");
    $db = new database();

    $user = $db->getUserByEmail($email, $account_type);

    if (!$user) {
        $email_not_found = true;
    } else {
        $hashed = $account_type === 'admin' ? $user['Admin_Password'] : $user['Customer_Password'];
        if (!password_verify($password, $hashed)) {
            $wrong_password = true;
    } else {
            $login_success = true;
      if ($account_type === 'admin') {
        // Prevent session fixation by regenerating the session id on login
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['Admin_ID'];
        $_SESSION['admin_name'] = $user['Admin_Name'];
        $_SESSION['admin_role'] = $user['Admin_Role'];
        // If user asked to be remembered, create a secure persistent token and cookie
        if ($remember) {
          // create selector and token
          $selector = bin2hex(random_bytes(9));
          $token = bin2hex(random_bytes(33));
          $token_hash = hash('sha256', $token);
          $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);
          $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
          $ip = $_SERVER['REMOTE_ADDR'] ?? null;
          // store in DB
          $db->createRememberToken($selector, $token_hash, (int)$user['Admin_ID'], 'admin', $expires_at, $userAgent, $ip);
          // set cookie
          $cookieValue = $selector . ':' . $token;
          setcookie('rememberme', $cookieValue, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            // 'domain' => '.naitsa.online', // optional for subdomains
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
          ]);
        }
            } else {
        // Gate unverified customers
        if ((int)($user['is_verified'] ?? 0) !== 1) {
          $unverified = true;
          $login_success = false;
        } else {
                $_SESSION['customer_name'] = $user['Customer_Name'];
                $_SESSION['customer_email'] = $user['Customer_Email'];
                $_SESSION['customer_id'] = $user['Customer_ID'];
        // Prevent session fixation by regenerating session id on successful login
        session_regenerate_id(true);
        if ($remember) {
          // Only remember the email in UI and create a persistent rememberme token
          setcookie('remember_email', $email, time() + (86400 * 30), "/");
          // create selector and token
          $selector = bin2hex(random_bytes(9));
          $token = bin2hex(random_bytes(33));
          $token_hash = hash('sha256', $token);
          $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);
          $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
          $ip = $_SERVER['REMOTE_ADDR'] ?? null;
          // store in DB
          $db->createRememberToken($selector, $token_hash, (int)$user['Customer_ID'], 'customer', $expires_at, $userAgent, $ip);
          // set cookie
          $cookieValue = $selector . ':' . $token;
          setcookie('rememberme', $cookieValue, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            // 'domain' => '.naitsa.online',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
          ]);
        } else {
          setcookie('remember_email', '', time() - 3600, "/");
        }
        }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log In | Nai Tsa</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/style.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
  <!-- Login Section -->
  <section class="login-section" id="login">
    <div class="section-overlay"></div>
    <div class="login-card">
      <img class="login-logo" src="assets/naitsalogo.jpg" alt="Nai Tsa Logo">
      <div class="login-title">Welcome Back</div>
      <div class="login-desc">Log in to your Nai Tsa account to continue.</div>
      <form method="post" action="login.php">
        <!-- Force customer login (admin has a separate login page) -->
        <input type="hidden" name="account_type" value="customer">
        <div class="mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email Address" required
            value="<?php if(isset($_COOKIE['remember_email'])) echo htmlspecialchars($_COOKIE['remember_email']); ?>">
        </div>
        <div class="mb-3 position-relative">
          <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Password" required minlength="6">
          <span class="toggle-password" onclick="togglePassword()" style="position:absolute;top:50%;right:1rem;transform:translateY(-50%);cursor:pointer;">
            <i id="eyeIcon" class="bi bi-eye" style="font-size:1.3em;color:gray;"></i>
          </span>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="rememberMe" name="remember"
            <?php if(isset($_COOKIE['remember_email'])) echo 'checked'; ?>>
          <label class="form-check-label" for="rememberMe">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-soft-orange">Log In</button>
      </form>
      <a href="signup.php" class="signup-link">Don't have an account? Sign Up</a>
      <a href="users/forgot_password.php" class="forgot-link">Forgot Password?</a>
      <div class="mt-2">
        <a href="admin/login.php" class="text-decoration-none" style="color:#6c757d; font-size:0.95rem;">Admin Login</a>
      </div>
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Rotating background images for login page using local assets
    const loginImages = [
      "assets/bg10.jpg",
      "assets/b6.jpg",
      "assets/bg11.jpg"
      // Add more local images if you want
    ];
    const section = document.querySelector('.login-section');
    let idx = 0;
    function changeBg() {
      section.style.backgroundImage = `url('${loginImages[idx]}')`;
      idx = (idx + 1) % loginImages.length;
    }
    changeBg();
    setInterval(changeBg, 3500);

    // SweetAlert for successful login
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $login_success && $account_type === 'admin'): ?>
      Swal.fire({
        icon: 'success',
        title: 'Login Successful!',
        text: 'Redirecting to admin dashboard...',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        window.location.href = 'admin/index.php';
      });
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $login_success): ?>
      Swal.fire({
        icon: 'success',
        title: 'Login Successful!',
        text: 'Redirecting to your dashboard...',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        window.location.href = 'users/index.php';
      });
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $email_not_found): ?>
      Swal.fire({
        icon: 'warning',
        title: 'Email Not Registered',
        text: 'The email you entered is not registered.',
        confirmButtonColor: '#FFB27A'
      });
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $wrong_password): ?>
      Swal.fire({
        icon: 'error',
        title: 'Incorrect Password',
        text: 'The password you entered is incorrect.',
        confirmButtonColor: '#FFB27A'
      });
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $unverified): ?>
      Swal.fire({
        icon: 'info',
        title: 'Verify your email',
        html: `Your account isn't verified yet.<br><small>We can resend the verification link to <b><?php echo htmlspecialchars($email ?? ''); ?></b></small>`,
        showCancelButton: true,
        confirmButtonText: 'Resend Link',
        cancelButtonText: 'Close'
      }).then((res) => {
        if (res.isConfirmed) {
          fetch('resend_verification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent('<?php echo htmlspecialchars($email ?? '', ENT_QUOTES); ?>')
          }).then(r => r.json()).then(data => {
            if (data.ok) {
              Swal.fire({ icon: 'success', title: 'Sent', text: 'Check your inbox for the new link.' });
            } else {
              Swal.fire({ icon: 'error', title: 'Failed', text: data.error || 'Could not send email.' });
            }
          }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Failed', text: 'Network error.' });
          });
        }
      });
    <?php endif; ?>

    // Toggle password visibility
    function togglePassword() {
      const passwordInput = document.getElementById('passwordInput');
      const eyeIcon = document.getElementById('eyeIcon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
        eyeIcon.style.color = 'orange';
      } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
        eyeIcon.style.color = 'gray';
      }
    }
  </script>
</body>
</html>