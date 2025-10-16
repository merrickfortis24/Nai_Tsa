<?php
$categories = [];
$products = [];
$avg_ratings = [];
$bestsellers = [];
try {
  require_once __DIR__ . '/users/classes/database.php';
  $db = new database();
  if (method_exists($db, 'fetchAllCategories')) {
    $categories = $db->fetchAllCategories();
  }
  if (method_exists($db, 'fetchAllProducts')) {
    $products = $db->fetchAllProducts();
  }
  if (method_exists($db, 'getAverageRatings')) {
    $avg_ratings = $db->getAverageRatings();
  }
  if (method_exists($db, 'getBestsellerProducts')) {
    $bestsellers = $db->getBestsellerProducts(8);
  }
} catch (Throwable $e) {
  // leave arrays empty; UI will degrade gracefully
  //testing only: echo "Error loading data: " . $e->getMessage();HELLOOOO
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nai Tsa - Coffee & Milk Tea</title>
  <!-- Favicon / App Icons -->
  <!-- Place your PNG logo (square) in assets as favicon.png (recommend 512x512) -->
  <!-- Also optionally create resized versions (32x32, 16x16). Browsers will downscale if only one provided. -->
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon.png?v=1">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png?v=1">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png?v=1">
  <!-- Fallback to existing JPEG logo if PNGs not yet uploaded -->
  <link rel="alternate icon" type="image/jpeg" href="assets/naitsalogo.jpg">
  <!-- Manifest (optional, create manifest.webmanifest at project root if using PWA features) -->
  <!-- <link rel="manifest" href="/manifest.webmanifest"> -->
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons for social/phone logos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts: Poppins for modern look -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <!-- Your custom CSS -->
  <link rel="stylesheet" href="assets/style.css">
  <style>
    /* === Menu Card Layout Enhancements === */
    #menuCards.menu-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:32px; align-items:stretch; }
    .menu-card { display:flex; flex-direction:column; border-radius:24px; background:rgba(255,255,255,0.85); backdrop-filter:blur(6px); height:100%; box-shadow:0 6px 18px rgba(0,0,0,0.05); overflow:hidden; }
    .menu-card-image { height:220px; border-radius:24px 24px 0 0; overflow:hidden; background:#f2f2f2; display:flex; align-items:center; justify-content:center; }
    .menu-card-image img { width:100%; height:100%; object-fit:cover; display:block; }
    .menu-card-image.placeholder { background:linear-gradient(135deg,#ffe5d2,#ffffff); }
    .menu-card-image.placeholder img { width:140px; height:auto; object-fit:contain; opacity:.55; }
    .menu-card-content { padding:1rem 1.1rem 0.75rem; flex:1 1 auto; display:flex; flex-direction:column; }
    .menu-card-title { font-size:1.05rem; margin:0 0 .35rem; }
    .menu-card-description { font-size:0.9rem; line-height:1.35rem; margin:0 0 .75rem; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; min-height:4.05rem; }
    .menu-card-rating { font-size:.8rem; display:flex; align-items:center; gap:.35rem; margin-top:auto; color:#a0673f; }
    .menu-card-footer { padding:0 1.1rem 1.1rem; margin-top:auto; }
    @media (max-width: 576px){ #menuCards.menu-cards { gap:18px; } .menu-card-image { height:190px; } }
  /* === Steady single backgrounds for main sections === */
  .section { background-size: cover; background-repeat: no-repeat; background-position: center center; /* allow normal scrolling on background images */ background-color: transparent; }
    /* Assign single static images per section (no rotation) */
    /* === Sections are transparent so the shared fixed background shows through === */
    .section { background-size: cover; background-repeat: no-repeat; background-position: center center; background-color: transparent; }
  </style>
</head>
<body>
  <div class="bg-fixed" aria-hidden="true"></div>
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
      <h1 class="section-title" style="font-size:4.5rem;">Take a Sip. Take a Break.</h1>
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
        Your next cup is waiting at NaiTsa
      </p>
      <!-- Location Card with Map and Address -->
      <div class="card shadow-sm mb-3" style="max-width:410px; background:rgba(255,255,255,0.98); border-radius:16px;">
        <div style="border-radius:16px 16px 0 0; overflow:hidden;">
          <iframe
            width="100%"
            height="180"
            style="border:0; display:block;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d223.12180430306455!2d121.0944898450661!3d13.929589039573633!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd12b3c5bfce1f%3A0x3cd9f9ce0a7759b3!2sPJ%20LIZA%20STORE!5e1!3m2!1sen!2sph!4v1756433717417!5m2!1sen!2sph"></iframe>
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

  <!-- Menu Section (public view imitating user menu UI) -->
  <section class="section" id="menu">
    <div class="section-overlay"></div>
    <div class="section-content section-content--wide">
      <h2 class="section-title text-center w-100" style="font-size:3.2rem;">Menu</h2>

      <!-- Category filter buttons (from database) -->
      <div class="d-flex flex-wrap justify-content-center mb-3 w-100 gap-2">
        <button class="menu-category-btn active-category" id="showBestsellersBtn" data-category="bestsellers">Bestsellers</button>
        <?php foreach ($categories as $cat): ?>
          <button class="menu-category-btn category-link" data-category="<?php echo htmlspecialchars($cat['Category_Name']); ?>"><?php echo htmlspecialchars($cat['Category_Name']); ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Menu grid -->
      <div id="menuCards" class="menu-cards w-100 justify-content-center">
        <?php if (!empty($products)): ?>
          <?php
            $bestIds = array_column($bestsellers, 'Product_ID');
            $bestMap = [];
            foreach ($bestIds as $bid) { $bestMap[(int)$bid] = true; }
          ?>
          <?php foreach ($products as $p): ?>
            <?php
              $pid  = (int)($p['Product_ID'] ?? 0);
              $name = htmlspecialchars($p['Product_Name'] ?? '');
              $desc = htmlspecialchars($p['Product_desc'] ?? '');
              $cat  = htmlspecialchars($p['Category_Name'] ?? 'Other');
              $img  = !empty($p['Product_Image']) ? ('admin/uploads/products/' . $p['Product_Image']) : 'assets/naitsalogo.jpg';
              $price = isset($p['Price_Amount']) ? number_format((float)$p['Price_Amount'], 2) : null;
              $avg  = isset($avg_ratings[$pid]['avg']) ? number_format((float)$avg_ratings[$pid]['avg'], 2) : '0.0';
              $rcnt = isset($avg_ratings[$pid]['count']) ? (int)$avg_ratings[$pid]['count'] : 0;
              $isBest = isset($bestMap[$pid]);
              // allergens removed from public view
            ?>
            <div class="menu-card" data-category="<?php echo $cat; ?>" data-product-id="<?php echo $pid; ?>" data-bestseller="<?php echo $isBest ? '1' : '0'; ?>">
              <div class="menu-card-image <?php echo empty($p['Product_Image']) ? 'placeholder' : ''; ?>">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo $name; ?>">
              </div>
              <div class="menu-card-content">
                <div class="menu-card-header">
                  <h3 class="menu-card-title"><?php echo $name; ?></h3>
                  <?php if ($price !== null): ?>
                    <span class="menu-card-price">₱<?php echo $price; ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($desc)): ?>
                  <p class="menu-card-description"><?php echo $desc; ?></p>
                <?php endif; ?>
                <div class="menu-card-rating">
                  <svg class="star-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                  <span class="rating-value"><?php echo $avg; ?></span>
                  <span class="rating-count">(<?php echo $rcnt; ?> reviews)</span>
                </div>
              </div>
              <div class="menu-card-footer">
                <button class="add-to-cart-btn" data-product="<?php echo $name; ?>">
                  <svg class="plus-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Add to Cart
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-muted">No products available right now.</div>
        <?php endif; ?>
      </div>

      <div class="text-center w-100" style="margin-top: 0.2rem;">
        <a href="#" id="moreProductsBtn" class="btn btn-outline-soft-orange" style="font-size:1.09rem; padding:0.7rem 2.2rem; font-weight:600;">
          More Products
        </a>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="section" id="contact">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h2 class="section-title" style="font-size:3.2rem;">Contact Us</h2>
      <p class="section-desc">Have a question or want to say hi? Fill out the form below or visit us in-store. We love to connect with our Nai Tsa community!</p>
      <?php if (isset($_GET['contact'])): $c = $_GET['contact']; ?>
        <?php if ($c === 'success'): ?>
          <div class="alert alert-success">Thank you! Your message has been sent.</div>
        <?php elseif ($c === 'invalid'): ?>
          <div class="alert alert-danger">Please check your inputs: make sure name, a valid email, and message are provided.</div>
        <?php elseif ($c === 'limited'): ?>
          <div class="alert alert-warning">You’ve reached the limit for submissions. Please try again later.</div>
        <?php elseif ($c === 'mailcfg'): ?>
          <div class="alert alert-danger">Mail server is not configured on this site. Please contact the site administrator.</div>
        <?php elseif ($c === 'sendfail'): ?>
          <div class="alert alert-danger">We couldn’t send your message due to a temporary email issue. Please try again in a few minutes.</div>
        <?php elseif ($c === 'auth'): ?>
          <div class="alert alert-danger">Email server rejected the credentials. Please verify the mailbox email and password in the site settings.</div>
        <?php elseif ($c === 'connect'): ?>
          <div class="alert alert-danger">Cannot connect to the email server. If this persists, try again later or contact support.</div>
        <?php elseif ($c === 'cert'): ?>
          <div class="alert alert-danger">Certificate validation failed when contacting the email server. Please try again later.</div>
        <?php elseif ($c === 'addr'): ?>
          <div class="alert alert-danger">We couldn’t deliver your message to our inbox right now. Please try again later. If this keeps happening, the site mailbox may be unavailable—please contact the site owner.</div>
        <?php endif; ?>
      <?php endif; ?>
      <form method="POST" action="send_contact.php" novalidate>
  <div class="row">
    <div class="col-md-6 mb-3">
      <input type="text" class="form-control" name="name" placeholder="Your Name" maxlength="100" required>
    </div>
    <div class="col-md-6 mb-3">
      <input type="email" class="form-control" name="email" placeholder="Your Email" maxlength="150" required>
    </div>
  </div>
  <textarea class="form-control mb-3" name="message" rows="3" placeholder="Your Message" maxlength="1000" required></textarea>
  <!-- Honeypot field to reduce spam (hidden to avoid browser autofill) -->
  <input type="hidden" name="website" value="">
  <input type="hidden" name="return_to" value="index.php">
  <button type="submit" class="btn btn-soft-orange px-4">Send Message</button>
</form>
      <!-- Social / Contact quick links -->
      <div class="mt-4 d-flex flex-wrap align-items-center justify-content-center gap-3">
        <a href="https://www.instagram.com/naitsaofficial/" class="btn btn-light rounded-circle p-2" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
          <i class="bi bi-instagram" style="font-size:1.5rem;color:#C13584;"></i>
        </a>
        <a href="https://www.facebook.com/sipnslurp.milkteacorner" class="btn btn-light rounded-circle p-2" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
          <i class="bi bi-facebook" style="font-size:1.5rem;color:#1877F2;"></i>
        </a>
        <a href="tel:09672556259" class="btn btn-light rounded-pill px-3 py-2" aria-label="Call 09672556259">
          <i class="bi bi-telephone me-2" style="font-size:1.1rem;"></i>
          <span class="fw-semibold">09672556259</span>
        </a>
      </div>
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
    // Clean up contact param in URL after showing the alert once
    (function(){
      try {
        const params = new URLSearchParams(window.location.search);
        const contact = params.get('contact');
        if (contact) {
          params.delete('contact');
          const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '') + window.location.hash;
          history.replaceState({}, '', newUrl);
        }
      } catch (_) { /* noop */ }
    })();
  </script>
  <script>
    // If SweetAlert2 failed to load from jsDelivr, try another CDN
    (function(){
      if (!window.Swal) {
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/sweetalert2@11/dist/sweetalert2.min.js';
        s.async = true;
        s.onload = function(){ console.log('SweetAlert2 fallback loaded from unpkg'); };
        document.head.appendChild(s);
      }
    })();
  </script>
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

    // Rotating background JavaScript removed — sections use single static background images via CSS

    // Category filtering with Bestsellers default
    (function(){
      const filterButtons = document.querySelectorAll('.menu-category-btn');
      const menuCards = document.querySelectorAll('#menuCards .menu-card');
      function applyFilter(cat){
        menuCards.forEach(function(card){
          if (cat === 'bestsellers') {
            card.style.display = (card.dataset.bestseller === '1') ? '' : 'none';
          } else if (cat === 'all') {
            card.style.display = '';
          } else {
            card.style.display = (card.dataset.category === cat) ? '' : 'none';
          }
        });
      }
      filterButtons.forEach(function(btn){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          filterButtons.forEach(b=>b.classList.remove('active-category'));
          this.classList.add('active-category');
          applyFilter(this.dataset.category || 'all');
        });
      });
      // Default view
      const bestBtn = document.getElementById('showBestsellersBtn');
      if (bestBtn) {
        filterButtons.forEach(b=>b.classList.remove('active-category'));
        bestBtn.classList.add('active-category');
        applyFilter('bestsellers');
      }
    })();

    // Force login on any menu interactions
    function promptLogin(e){
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      // Use SweetAlert2 when available; otherwise fall back to native confirm
      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'Not Logged In',
          text: 'You must log in first to continue.',
          showCancelButton: true,
          confirmButtonText: 'Log In',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'login.php';
          }
        });
      } else {
        // Fallback if CDN blocked/offline
        console.warn('SweetAlert2 not loaded; using native confirm fallback.');
        if (window.confirm('You must log in first to continue.\n\nPress OK to go to the login page.')) {
          window.location.href = 'login.php';
        }
      }
    }

    // Apply login prompt to menu buttons, cards, and More Products
    document.querySelectorAll('.add-to-cart-btn').forEach(btn=>btn.addEventListener('click', promptLogin));
    document.querySelectorAll('#menuCards .menu-card').forEach(card=>card.addEventListener('click', function(e){
      // Avoid double if inner button handled—still prompt once
      if (!e.target.closest('button')) { promptLogin(e); }
    }));
    const moreBtn = document.getElementById('moreProductsBtn');
    if (moreBtn) { moreBtn.addEventListener('click', promptLogin); }

    // Defensive: also delegate in case cards/buttons render later or markup varies
    document.addEventListener('click', function(e){
      const addBtn = e.target.closest('.add-to-cart-btn');
      if (addBtn) { return promptLogin(e); }
      const cardEl = e.target.closest('#menuCards .menu-card');
      if (cardEl && !e.target.closest('button')) { return promptLogin(e); }
      if (e.target.closest('#moreProductsBtn')) { return promptLogin(e); }
    });

    // --- Real-time My Orders Modal Refresh ---
    // Place this after your other <script> code, before </body>

    function refreshMyOrders() {
      fetch('fetch_orders.php')
        .then(res => res.json())
        .then(orders_by_status => {
          // To Ship
          let html = '';
          const toShipEl = document.querySelector('#to-ship');
          if (orders_by_status['To Ship'] && orders_by_status['To Ship'].length) {
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
          if (toShipEl) toShipEl.innerHTML = html;

          // To Receive
          html = '';
          const toReceiveEl = document.querySelector('#to-receive');
          if (orders_by_status['To Receive'] && orders_by_status['To Receive'].length) {
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
          if (toReceiveEl) toReceiveEl.innerHTML = html;

          // Delivered
          html = '';
          const deliveredEl = document.querySelector('#delivered');
          if (orders_by_status['Delivered'] && orders_by_status['Delivered'].length) {
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
          if (deliveredEl) deliveredEl.innerHTML = html;
        });
    }

    // When the My Orders modal is shown, start refreshing every 5 seconds (guard if element missing)
    const myOrdersModalEl = document.getElementById('myOrdersModal');
    if (myOrdersModalEl) {
      myOrdersModalEl.addEventListener('show.bs.modal', function () {
        refreshMyOrders();
        window.myOrdersInterval = setInterval(refreshMyOrders, 5000);
      });
      // Stop refreshing when modal is hidden
      myOrdersModalEl.addEventListener('hidden.bs.modal', function () {
        if (window.myOrdersInterval) { clearInterval(window.myOrdersInterval); window.myOrdersInterval = null; }
      });
    }
  </script>
</body>
</html>