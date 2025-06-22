<?php
require_once("database/database.php");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match!";
    } else {
        $db = new database();
        // Check if email already exists
        $con = $db->opencon();
        $stmt = $con->prepare("SELECT * FROM customer WHERE Customer_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Email already registered!";
        } else {
            $success = $db->addCustomer($name, $email, $password);
            if ($success) {
                header("Location: login.php?signup=success");
                exit;
            } else {
                $message = "Sign up failed. Please try again.";
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
  <title>Sign Up | Nai Tsa</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>
<body>
  <!-- Signup Section -->
  <section class="signup-section" id="signup">
    <div class="section-overlay"></div>
    <div class="signup-card">
      <img class="signup-logo" src="assets/naitsalogo.jpg" alt="Nai Tsa Logo">
      <div class="signup-title">Create Your Account</div>
      <div class="signup-desc">Join Nai Tsa for exclusive offers and a more personalized experience!</div>
      <form method="post" action="signup.php">
        <div class="mb-3">
          <input type="text" class="form-control" name="fullname" placeholder="Full Name" required>
        </div>
        <div class="mb-3">
          <input type="email" class="form-control" name="email" placeholder="Email Address" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          <div id="email-warning" class="text-danger mt-1" style="display:none;font-size:0.97rem;"></div>
          <?php if ($message === "Email already registered!" && isset($_POST['email'])): ?>
            <div id="php-email-warning" class="text-danger mt-1" style="font-size:0.97rem;">
              <?= htmlspecialchars($message) ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="mb-3 position-relative">
          <input type="password" class="form-control" name="password" id="signupPassword" placeholder="Password" required minlength="6">
          <span class="toggle-password" onclick="toggleSignupPassword('signupPassword', 'eyeSignup')" style="position:absolute;top:50%;right:1.2rem;transform:translateY(-50%);cursor:pointer;">
            <i id="eyeSignup" class="bi bi-eye" style="font-size:1.3em;color:gray;"></i>
          </span>
        </div>
        <div class="mb-3 position-relative">
          <input type="password" class="form-control" name="confirm_password" id="signupConfirmPassword" placeholder="Confirm Password" required minlength="6">
          <div id="password-warning" class="text-danger mt-1" style="display:none;font-size:0.97rem;"></div>
        </div>
        <button type="submit" class="btn btn-soft-orange" name="signup">Sign Up</button>
      </form>
      <a href="login.php" class="login-link">Already have an account? Sign In</a>
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Rotating background images for signup page using local assets
    const signupImages = [
      "assets/bg10.jpg",
      "assets/b6.jpg",
      "assets/bg11.jpg"
      // Add more local images if you want
    ];
    const section = document.querySelector('.signup-section');
    let idx = 0;
    function changeBg() {
      section.style.backgroundImage = `url('${signupImages[idx]}')`;
      idx = (idx + 1) % signupImages.length;
    }
    changeBg();
    setInterval(changeBg, 3500);

    const passInput = document.querySelector('input[name="password"]');
    const confirmInput = document.querySelector('input[name="confirm_password"]');
    const warning = document.getElementById('password-warning');

    function checkPasswords() {
      if (confirmInput.value.length > 0 && passInput.value !== confirmInput.value) {
        warning.textContent = 'Passwords do not match. Please try again.';
        warning.style.display = 'block';
      } else {
        warning.textContent = '';
        warning.style.display = 'none';
      }
    }

    passInput.addEventListener('input', checkPasswords);
    confirmInput.addEventListener('input', checkPasswords);

    document.querySelector('form').addEventListener('submit', function(e) {
      if (emailWarning.style.display === 'block') {
        e.preventDefault();
        emailInput.focus();
      }
      const pass = document.querySelector('input[name="password"]').value;
      const confirm = document.querySelector('input[name="confirm_password"]').value;
      const warning = document.getElementById('password-warning');
      if (pass !== confirm) {
        e.preventDefault();
        warning.textContent = 'Passwords do not match. Please try again.';
        warning.style.display = 'block';
      } else {
        warning.textContent = '';
        warning.style.display = 'none';
      }
    });

    const emailInput = document.querySelector('input[name="email"]');
    const emailWarning = document.getElementById('email-warning');

    emailInput.addEventListener('input', function() {
      const email = emailInput.value.trim();
      // Hide PHP warning when user types
      const phpWarning = document.getElementById('php-email-warning');
      if (phpWarning) phpWarning.style.display = 'none';

      if (email.length < 5) {
        emailWarning.style.display = 'none';
        return;
      }
      fetch('check_email.php?email=' + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
          if (data.exists) {
            emailWarning.textContent = 'Email already registered!';
            emailWarning.style.display = 'block';
          } else {
            emailWarning.textContent = '';
            emailWarning.style.display = 'none';
          }
        });
    });

    function toggleSignupPassword(inputId, eyeId) {
      const input = document.getElementById(inputId);
      const eye = document.getElementById(eyeId);
      if (input.type === "password") {
        input.type = "text";
        eye.classList.remove('bi-eye');
        eye.classList.add('bi-eye-slash');
        eye.style.color = 'orange';
      } else {
        input.type = "password";
        eye.classList.remove('bi-eye-slash');
        eye.classList.add('bi-eye');
        eye.style.color = 'gray';
      }
    }
  </script>

  <?php if (!empty($message) && $message !== "Email already registered!"): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'error',
  title: 'Sign Up Failed',
  text: <?= json_encode($message) ?>,
  confirmButtonColor: '#FFB27A'
});
</script>
<?php endif; ?>

  <?php if ($message === "Sign up failed. Please try again."): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Sign Up Failed',
  text: 'Please try again.',
  confirmButtonColor: '#FFB27A'
});
</script>
<?php elseif (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'success',
  title: 'Sign Up Successful!',
  text: 'You can now log in.',
  confirmButtonColor: '#FFB27A'
});
</script>
<?php endif; ?>
</body>
</html>