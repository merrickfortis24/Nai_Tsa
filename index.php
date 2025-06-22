<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nai Tsa - Coffee & Milk Tea</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts: Poppins for modern look -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <!-- Your custom CSS -->
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg shadow-sm" style="background: rgba(255,255,255,0.68); box-shadow: 0 4px 20px rgba(255, 178, 122, 0.15); border-radius: 0 0 24px 24px; padding: 0.9rem 0;">
    <div class="container px-2">
      <a class="navbar-brand me-4" href="#" aria-label="Nai Tsa Home">
        <img src="assets/naitsalogo.jpg" alt="Nai Tsa Logo">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link" href="#home">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#menu">Menu</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#contact">Contact</a>
          </li>
        </ul>
        <div class="d-flex align-items-center ms-lg-auto flex-column flex-lg-row gap-2 gap-lg-0">
          <span class="navbar-right-text me-lg-3">OPEN 10:00AM TO 12AM</span>
          <a href="login.php" class="btn btn-outline-soft-orange me-2">Sign In</a>
          <a href="signup.php" class="btn btn-soft-orange">Join Now</a>
        </div>
      </div>
    </div>
  </nav>

   <!-- Home Section -->
  <section class="section" id="home">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h1 class="section-title" style="font-size:4.0rem;">Take a Sip. Take a Break.</h1>
      <p class="section-desc">Welcome to NaiTsa your cozy escape for calm and comfort. Sip fresh coffee or vibrant milk tea in a space made to help you breathe and feel better.
Open daily from 10AM to midnight..</p>
      <!-- <a href="#menu" class="btn btn-section">ORDER NOW</a> -->
    </div>
  </section>

  <!-- About Section -->
  <section class="section" id="about">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h2 class="section-title">About Nai Tsa</h2>
      <p class="section-desc">
        At Nai Tsa, we blend premium milk tea and coffee with creativity in every cup. From classics to signature blends, enjoy great flavors in a cozy space with friendly baristas. Come for the taste, stay for the vibe.
      <!-- Location Card with Map and Address -->
      <div class="card shadow-sm mb-3" style="max-width:410px; background:rgba(255,255,255,0.98); border-radius:16px;">
        <div style="border-radius:16px 16px 0 0; overflow:hidden;">
          <!-- OpenStreetMap Embed for Lipa City, Philippines, Banay-Banay -->
          <iframe
            width="100%"
            height="180"
            frameborder="0"
            style="border:0; display:block;"
            src="https://www.openstreetmap.org/export/embed.html?bbox=121.118%2C13.940%2C121.175%2C13.990&layer=mapnik&marker=13.965%2C121.146"
            allowfullscreen
            aria-hidden="false"
            tabindex="0"></iframe>
        </div>
        <div class="p-3">
          <div class="mb-2" style="font-size:1.08rem; color:#61391D;">
            <span style="display:inline-flex;align-items:center;">
              <span style="font-size:1.4em; margin-right:0.4em;">🏠</span>
              Zone 6, Brgy. Pinagtong-ulan, Lipa City, Philippines, 4217
            </span>
            <div style="font-size:0.95em;color:#888888;margin-left:2em;">Address</div>
          </div>
          <div style="font-size:1.08rem; color:#61391D;">
            <span style="display:inline-flex;align-items:center;">
              <span style="font-size:1.3em; margin-right:0.4em;">📞</span>
              0967 255 6259
            </span>
            <div style="font-size:0.95em;color:#888888;margin-left:2em;">Mobile</div>
          </div>
        </div>
      </div>
      <!-- End Location Card -->
    </div>
  </section>

  <!-- Menu Section -->
  <section class="section" id="menu">
    <div class="section-overlay"></div>
    <div class="section-content" style="max-width: 950px;">
      <h2 class="section-title text-center w-100">Menu</h2>
      <div class="menu-cards w-100 justify-content-center">
        <div class="menu-card">
          <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=150&q=80" alt="Classic Milk Tea">
          <div class="menu-card-title">Classic Milk Tea</div>
          <div class="menu-card-desc">Traditional black tea with creamy milk, slightly sweet, perfectly chilled.</div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="Classic Milk Tea">Add to Cart</button>
        </div>
        <div class="menu-card">
          <img src="https://images.unsplash.com/photo-1519864600265-abb23847ef2c?auto=format&fit=crop&w=150&q=80" alt="Brown Sugar Boba">
          <div class="menu-card-title">Brown Sugar Boba</div>
          <div class="menu-card-desc">Rich brown sugar syrup, chewy pearls, and velvety milk tea.</div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="Brown Sugar Boba">Add to Cart</button>
        </div>
        <div class="menu-card">
          <img src="https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=150&q=80" alt="Coffee Latte">
          <div class="menu-card-title">Coffee Latte</div>
          <div class="menu-card-desc">Espresso meets creamy steamed milk, topped with light foam.</div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="Coffee Latte">Add to Cart</button>
        </div>
        <div class="menu-card">
          <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=150&q=80" alt="Strawberry Matcha">
          <div class="menu-card-title">Strawberry Matcha</div>
          <div class="menu-card-desc">Earthy matcha layered with fresh strawberry milk for a vibrant treat.</div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="Strawberry Matcha">Add to Cart</button>
        </div>
        <div class="menu-card">
          <img src="https://images.unsplash.com/photo-1528825871115-3581a5387919?auto=format&fit=crop&w=150&q=80" alt="Caramel Macchiato">
          <div class="menu-card-title">Caramel Macchiato</div>
          <div class="menu-card-desc">Sweet caramel, rich espresso, and frothy milk in an irresistible blend.</div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="Caramel Macchiato">Add to Cart</button>
        </div>
      </div>
      <div class="text-center w-100" style="margin-top: 0.2rem;">
        <a href="#" class="btn btn-outline-soft-orange" style="font-size:1.09rem; padding:0.7rem 2.2rem; font-weight:600;">
          More Products
        </a>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="section" id="contact">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h2 class="section-title">Contact Us</h2>
      <p class="section-desc">Have a question or want to say hi? Fill out the form below or visit us in-store. We love to connect with our Nai Tsa community!</p>
      <?php if (isset($_GET['contact']) && $_GET['contact'] === 'success'): ?>
  <div class="alert alert-success">Thank you! Your message has been sent.</div>
