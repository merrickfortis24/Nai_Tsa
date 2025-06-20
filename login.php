<?php
session_start();
$login_success = false;
$email_not_found = false;
$wrong_password = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    require_once("database/database.php");
    $db = new database();
    $con = $db->opencon();
    $stmt = $con->prepare("SELECT * FROM customer WHERE Customer_Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $email_not_found = true;
    } elseif (!password_verify($password, $user['Customer_Password'])) {
        $wrong_password = true;
    } else {
        $login_success = true;
        $_SESSION['customer_name'] = $user['Customer_Name'];
        $_SESSION['customer_email'] = $user['Customer_Email'];
        $_SESSION['customer_id'] = $user['Customer_ID'];
        if ($remember) {
            setcookie('remember_email', $email, time() + (86400 * 30), "/");
            setcookie('remember_pass', $password, time() + (86400 * 30), "/");
        } else {
            setcookie('remember_email', '', time() - 3600, "/");
            setcookie('remember_pass', '', time() - 3600, "/");
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
        <div class="mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email Address" required
            value="<?php if(isset($_COOKIE['remember_email'])) echo htmlspecialchars($_COOKIE['remember_email']); ?>">
        </div>
        <div class="mb-3 position-relative">
          <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Password" required minlength="6"
            value="<?php if(isset($_COOKIE['remember_pass'])) echo htmlspecialchars($_COOKIE['remember_pass']); ?>">
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
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Rotating background images for login page
    const loginImages = [
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/500230252_692319856896013_8852028192218548547_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeF-RPA8YMrq0jSRkO2a0609EMOea9UkhbkQw55r1SSFuTfggM7u_KphE4xaukwWnHiAvNIb54Tdug6LylldXazD&_nc_ohc=8WcA0JIr4KIQ7kNvwFY78B0&_nc_oc=AdlTPV6RJW8qhyOfoECtVos5lPInQmWRuboETiLNzFvf83N1xvPdu7pD2Hn9uOOZzWU&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=Gq6LmE5At4ZrlEznx7hrpA&oh=00_AfPJo1WfXQ6mGewsL6MBctLZTzlYWpdf38MhNxyZzLAhew&oe=6856EF07",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/493052023_669980562463276_344743802743648025_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGwj-3y5c4c6RFeK3M8r6uqGjWNEW45BwQaNY0RbjkHBKNFMGyVnsaTCmP1jYD64leH77wr4A5YINiGB7s9YlbZ&_nc_ohc=OrDGRMfc9fEQ7kNvwFilZ3w&_nc_oc=AdnXPNhq_uUsbGVZ7AqplJovZMyR8sl23I19fYpi4ku-ZydHFjsGxgkpAVjUahaYbNI&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=sSGMrA8A6u3A7aKsg0EEcg&oh=00_AfO_u41aZWGJd10VxQS-Vdm_iPd5H9alPfqk4ifE7bxztQ&oe=685733E8"
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
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $login_success): ?>
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