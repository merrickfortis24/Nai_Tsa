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

</head>
<body>
  <!-- Signup Section -->
  <section class="signup-section" id="signup">
    <div class="section-overlay"></div>
    <div class="signup-card">
      <img class="signup-logo" src="https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/305017926_123739037082830_6536344361033765846_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeF6gojYSTdWNY4orY0VNUkSmvcRd1ll5jia9xF3WWXmODD-saAHrmXgUQmKemzloGzWiKXvFLnLMDOAGKdxzyD6&_nc_ohc=7iBKmMdkBywQ7kNvwFRDQYs&_nc_oc=AdlY_BYvScrT1IflonpxA1Qvq5KxK43IM6csPvtUSzdETsmOm1huAnaj3u8V2bhL94M&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=zr_iO0JmrhCwDGAHOAqdbQ&oh=00_AfP71h0Bxwo_zXF6XA1C60idZzXqlq6yMUdhgIvHHgnRbA&oe=6855DFB1" alt="Nai Tsa Logo">
      <div class="signup-title">Create Your Account</div>
      <div class="signup-desc">Join Nai Tsa for exclusive offers and a more personalized experience!</div>
      <form method="post" action="signup.php">
        <div class="mb-3">
          <input type="text" class="form-control" name="fullname" placeholder="Full Name" required>
        </div>
        <div class="mb-3">
          <input type="email" class="form-control" name="email" placeholder="Email Address" required>
          <div id="email-warning" class="text-danger mt-1" style="display:none;font-size:0.97rem;"></div>
        </div>
        <div class="mb-3">
          <input type="password" class="form-control" name="password" placeholder="Password" required minlength="6">
        </div>
        <div class="mb-3">
          <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required minlength="6">
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
    // Rotating background images for signup page
    const signupImages = [
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/500230252_692319856896013_8852028192218548547_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeF-RPA8YMrq0jSRkO2a0609EMOea9UkhbkQw55r1SSFuTfggM7u_KphE4xaukwWnHiAvNIb54Tdug6LylldXazD&_nc_ohc=8WcA0JIr4KIQ7kNvwFY78B0&_nc_oc=AdlTPV6RJW8qhyOfoECtVos5lPInQmWRuboETiLNzFvf83N1xvPdu7pD2Hn9uOOZzWU&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=Gq6LmE5At4ZrlEznx7hrpA&oh=00_AfPJo1WfXQ6mGewsL6MBctLZTzlYWpdf38MhNxyZzLAhew&oe=6856EF07",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/493052023_669980562463276_344743802743648025_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGwj-3y5c4c6RFeK3M8r6uqGjWNEW45BwQaNY0RbjkHBKNFMGyVnsaTCmP1jYD64leH77wr4A5YINiGB7s9YlbZ&_nc_ohc=OrDGRMfc9fEQ7kNvwFilZ3w&_nc_oc=AdnXPNhq_uUsbGVZ7AqplJovZMyR8sl23I19fYpi4ku-ZydHFjsGxgkpAVjUahaYbNI&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=sSGMrA8A6u3A7aKsg0EEcg&oh=00_AfO_u41aZWGJd10VxQS-Vdm_iPd5H9alPfqk4ifE7bxztQ&oe=685733E8"
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
  </script>

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

<?php if (!empty($message)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
</body>
</html>