<?php elseif (isset($_GET['contact']) && $_GET['contact'] === 'error'): ?>
  <div class="alert alert-danger">Sorry, there was a problem sending your message.</div>
<?php endif; ?>
      <form method="POST" action="https://formspree.io/f/xnnvvzvw">
  <div class="row">
    <div class="col-md-6 mb-3">
      <input type="text" class="form-control" name="name" placeholder="Your Name" required>
    </div>
    <div class="col-md-6 mb-3">
      <input type="email" class="form-control" name="email" placeholder="Your Email" required>
    </div>
  </div>
  <textarea class="form-control mb-3" name="message" rows="3" placeholder="Your Message" required></textarea>
  <button type="submit" class="btn btn-soft-orange px-4">Send Message</button>
</form>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    &copy; 2025 Nai Tsa &mdash; Coffee & Milk Tea. Designed with <span style="color: var(--soft-orange);">&#10084;</span>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Smooth scroll and highlight active nav
    document.querySelectorAll('.nav-link').forEach(function(link) {
      link.addEventListener('click', function(e) {
        var targetId = this.getAttribute('href').replace('#','');
        var target = document.getElementById(targetId);
        if (target) {
          e.preventDefault();
          window.scrollTo({
            top: target.offsetTop - document.querySelector('.navbar').offsetHeight,
            behavior: 'smooth'
          });
        }
      });
    });

    // Highlight nav on scroll
    window.addEventListener('scroll', function() {
      var scrollPos = window.scrollY + document.querySelector('.navbar').offsetHeight + 10;
      document.querySelectorAll('.section').forEach(function(section) {
        var id = section.id;
        if (
          scrollPos >= section.offsetTop &&
          scrollPos < section.offsetTop + section.offsetHeight
        ) {
          document.querySelectorAll('.nav-link').forEach(function(link) {
            if (link.getAttribute('href') === '#' + id) {
              link.classList.add('active');
            } else {
              link.classList.remove('active');
            }
          });
        }
      });
    });

    // Rotating background images for all main sections using local assets
    function setupRotatingBg(sectionId, images) {
      const section = document.getElementById(sectionId);
      let idx = 0;
      function changeBg() {
        section.style.backgroundImage = `url('${images[idx]}')`;
        idx = (idx + 1) % images.length;
      }
      changeBg();
      setInterval(changeBg, 3000);
    }

    // Use your downloaded images from assets folder
    const homeImages = [
      "assets/bg7.jpg",
      "assets/b6.jpg",
      "assets/bg1.jpg"
    ];
    const aboutImages = [
      "assets/bg11.jpg",
      "assets/bg7.jpg",
      "assets/bg12.jpg"
    ];
    const menuImages = [
      "assets/bg3.jpg",
      "assets/bg9.jpg",
      "assets/bg5.jpg"
    ];
    const contactImages = [
      "assets/bg1.jpg",
      "assets/bg8.jpg",
      "assets/bg10.jpg"
    ];

    // Setup rotating backgrounds
    setupRotatingBg("home", homeImages);
    setupRotatingBg("about", aboutImages);
    setupRotatingBg("menu", menuImages);
    setupRotatingBg("contact", contactImages);

    // Add to Cart functionality (simple alert for demo)
    document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Not Logged In',
          text: 'You must log in first to add items to your cart.',
          showCancelButton: true,
          confirmButtonText: 'Log In',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'login.php';
          }
        });
      });
    });

    // --- Real-time My Orders Modal Refresh ---
    // Place this after your other <script> code, before </body>

    function refreshMyOrders() {
      fetch('fetch_orders.php')
        .then(res => res.json())
        .then(orders_by_status => {
          // To Ship
          let html = '';
          if (orders_by_status['To Ship'].length) {
            orders_by_status['To Ship'].forEach(order => {
              html += `
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #${order.Order_ID}</strong> | ${order.Order_Date}</div>
                    <div>Status: <span class="badge bg-warning text-dark">${order.order_status}</span></div>
                    <div>Total: ₱${parseFloat(order.Order_Amount).toFixed(2)}</div>
                  </div>
                </div>
              `;
            });
          } else {
            html = '<div class="text-muted">No orders to ship.</div>';
          }
          document.querySelector('#to-ship').innerHTML = html;

          // To Receive
          html = '';
          if (orders_by_status['To Receive'].length) {
            orders_by_status['To Receive'].forEach(order => {
              html += `
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #${order.Order_ID}</strong> | ${order.Order_Date}</div>
                    <div>Status: <span class="badge bg-info text-dark">${order.order_status}</span></div>
                    <div>Total: ₱${parseFloat(order.Order_Amount).toFixed(2)}</div>
                  </div>
                </div>
              `;
            });
          } else {
            html = '<div class="text-muted">No orders to receive.</div>';
          }
          document.querySelector('#to-receive').innerHTML = html;

          // Delivered
          html = '';
          if (orders_by_status['Delivered'].length) {
            orders_by_status['Delivered'].forEach(order => {
              html += `
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #${order.Order_ID}</strong> | ${order.Order_Date}</div>
                    <div>Status: <span class="badge bg-success">${order.order_status}</span></div>
                    <div>Total: ₱${parseFloat(order.Order_Amount).toFixed(2)}</div>
                  </div>
                </div>
              `;
            });
          } else {
            html = '<div class="text-muted">No delivered orders.</div>';
          }
          document.querySelector('#delivered').innerHTML = html;
        });
    }

    // When the My Orders modal is shown, start refreshing every 5 seconds
    document.getElementById('myOrdersModal').addEventListener('show.bs.modal', function () {
      refreshMyOrders();
      window.myOrdersInterval = setInterval(refreshMyOrders, 5000);
    });
    // Stop refreshing when modal is hidden
    document.getElementById('myOrdersModal').addEventListener('hidden.bs.modal', function () {
      clearInterval(window.myOrdersInterval);
    });
  </script>
</body>
</html>