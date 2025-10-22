<?php
require_once("database/database.php");
$message = "";
$sweetAlertConfig = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $agreed = isset($_POST['agree_terms']) && $_POST['agree_terms'] === '1';

  if (!$agreed) {
    $message = "You must agree to the Terms of Agreement and Privacy Policy before signing up.";
  } else if ($password !== $confirm) {
    $message = "Passwords do not match!";
  } else {
  $db = new database();
  // Pass whether the user agreed to terms so the DB layer can record the timestamp
  $result = $db->registerCustomer($name, $email, $password, $agreed);
    if ($result === true) {
      // Trigger OTP send via AJAX after render
      $sweetAlertConfig = '<script>window.__POST_SIGNUP_EMAIL__ = ' . json_encode($email) . '; window.__POST_SIGNUP_NAME__ = ' . json_encode($name) . ';</script>';
    } else {
      $message = $result;
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
        <div class="mb-3 form-check">
          <input class="form-check-input" type="checkbox" value="1" id="agreeTerms" name="agree_terms">
          <label class="form-check-label" for="agreeTerms">I have read and agree to the <a href="#" id="openTerms">Terms of Agreement and Privacy Policy</a></label>
        </div>
        <button type="submit" id="signupBtn" class="btn btn-soft-orange" name="signup" disabled>Sign Up</button>
      </form>
      <a href="login.php" class="login-link">Already have an account? Sign In</a>
    </div>
  </section>

  <!-- Verification Modal -->
  <div class="modal fade" id="verifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:14px">
        <div class="modal-header" style="background:#fff7ef">
          <h5 class="modal-title">Verify your email</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-1">We sent a 6-digit code to <b id="vm-email"></b>.</p>
          <div class="mb-3">
            <input id="vm-code" class="form-control" maxlength="6" inputmode="numeric" placeholder="e.g. 123456" />
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <small id="vm-expire" class="text-muted">Code expires in 5:00</small>
            <a href="#" id="vm-resend" class="link-danger">Resend</a>
          </div>
          <div id="vm-error" class="text-danger mt-2" style="display:none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" id="vm-verify-btn">Verify</button>
        </div>
      </div>
    </div>

      <!-- Terms Modal -->
      <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">NaiTsa Food Hub – Terms of Agreement</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="small text-muted">Last Updated: <strong>[Insert Date]</strong></p>
              <p>Welcome to NaiTsa Food Hub, a web and mobile-based ordering and delivery platform managed by NaiTsa Coffee Shop. By creating an account, placing an order, or using any part of our system (as a Customer, Driver, or Administrator), you agree to these Terms of Agreement. Please read carefully before using the service.</p>
              <ol>
                <li><strong>General Terms</strong><br>By accessing NaiTsa Food Hub, you confirm that you are at least 18 years old or have obtained parental/guardian consent. You agree to use the platform lawfully and refrain from fraudulent, abusive, or disruptive activities.</li>
                <li><strong>Account Registration</strong><br>All users (customers and drivers) must register using accurate and complete information, including a valid email address. Users are responsible for keeping their account credentials secure. NaiTsa reserves the right to suspend or terminate any account found to be violating system policies or engaging in suspicious activities.</li>
                <li><strong>Orders and Payments</strong><br>Orders can be placed through the NaiTsa Food Hub web or mobile app. Available payment options include Cash on Delivery (COD) and Gcash. Once an order is confirmed, changes or cancellations may not be accepted if preparation has started. Orders marked Paid are considered final and non-refundable unless there is a valid service error (e.g., wrong or missing item).</li>
                <li><strong>Delivery Policy</strong><br>Drivers are assigned automatically or manually based on availability. Customers must ensure that their delivery address and contact details are correct. Delivery times may vary due to weather, traffic, or distance. Cash on Delivery payments must be settled upon delivery before the order is marked as completed.</li>
                <li><strong>Driver Responsibilities</strong><br>Drivers must verify their identity through the system before activation. They are expected to handle deliveries safely, courteously, and in compliance with local traffic laws. Any form of tampering, misdelivery, or unreported incident may result in account suspension. Drivers must submit proof of delivery (including amount received, if applicable) within the app after each order.</li>
                <li><strong>Refunds and Disputes</strong><br>Refunds are only processed for incorrect or missing items verified by NaiTsa’s support team. Disputes or complaints should be reported within 24 hours after delivery.</li>
                <li><strong>Data Privacy</strong><br>Personal data collected through the system (name, contact info, delivery address, payment details) will be used strictly for order processing and improving services. NaiTsa will not sell or share user data with third parties without consent. For full details, please refer to our Privacy Policy.</li>
                <li><strong>Intellectual Property</strong><br>All logos, trademarks, and content displayed in NaiTsa Food Hub (including its website and mobile app design) are the exclusive property of NaiTsa Coffee Shop. Unauthorized copying or use is strictly prohibited.</li>
                <li><strong>System Usage and Restrictions</strong><br>Users must not attempt to bypass security, manipulate system data, or engage in fraudulent transactions. NaiTsa reserves the right to suspend or restrict access for violations of this agreement. The system may experience maintenance downtime; NaiTsa is not liable for service interruptions beyond its control.</li>
                <li><strong>Limitation of Liability</strong><br>NaiTsa Coffee Shop and its developers shall not be held responsible for losses resulting from delayed deliveries due to unforeseen circumstances, incorrect input of order or address information, or system errors caused by third-party services.</li>
                <li><strong>Updates to Terms</strong><br>NaiTsa reserves the right to modify or update these Terms at any time. Continued use of the system after updates constitutes acceptance of the new terms.</li>
                <li><strong>Contact Information</strong><br>For concerns or support, contact us through: Email: [Insert Email Address], Phone: [Insert Contact Number], Address: NaiTsa Coffee Shop, [Insert Address].</li>
              </ol>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" id="agreeTermsBtn" class="btn btn-primary">I Agree</button>
            </div>
          </div>
        </div>
      </div>
  </div>

  <?= $sweetAlertConfig ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Rotating background images for signup page using local assets
    const signupImages = [
      "assets/bg7.jpg"
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
      } else if (!passwordRegex.test(pass)) {
        e.preventDefault();
        warning.textContent = 'Password must be at least 6 characters, include one uppercase letter, one number, and one special character.';
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

    const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/;
    // OTP modal logic
    const vm = {
      modal: new bootstrap.Modal(document.getElementById('verifyModal')),
      emailEl: document.getElementById('vm-email'),
      codeEl: document.getElementById('vm-code'),
      expireEl: document.getElementById('vm-expire'),
      resendEl: document.getElementById('vm-resend'),
      errorEl: document.getElementById('vm-error'),
      timerId: null,
      endAt: null,
      startCountdown() {
        this.endAt = Date.now() + 5 * 60 * 1000; // 5 minutes
        const tick = () => {
          const left = Math.max(0, this.endAt - Date.now());
          const m = Math.floor(left/60000);
          const s = Math.floor((left%60000)/1000).toString().padStart(2,'0');
          this.expireEl.textContent = `Code expires in ${m}:${s}`;
          if (left <= 0) clearInterval(this.timerId);
        };
        clearInterval(this.timerId);
        this.timerId = setInterval(tick, 1000);
        tick();
      },
      send(email, name) {
        this.emailEl.textContent = email;
        this.codeEl.value = '';
        this.errorEl.style.display = 'none';
        this.startCountdown();
        fetch('send_verification_code.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'email=' + encodeURIComponent(email) + '&name=' + encodeURIComponent(name || '')
        }).then(r => r.json()).then(data => {
          if (!data.ok && !data.already) {
            this.errorEl.textContent = data.error || 'Failed to send code.';
            this.errorEl.style.display = 'block';
          }
        }).catch(() => {
          this.errorEl.textContent = 'Network error.';
          this.errorEl.style.display = 'block';
        });
      },
      verify(email) {
        const code = this.codeEl.value.trim();
        if (!/^\d{6}$/.test(code)) {
          this.errorEl.textContent = 'Enter the 6-digit code.';
          this.errorEl.style.display = 'block';
          return;
        }
        fetch('verify_verification_code.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code)
        }).then(r => r.json()).then(data => {
          if (data.ok) {
            this.modal.hide();
            Swal.fire({ icon:'success', title:'Verified!', text:'Your email is verified. You can log in now.' })
              .then(()=> window.location.href='login.php');
          } else {
            this.errorEl.textContent = 'Invalid or expired code. Try again or resend.';
            this.errorEl.style.display = 'block';
          }
        }).catch(() => {
          this.errorEl.textContent = 'Network error.';
          this.errorEl.style.display = 'block';
        });
      }
    };

  // If server set post-signup email, show modal and send code
  if (window.__POST_SIGNUP_EMAIL__) {
      vm.modal.show();
      vm.send(window.__POST_SIGNUP_EMAIL__, window.__POST_SIGNUP_NAME__);
      document.getElementById('vm-verify-btn').onclick = () => vm.verify(window.__POST_SIGNUP_EMAIL__);
      vm.resendEl.onclick = (e) => { e.preventDefault(); vm.send(window.__POST_SIGNUP_EMAIL__, window.__POST_SIGNUP_NAME__); };
    }
  </script>

    <script>
      // Terms checkbox wiring
      (function(){
        const agree = document.getElementById('agreeTerms');
        const signupBtn = document.getElementById('signupBtn');
        const openTerms = document.getElementById('openTerms');
        const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
        const agreeBtn = document.getElementById('agreeTermsBtn');
        if (agree && signupBtn) {
          agree.addEventListener('change', function(){ signupBtn.disabled = !this.checked; });
        }
        if (openTerms) {
          openTerms.addEventListener('click', function(e){ e.preventDefault(); termsModal.show(); });
        }
        if (agreeBtn) {
          agreeBtn.addEventListener('click', function(){
            if (agree) { agree.checked = true; signupBtn.disabled = false; }
            termsModal.hide();
          });
        }
      })();
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