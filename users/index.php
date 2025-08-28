<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php'); // was login.php
    exit();
}
$customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : 'Guest';
$first_name = explode(' ', trim($customer_name))[0];
require_once "classes/database.php";
require_once "classes/order.php"; // Include the Order class

$db = new database();
$orderObj = new Order(); // Create an instance of the Order class
$products = $db->fetchAllProducts();
$order_id = 123; // The order you want to view
$items = $orderObj->getOrderItems($order_id); // Get the order items

// Fetch categories for menu dropdown
$categories = method_exists($db, 'fetchAllCategories') ? $db->fetchAllCategories() : [];

// Fetch user's orders grouped by status
$user_id = $_SESSION['customer_id'] ?? null;
$orders_by_status = [
    'To Ship' => [],
    'To Receive' => [],
    'Delivered' => []
];
if ($user_id) {
    $orders_by_status = $db->getOrdersByStatus($user_id);
}

// Fetch average ratings for all products
$avg_ratings = $db->getAverageRatings();

// Fetch recommended products for the user
$recommended = $db->getRecommendedProducts($_SESSION['customer_id']);

// Fetch bestsellers (e.g., top 4 by order count)
$bestsellers = $db->getBestsellerProducts(4); // You need to implement this method
$all_products = $db->fetchAllProducts();
?>
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
            <a class="nav-link" href="#menu">Menu</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#contact">Contact</a>
          </li>
        </ul>
        <div class="d-flex align-items-center ms-lg-auto flex-column flex-lg-row gap-2 gap-lg-0">
  <!-- Search Bar -->
  <form id="menuSearchForm" class="d-flex align-items-center me-2" role="search" autocomplete="off" style="min-width:180px;">
    <input class="form-control form-control-sm" type="search" placeholder="Search menu..." aria-label="Search" id="menuSearchInput" style="min-width:140px;">
  </form>
  <!-- Profile Button -->
  <button class="btn btn-soft-orange d-flex align-items-center" style="font-weight:600;" type="button" data-bs-toggle="offcanvas" data-bs-target="#profileOffcanvas" aria-controls="profileOffcanvas">
    <span style="font-size:1.2em; margin-right:0.4em;">👤</span> <?php echo htmlspecialchars($first_name); ?>
  </button>
</div>
      </div>
    </div>
  </nav>

  <!-- Home Section -->
  <section class="section" id="home" style="background-image: url('assets/bg7.jpg');">
    <div class="section-overlay"></div>
    <div class="section-content align-items-start">
      <h1 class="section-title" style="font-size:3.4rem; text-align:left;">
        Welcome, <?php echo htmlspecialchars($first_name); ?>
      </h1>
      <p class="section-desc" style="text-align:left;">Welcome to Nai Tsa - Take a pause. You deserve this moment of calm and your favorite drink!</p>
      <a href="#menu" class="btn btn-section" style="align-self:flex-start;">ORDER NOW</a>
    </div>
  </section>


  <!-- Menu Section -->


   <!-- Kumakain ako -->
  <section class="section" id="menu" style="background-image: url('assets/bg4.jpg');">
    <div class="section-overlay"></div>
    <div class="section-content section-content--wide">  <!-- changed: removed inline max-width -->
      <h2 class="section-title text-center w-100">Menu</h2>
      <!-- Recommended Section -->
      <div id="recommendedWrap" class="w-100 mb-3">
        <h3 class="text-center" style="font-weight:700;color:var(--text-dark);">Recommended for you</h3>
        <div class="menu-cards w-100 justify-content-center" id="recommendedCards"></div>
      </div>
      <div class="d-flex flex-wrap justify-content-center mb-3 w-100 gap-2">
        <button class="menu-category-btn" id="showBestsellersBtn">
          Bestsellers
        </button>
        <?php foreach ($categories as $cat): ?>
          <button class="menu-category-btn category-link"
                  data-category="<?php echo htmlspecialchars($cat['Category_Name']); ?>">
            <?php echo htmlspecialchars($cat['Category_Name']); ?>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="menu-cards w-100 justify-content-center" id="menuCards">
        <!-- Render menu products here -->
      </div>
    </div>
  </section>


  <!-- About Section -->
  <section class="section" id="about" style="background-image: url('assets/bg11.jpg');">
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
    </div>
  </section>

  <!-- Contact Section -->
  <section class="section" id="contact" style="background-image: url('assets/bg10.jpg');">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h2 class="section-title">Contact Us</h2>
      <p class="section-desc">Have a question or want to say hi? Fill out the form below or visit us in-store. We love to connect with our Nai Tsa community!</p>
      <form>
        <div class="row">
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" placeholder="Your Name" required>
          </div>
          <div class="col-md-6 mb-3">
            <input type="email" class="form-control" placeholder="Your Email" required>
          </div>
        </div>
        <textarea class="form-control mb-3" rows="3" placeholder="Your Message" required></textarea>
        <button type="submit" class="btn btn-soft-orange px-4">Send Message</button>
      </form>
    </div>
  </section>

  <!-- Profile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-end profile-offcanvas" tabindex="-1" id="profileOffcanvas" aria-labelledby="profileOffcanvasLabel">
  <div class="offcanvas-header" style="background:var(--soft-orange);color:#fff;">
    <h5 class="offcanvas-title" id="profileOffcanvasLabel">My Profile</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column align-items-center" style="background:var(--beige);">
    <div style="background:#fff; border-radius:50%; box-shadow:0 2px 12px rgba(255,178,122,0.10); padding:8px; margin-bottom:1.2rem;">
      <span style="font-size:3.2rem;">👤</span>
    </div>
    <h4 style="font-weight:700; color:var(--text-dark); margin-bottom:0.5rem;"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h4>
    <div style="color:#825e3a; font-size:1.08rem; margin-bottom:1.7rem;"><?php echo htmlspecialchars($_SESSION['customer_email']); ?></div>
    <hr style="width:80%;margin:1.2rem 0;">
  <a href="#" id="openMyOrdersBtn" class="btn btn-soft-orange w-100 mb-2" data-bs-toggle="modal" data-bs-target="#myOrdersModal">My Orders</a>
    <hr style="width:80%;margin:1.2rem 0;">
    <a href="logout.php" class="btn btn-outline-soft-orange w-100 mb-2">Logout</a>
    <button type="button" class="btn btn-soft-orange w-100" data-bs-dismiss="offcanvas">Close</button>
  </div>
</div>

  <!-- Cart Floating Action Button -->
<a href="#" id="cartFab" class="cart-fab position-fixed" title="View Cart" aria-label="Cart" data-bs-toggle="modal" data-bs-target="#cartModal" style="bottom:32px;right:32px;">
  <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16" style="display:block;">
    <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 14H4a.5.5 0 0 1-.491-.408L1.01 2H.5a.5.5 0 0 1-.5-.5zm3.14 4l1.25 6h7.22l1.25-6H3.14zM5.5 16a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm7 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
  </svg>
  <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.9em;display:none;">
    0
  </span>
</a>

  <!-- Cart Modal -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-end modal-lg">
    <div class="modal-content" style="border-radius:24px;">
      <div class="modal-header" style="background:var(--soft-orange);color:#fff;border-top-left-radius:24px;border-top-right-radius:24px;">
        <h5 class="modal-title" id="cartModalLabel">🛒 Your Cart</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="background:var(--beige);min-height:180px;">
        <div id="cart-items-list">
          <div class="text-center text-muted" style="font-size:1.1rem;">Your cart is empty.</div>
        </div>
      </div>
      <div class="modal-footer" style="background:var(--beige);border-bottom-left-radius:24px;border-bottom-right-radius:24px;">
        <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-soft-orange" id="checkoutBtn">Checkout</button>
      </div>
    </div>
  </div>
</div>

  <!-- Add-ons Modal -->
<div class="modal fade" id="addonsModal" tabindex="-1" aria-labelledby="addonsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:20px;">
      <div class="modal-header" style="background:var(--soft-orange);color:#fff;border-top-left-radius:20px;border-top-right-radius:20px;">
        <h5 class="modal-title" id="addonsModalLabel">Choose Add-ons</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="background:var(--beige);">
        <div id="addonsList" class="vstack gap-2"></div>
      </div>
      <div class="modal-footer" style="background:var(--beige);border-bottom-left-radius:20px;border-bottom-right-radius:20px;">
        <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Skip</button>
        <button type="button" class="btn btn-soft-orange" id="confirmAddonsBtn">Add to Cart</button>
      </div>
    </div>
  </div>
</div>

  <!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:20px;">
      <div class="modal-header" style="background:var(--soft-orange);color:#fff;border-top-left-radius:20px;border-top-right-radius:20px;">
        <h5 class="modal-title" id="paymentModalLabel">Checkout</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="paymentForm">
        <div class="modal-body" style="background:var(--beige);">
          <div class="mb-3">
            <label class="form-label mb-1">Order Type</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="orderType" id="pickup" value="Pick Up" checked>
              <label class="form-check-label" for="pickup">Pick Up</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="orderType" id="delivery" value="Delivery">
              <label class="form-check-label" for="delivery">Delivery</label>
            </div>
          </div>
          <div id="deliveryFields" style="display:none;">
            <div class="mb-3">
              <input type="text" class="form-control" name="street" placeholder="Street">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="barangay" placeholder="Barangay">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="city" placeholder="City">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="contact" placeholder="Contact Number">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label mb-1">Payment Method</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="paymentMethod" id="cod" value="COD" checked>
              <label class="form-check-label" for="cod">Cash on Delivery</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="paymentMethod" id="gcash" value="GCash">
              <label class="form-check-label" for="gcash">GCash</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="paymentMethod" id="credit" value="Credit Card">
              <label class="form-check-label" for="credit">Credit Card</label>
            </div>
          </div>
          <!-- GCash Info -->
          <div id="gcashFields" style="display:none;">
            <div class="alert alert-info mb-3" style="font-size:1.05em;">
              <strong>GCash:</strong><br>
              Name: Nai Tsa<br>
              Number: 09940780881
            </div>
          </div>
          <!-- Credit Card Info -->
          <div id="creditFields" style="display:none;">
            <div class="alert alert-info mb-3" style="font-size:1.05em;" id="creditCardInfo">
              <strong>Credit Card:</strong><br>
              Name: Nai Tsa<br>
              Number: <span id="generatedCardNumber"></span>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background:var(--beige);border-bottom-left-radius:20px;border-bottom-right-radius:20px;">
          <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-soft-orange">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <!-- My Orders Modal -->
  <div class="modal fade" id="myOrdersModal" tabindex="-1" aria-labelledby="myOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" style="border-radius:20px;">
        <div class="modal-header" style="background:var(--soft-orange);color:#fff;">
          <h5 class="modal-title" id="myOrdersModalLabel">My Orders</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:var(--beige);">
          <div id="orderStatusChips" class="order-status-filter d-flex flex-wrap gap-2 mb-3">
            <!-- Chips injected by JS -->
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <input id="ordersSearch" type="text" class="form-control form-control-sm" placeholder="Search orders/products..." style="max-width:260px;">
            <select id="ordersFilter" class="form-select form-select-sm" style="max-width:160px;">
              <option value="">Any Date</option>
              <option value="30">Last 30 Days</option>
              <option value="90">Last 90 Days</option>
              <option value="365">Last Year</option>
            </select>
          </div>
          <div id="ordersSummaryLine" class="small mb-2 text-muted"></div>
          <div id="ordersList"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Review Items Modal -->
  <div class="modal fade review-modal" id="reviewModal" tabindex="-1"
       aria-labelledby="reviewModalLabel" aria-hidden="true"
       data-bs-backdrop="static" data-bs-keyboard="false">  <!-- prevent behind-clicks -->
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" style="border-radius:20px;">
        <div class="modal-header" style="background:var(--soft-orange);color:#fff;">
          <h5 class="modal-title" id="reviewModalLabel">Rate your items</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="background:var(--beige);">
          <div id="reviewOrderHeader" class="mb-2 small text-muted"></div>
          <div id="reviewItemsContainer"></div>
        </div>
        <div class="modal-footer" style="background:var(--beige);">
          <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-soft-orange" id="submitReviewsBtn">Submit Reviews</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Product Details Modal (wide, with Add-ons) -->
  <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content" style="border-radius:20px;">
        <div class="modal-header" style="background:var(--soft-orange);color:#fff;">
          <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="background:var(--beige);">
          <div id="productDetailsContent">
            <!-- Injected by JS: left (image/details), right (add-ons) -->
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between align-items-center" style="background:var(--beige);">
          <div class="fw-semibold">Total: <span id="productWithAddonsTotal">₱0.00</span></div>
          <div>
            <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-soft-orange" id="modalAddToCartBtn">Add to Cart</button>
          </div>
        </div>
      </div>
    </div>
  </div>

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

    // Rotating background images for all main sections
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
      "assets/bg1.jpg",
      "assets/bg7.jpg",
      "assets/bg3.jpg"
    ];
    const menuImages = [
      "assets/bg4.jpg",
       "assets/bg2.jpg",
      "assets/bg5.jpg"
    ];
    const aboutImages = [
      "assets/bg8.jpg",
       "assets/bg11.jpg",
      "assets/bg9.jpg"
    ];
    const contactImages = [
      "assets/bg12.jpg",
       "assets/bg10.jpg",
      "assets/bg13.jpg"
    ];

    setupRotatingBg("home", homeImages);
    setupRotatingBg("menu", menuImages);
    setupRotatingBg("about", aboutImages);
    setupRotatingBg("contact", contactImages);

    // Cart logic
let cart = [];
let PENDING_ADD_TO_CART = null; // { productId, productName }
const cartBadge = document.getElementById('cart-badge');
const cartItemsList = document.getElementById('cart-items-list');

function updateCartBadge() {
  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  if (totalQty > 0) {
    cartBadge.textContent = totalQty;
    cartBadge.style.display = 'inline-block';
  } else {
    cartBadge.style.display = 'none';
  }
}

function money(n){ return Number(n||0).toFixed(2); }

function renderCartItems() {
  if (cart.length === 0) {
    cartItemsList.innerHTML = '<div class="text-center text-muted" style="font-size:1.1rem;">Your cart is empty.</div>';
    return;
  }
  cartItemsList.innerHTML = cart.map((item, idx) => {
    const addonsHtml = (item.addons && item.addons.length)
      ? `<div class="ms-2 small text-muted">${item.addons.map(a=>`${a.name} (+₱${money(a.price)})`).join(', ')}</div>`
      : '';
    return `
    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
      <div>
        <strong>${item.name}</strong>
        ${addonsHtml}
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary">${item.qty}</span>
        <button class="remove-cart-item" data-idx="${idx}" title="Remove">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5.5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6zm3 .5a.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6zm-7-1A1.5 1.5 0 0 1 5.5 4h5A1.5 1.5 0 0 1 12 5.5V6h1.5A.5.5 0 0 1 14 6.5v.5a.5.5 0 0 1-.5.5H2.5a.5.5 0 0 1-.5-.5v-.5A.5.5 0 0 1 2.5 6H4v-.5zM5.5 5a.5.5 0 0 0-.5.5V6h6v-.5a.5.5 0 0 0-.5-.5h-5z"/>
          </svg>
        </button>
      </div>
    </div>`;
  }).join('');
  
  // Add event listeners for remove buttons
  document.querySelectorAll('.remove-cart-item').forEach(btn => {
    btn.addEventListener('click', function() {
      const idx = parseInt(this.getAttribute('data-idx'));
      cart.splice(idx, 1);
      updateCartBadge();
      renderCartItems();
    });
  });
}

document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    const productName = this.getAttribute('data-product');
    const allProducts = <?php echo json_encode($all_products); ?>;
    const prod = (allProducts||[]).find(p => p.Product_Name === productName);
    if (!prod) return;
    await openProductDetailsWithAddons(prod);
  });
});

// When the cart modal is opened, always render the latest cart items
const cartFab = document.getElementById('cartFab');
const cartModalEl = document.getElementById('cartModal');
cartModalEl.addEventListener('show.bs.modal', function () {
  cartFab.classList.add('hide');
  renderCartItems();
});
cartModalEl.addEventListener('hidden.bs.modal', function () {
  cartFab.classList.remove('hide');
});

document.getElementById('checkoutBtn').addEventListener('click', function() {
  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  if (totalQty === 0) {
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Your cart is empty',
        text: 'Please add items before checking out.',
        confirmButtonColor: '#FFB27A'
      });
    } else {
      alert('Your cart is empty. Please add items before checking out.');
    }
    return;
  }
  // Show payment modal
  const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
  paymentModal.show();
});

// Payment modal logic
const paymentModalEl = document.getElementById('paymentModal');
const paymentForm = document.getElementById('paymentForm');
const deliveryFields = document.getElementById('deliveryFields');

// Show/hide delivery fields based on order type
document.querySelectorAll('input[name="orderType"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('deliveryFields').style.display =
      this.value === 'Delivery' ? 'block' : 'none';
  });
});

// Show/hide payment fields based on payment method
document.querySelectorAll('input[name="paymentMethod"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('gcashFields').style.display = this.value === 'GCash' ? 'block' : 'none';
    document.getElementById('creditFields').style.display = this.value === 'Credit Card' ? 'block' : 'none';
    if (this.value === 'Credit Card') {
      document.getElementById('generatedCardNumber').textContent = generateCreditCardNumber();
    }
  });
});

function generateCreditCardNumber() {
  // Simple random 16-digit number (not a real card, just for display)
  let num = '';
  for (let i = 0; i < 16; i++) {
    num += Math.floor(Math.random() * 10);
    if ((i + 1) % 4 === 0 && i !== 15) num += ' ';
  }
  return num;
}

// Handle payment form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const orderType = document.querySelector('input[name="orderType"]:checked').value;
  const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
  let street = '', barangay = '', city = '', contact = '';
  if (orderType === 'Delivery') {
    street = this.street.value.trim();
    barangay = this.barangay.value.trim();
    city = this.city.value.trim();
    contact = this.contact.value.trim();
    if (!street || !barangay || !city || !contact) {
      Swal.fire({
        icon: 'error',
        title: 'Missing Details',
        text: 'Please provide your street, barangay, city, and contact number.',
        confirmButtonColor: '#FFB27A'
      });
      return;
    }
  }
  // Send data to PHP
  fetch('checkout_process.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      orderType,
      paymentMethod,
      street,
      barangay,
      city,
      contact,
      cart
    })
  })
  .then(async res => {
    const text = await res.text();
    console.log('Raw response:', text); // <-- Add this line
    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error('Invalid JSON: ' + text);
    }
  })
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Order Confirmed!',
        text: 'Your order has been placed successfully.',
        confirmButtonColor: '#FFB27A'
      }).then(() => {
        cart.length = 0;
        updateCartBadge();
        renderCartItems();
        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Order Failed',
        text: data.message || 'There was a problem processing your order.',
        confirmButtonColor: '#FFB27A'
      });
    }
  })
  .catch(err => {
    Swal.fire({
      icon: 'error',
      title: 'Order Failed',
      text: err.message || 'A network or server error occurred.',
      confirmButtonColor: '#FFB27A'
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const allProducts = <?php echo json_encode($all_products); ?>;
  const recommended = <?php echo json_encode($recommended); ?>;
  const bestsellers = <?php echo json_encode($bestsellers); ?>;
  const avgRatings = <?php echo json_encode($avg_ratings); ?>;
  const menuCardsDiv = document.getElementById('menuCards');
  const recommendedWrap = document.getElementById('recommendedWrap');
  const recommendedCardsDiv = document.getElementById('recommendedCards');
  const showBestsellersBtn = document.getElementById('showBestsellersBtn');
  const menuSearchInput = document.getElementById('menuSearchInput');

  // Allergen icons (ensure only one definition)
  const allergenIcons = {
    Milk: "assets/milk.png",
    Eggs: "assets/boiled-egg.png",
    Peanuts: "assets/peanut.png",
    Soy: "assets/soy-sauce.png"
  };
  console.log('allergenIcons initialized', allergenIcons);

  function renderRecommendedCards(productsArr) {
    if (!recommendedCardsDiv) return;
    if (!Array.isArray(productsArr) || productsArr.length === 0) {
      recommendedWrap && recommendedWrap.classList.add('d-none');
      return;
    }
    // Cap to 4 suggestions
    const list = productsArr.slice(0, 4);
    recommendedCardsDiv.innerHTML = list.map(product => {
      let allergenIconsHtml = '';
      if (product.Product_allergens) {
        allergenIconsHtml = product.Product_allergens.split(',').map(a => a.trim()).map(allergen => {
          const icon = allergenIcons[allergen];
          return icon ? `<img src="${icon}" class="allergen-icon" title="${allergen}" alt="${allergen}">` : '';
        }).join('');
      }
      const pid = product.Product_ID;
      const avgInfo = avgRatings[pid];
      const avgVal = avgInfo ? avgInfo.avg : '0.0';
      const countVal = avgInfo ? avgInfo.count : 0;
      const priceDisplay = product.Price_Amount ? `₱${parseFloat(product.Price_Amount).toFixed(2)}` : '₱0.00';
      return `
        <div class="menu-card" data-product-id="${pid}">
          <div class="menu-card-image">
            <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}">
            <div class="allergen-icon-group">${allergenIconsHtml}</div>
          </div>
          <div class="menu-card-content">
            <div class="menu-card-header">
              <h3 class="menu-card-title">${product.Product_Name}</h3>
              <span class="menu-card-price">${priceDisplay}</span>
            </div>
            <p class="menu-card-description">${product.Product_desc || ''}</p>
            <div class="menu-card-rating">
              <svg class="star-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <span class="rating-value">${avgVal}</span>
              <span class="rating-count">(${countVal} reviews)</span>
            </div>
          </div>
          <div class="menu-card-footer">
            <button class="add-to-cart-btn" data-product="${product.Product_Name}">
              <svg class="plus-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add to Cart
            </button>
          </div>
        </div>
      `;
    }).join('');

  // Add-to-cart (recommended) -> open product details modal with add-ons
    recommendedCardsDiv.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', async e => {
        e.preventDefault();
        e.stopPropagation();
        const productName = btn.getAttribute('data-product');
        const allProducts = <?php echo json_encode($all_products); ?>;
        const prod = (allProducts||[]).find(p => p.Product_Name === productName);
        if (!prod) return;
    await openProductDetailsWithAddons(prod);
      });
    });

  // Card click -> modal (recommended) with add-ons
    recommendedCardsDiv.querySelectorAll('.menu-card').forEach(card => {
      card.addEventListener('click', e => {
        if (e.target.closest('.add-to-cart-btn')) return;
        const pid = card.dataset.productId;
        const product = allProducts.find(p => p.Product_ID == pid) || list.find(p => p.Product_ID == pid);
        if (!product) return;
    openProductDetailsWithAddons(product);
      });
    });
  }

  function renderMenuCards(productsArr) {
    if (!productsArr || !productsArr.length) {
      menuCardsDiv.innerHTML = `<div class="text-center text-muted" style="padding:1.5rem;">No products found.</div>`;
      return;
    }
    menuCardsDiv.innerHTML = productsArr.map(product => {
      let allergenIconsHtml = '';
      if (product.Product_allergens) {
        allergenIconsHtml = product.Product_allergens.split(',').map(a => a.trim()).map(allergen => {
          const icon = allergenIcons[allergen];
          return icon ? `<img src="${icon}" class="allergen-icon" title="${allergen}" alt="${allergen}">` : '';
        }).join('');
      }
      const pid = product.Product_ID;
      const avgInfo = avgRatings[pid];
      const avgVal = avgInfo ? avgInfo.avg : '0.0';
      const countVal = avgInfo ? avgInfo.count : 0;
      const priceDisplay = product.Price_Amount ? `₱${parseFloat(product.Price_Amount).toFixed(2)}` : '₱0.00';

      return `
        <div class="menu-card" data-product-id="${pid}">
          <div class="menu-card-image">
            <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}">
            <div class="allergen-icon-group">${allergenIconsHtml}</div>
          </div>
          <div class="menu-card-content">
            <div class="menu-card-header">
              <h3 class="menu-card-title">${product.Product_Name}</h3>
              <span class="menu-card-price">${priceDisplay}</span>
            </div>
            <p class="menu-card-description">${product.Product_desc || ''}</p>
            <div class="menu-card-rating">
              <svg class="star-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <span class="rating-value">${avgVal}</span>
              <span class="rating-count">(${countVal} reviews)</span>
            </div>
          </div>
          <div class="menu-card-footer">
            <button class="add-to-cart-btn" data-product="${product.Product_Name}">
              <svg class="plus-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add to Cart
            </button>
          </div>
        </div>
      `;
    }).join('');

  // Add-to-cart -> open product details modal with add-ons
    menuCardsDiv.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', async e => {
        e.preventDefault();
        e.stopPropagation();
        const productName = btn.getAttribute('data-product');
        const allProducts = <?php echo json_encode($all_products); ?>;
        const prod = (allProducts||[]).find(p => p.Product_Name === productName);
        if (!prod) return;
    await openProductDetailsWithAddons(prod);
      });
    });

  // Card click -> modal with add-ons
    menuCardsDiv.querySelectorAll('.menu-card').forEach(card => {
      card.addEventListener('click', e => {
        if (e.target.closest('.add-to-cart-btn')) return;
        const pid = card.dataset.productId;
        const product = allProducts.find(p => p.Product_ID == pid);
        if (!product) return;
    openProductDetailsWithAddons(product);
      });
    });
  }

  // CATEGORY + SEARCH FILTERS
  let currentCategory = null;
  function applyMenuFilters() {
    let filtered = allProducts;
    if (currentCategory) {
      filtered = filtered.filter(p => p.Category_Name === currentCategory);
    }
    const q = menuSearchInput.value.trim().toLowerCase();
    if (q) {
      filtered = filtered.filter(p =>
        (p.Product_Name && p.Product_Name.toLowerCase().includes(q)) ||
        (p.Product_desc && p.Product_desc.toLowerCase().includes(q))
      );
    }
    renderMenuCards(filtered);
  }

  document.querySelectorAll('.category-link').forEach(btn => {
    btn.addEventListener('click', () => {
      currentCategory = btn.getAttribute('data-category');
      document.querySelectorAll('.menu-category-btn').forEach(b => b.classList.remove('active-category'));
      btn.classList.add('active-category');
      menuSearchInput.value = '';
      applyMenuFilters();
    });
  });

  if (showBestsellersBtn) {
    showBestsellersBtn.addEventListener('click', () => {
      currentCategory = null;
      document.querySelectorAll('.menu-category-btn').forEach(b => b.classList.remove('active-category'));
      showBestsellersBtn.classList.add('active-category');
      menuSearchInput.value = '';
      if (Array.isArray(bestsellers) && bestsellers.length) {
        renderMenuCards(bestsellers);
      } else {
        renderMenuCards(allProducts);
      }
    });
  }

  if (menuSearchInput) {
    menuSearchInput.addEventListener('input', applyMenuFilters);
  }

  // INITIAL RENDER
  // Recommended row
  renderRecommendedCards(recommended);

  if (!Array.isArray(bestsellers) || bestsellers.length === 0) {
    console.warn('No bestsellers found; showing all products.');
    renderMenuCards(allProducts);
    showBestsellersBtn && showBestsellersBtn.classList.add('active-category');
  } else {
    renderMenuCards(bestsellers);
    showBestsellersBtn && showBestsellersBtn.classList.add('active-category');
  }
});

// REMOVE / FIX MISSING IMAGE TO STOP 404
// Delete bg13.jpg from the contactImages array above OR add the actual file assets/bg13.jpg.

const ORDER_STATUS_STEPS = ["Pending","Processing","To Ship","To Receive","Delivered"];
const STATUS_LABEL_MAP = {
  Pending: "Pending",
  Processing: "Preparing",
  "To Ship": "To Ship",
  "To Receive": "To Receive",
  Delivered: "Delivered",
  Cancelled: "Cancelled"
};
const STATUS_BADGE_CLASS = {
  Pending: "bg-secondary",
  Processing: "bg-info text-dark",
  "To Ship": "bg-primary",
  "To Receive": "bg-warning text-dark",
  Delivered: "bg-success",
  Cancelled: "bg-dark"
};
function renderProgress(current) {
  const idx = ORDER_STATUS_STEPS.indexOf(current);
  return `
    <div class="order-progress d-flex align-items-center mb-2">
      ${ORDER_STATUS_STEPS.map((s,i)=>{
        const state = i < idx ? 'completed' : (i === idx ? 'active' : 'upcoming');
        return `
          <div class="step ${state}">
            <div class="dot">${i < idx ? '✓' : ''}</div>
            <div class="label">${STATUS_LABEL_MAP[s]||s}</div>
          </div>
          ${i<ORDER_STATUS_STEPS.length-1?`<div class="bar ${i<idx?'filled':''}"></div>`:""}
        `;
      }).join('')}
    </div>`;
}

// ================== ORDER LIST / FILTER UI ==================
const RAW_STATUS_STEPS = ["Pending","Processing","To Ship","To Receive","Delivered"];
const STATUS_DISPLAY = {
  All: "All",
  Pending:"Pending",
  Processing:"Processing",
  "To Ship":"To Ship",
  "To Receive":"To Receive",
  Delivered:"Delivered",
  Cancelled:"Cancelled"
};
const CHIP_SEQUENCE = ["All","Pending","Processing","To Ship","To Receive","Delivered","Cancelled"];

let ORDERS_CACHE = [];
let ACTIVE_STATUS = "All";

function skeletonOrders(count=3){
  return Array.from({length:count}).map(()=>`
    <div class="card mb-2" aria-hidden="true" style="border-radius:14px;">
      <div class="card-body">
        <div class="placeholder-wave">
          <div class="placeholder col-4 mb-2"></div>
          <div class="placeholder col-7 mb-2"></div>
          <div class="placeholder col-5"></div>
        </div>
      </div>
    </div>`).join('');
}

// Map backend combination -> UI status (adjust if your real logic differs)
function deriveUiStatus(o){
  // Honor backend status primarily; use payment only to refine Processing -> To Ship
  if (o.order_status === "Delivered") return "Delivered";
  if (o.order_status === "Cancelled") return "Cancelled";
  if (o.order_status === "Pending") return "Pending"; // don’t treat as To Ship
  if (o.order_status === "Processing") {
    return (o.payment_status === "Paid") ? "To Ship" : "Processing";
  }
  if (o.order_status === "To Ship") return "To Ship";
  if (o.order_status === "To Receive") return "To Receive";
  // Fallback to raw if it matches known steps
  if (RAW_STATUS_STEPS.includes(o.order_status)) return o.order_status;
  return o.order_status || "Pending";
}

function buildStatusCounts(rawList){
  const counts = {All: rawList.length};
  CHIP_SEQUENCE.forEach(s => { if(s!=="All") counts[s]=0; });
  rawList.forEach(o => { const st = deriveUiStatus(o); if(counts[st]!==undefined) counts[st]++; });
  return counts;
}

function renderStatusChips(){
  const container = document.getElementById('orderStatusChips');
  const counts = buildStatusCounts(ORDERS_CACHE);
  container.innerHTML = CHIP_SEQUENCE
    .filter(s => s==="All" || counts[s] > 0) // optionally hide zero statuses except All
    .map(s => `
      <div class="status-chip ${ACTIVE_STATUS===s?'active':''}" data-status="${s}">
        <span>${STATUS_DISPLAY[s]||s}</span>
        <span class="count">${counts[s]||0}</span>
      </div>`).join('');
  container.querySelectorAll('.status-chip').forEach(chip=>{
    chip.addEventListener('click', ()=>{
      ACTIVE_STATUS = chip.dataset.status;
      renderStatusChips();
      renderOrders();
    });
  });
}

function passesDateFilter(o){
  const sel = document.getElementById('ordersFilter').value;
  if(!sel) return true;
  const days = parseInt(sel,10);
  const orderDate = new Date(o.Order_Date.replace(' ','T'));
  const cutoff = new Date();
  cutoff.setDate(cutoff.getDate() - days);
  return orderDate >= cutoff;
}

function renderOrders(){
  const listEl = document.getElementById('ordersList');
  const q = document.getElementById('ordersSearch').value.trim().toLowerCase();

  let processed = ORDERS_CACHE.map(o => ({...o, ui_status: deriveUiStatus(o)}));

  if(ACTIVE_STATUS !== "All"){
    processed = processed.filter(o => o.ui_status === ACTIVE_STATUS);
  }
  processed = processed.filter(passesDateFilter);

  if(q){
    processed = processed.filter(o =>
      String(o.Order_ID).includes(q) ||
      o.items.some(it => it.Product_Name && it.Product_Name.toLowerCase().includes(q))
    );
  }

  const totalShowing = processed.length;
  const summary = `${totalShowing} order${totalShowing!==1?'s':''} showing`;
  document.getElementById('ordersSummaryLine').textContent = summary;

  if(!processed.length){
    listEl.innerHTML = `<div class="text-muted py-4 text-center">No matching orders.</div>`;
    return;
  }

  listEl.innerHTML = processed.map(o => {
    const uiStatus = o.ui_status;
    const badgeClass = (uiStatus==="Delivered"?"bg-success":
                       uiStatus==="To Ship"?"bg-primary":
                       uiStatus==="To Receive"?"bg-warning text-dark":
                       uiStatus==="Processing"?"bg-info text-dark":
                       uiStatus==="Pending"?"bg-secondary":
                       uiStatus==="Cancelled"?"bg-dark":"bg-secondary");
    const itemsPreview = o.items.slice(0,3).map(it=>`
      <div class="d-inline-flex align-items-center me-2 mb-1" style="font-size:.75rem;">
        <img src="../admin/uploads/products/${it.Product_Image}" style="width:34px;height:34px;object-fit:cover;border-radius:8px;margin-right:4px;">
        <span>${it.Product_Name} x ${it.Quantity}</span>
      </div>`).join('') + (o.items.length>3? `<span class="text-muted small">+${o.items.length-3} more</span>`:'');

    return `
      <div class="card mb-2" style="border-radius:16px;">
        <div class="card-body">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
              <strong>Order #${o.Order_ID}</strong> • ${o.Order_Date}
              <div class="mt-1">${renderProgress(uiStatus)}</div>
            </div>
            <span class="badge ${badgeClass}" style="height:fit-content;">${uiStatus}</span>
          </div>
          <div class="mt-2">${itemsPreview}</div>
          <div class="mt-3 d-flex flex-wrap gap-2">
            ${actionButtons(uiStatus,o.Order_ID)}
          </div>
          <div class="mt-2 fw-semibold">Total: ₱${parseFloat(o.Order_Amount).toFixed(2)}</div>
        </div>
      </div>`;
  }).join('');
}

function actionButtons(status,id){
  // Look up order to decide if review should be enabled
  const order = ORDERS_CACHE.find(o => String(o.Order_ID) === String(id));
  const allReviewed = order?.items?.length ? order.items.every(it => !!it.Already_Reviewed) : false;
  switch(status){
    case "Pending":
      return `<button class="btn btn-outline-soft-orange btn-sm" data-action="cancel" data-id="${id}">Cancel</button>`;
    case "To Receive":
      return `<button class="btn btn-soft-orange btn-sm" data-action="confirm" data-id="${id}">Confirm Received</button>`;
    case "Delivered":
      return allReviewed
        ? `<button class="btn btn-secondary btn-sm" data-action="review" data-id="${id}" disabled>Reviewed</button>`
        : `<button class="btn btn-soft-orange btn-sm" data-action="review" data-id="${id}">Review Items</button>`;
    default:
      return '';
  }
}

// (Removed earlier duplicate click listener to avoid conflicts)

// Ensure offcanvas closes before opening My Orders to avoid aria-hidden focus issues
(function(){
  const trigger = document.getElementById('openMyOrdersBtn');
  const offcanvasEl = document.getElementById('profileOffcanvas');
  const modalEl = document.getElementById('myOrdersModal');
  if(!trigger || !offcanvasEl || !modalEl) return;
  trigger.addEventListener('click', (e)=>{
    const instance = bootstrap.Offcanvas.getInstance(offcanvasEl) || bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
    if(offcanvasEl.classList.contains('show')){
      e.preventDefault();
      offcanvasEl.addEventListener('hidden.bs.offcanvas', ()=>{
        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
      }, {once:true});
      instance.hide();
    }
  });
})();

// On modal open fetch orders (with success handling)
document.getElementById('myOrdersModal').addEventListener('show.bs.modal', ()=>{
  const listEl = document.getElementById('ordersList');
  if(listEl) listEl.innerHTML = skeletonOrders();
  ACTIVE_STATUS = "All";
  fetch('orders_api.php?t=' + Date.now())
    .then(r=> r.ok ? r.json() : Promise.reject(r.status))
    .then(data=>{
      ORDERS_CACHE = Array.isArray(data) ? data : [];
      renderStatusChips();
      renderOrders();
    })
    .catch((err)=>{
      if(listEl) listEl.innerHTML = `<div class="text-danger">Failed to load orders.</div>`;
      console.error('orders_api failed', err);
    });
});

// Search / date filter
document.getElementById('ordersSearch').addEventListener('input', ()=>renderOrders());
document.getElementById('ordersFilter').addEventListener('change', ()=>renderOrders());

// ---------- Review Items (My Orders) ----------
let CURRENT_REVIEW_ORDER_ID = null;

function buildStarsHtml(initial=0){
  // 5 clickable stars
  return `
    <div class="review-stars" role="radiogroup" aria-label="Rating">
      ${[1,2,3,4,5].map(v => `
        <span class="review-star ${v<=initial?'active':''}" data-value="${v}" aria-label="${v} star${v>1?'s':''}" role="radio"></span>
      `).join('')}
    </div>`;
}

function openReviewModalByOrderId(orderId){
  const order = ORDERS_CACHE.find(o => String(o.Order_ID) === String(orderId));
  if(!order || !order.items || !order.items.length){
    Swal.fire({icon:'error', title:'No items to review.', confirmButtonColor:'#FFB27A'});
    return;
  }
  const pendingItems = order.items.filter(it => !it.Already_Reviewed);
  if(!pendingItems.length){
    // All reviewed; disable the review button in the orders list and inform user
    document.querySelector(`#myOrdersModal [data-action="review"][data-id="${orderId}"]`)?.setAttribute('disabled','disabled');
    Swal.fire({icon:'info', title:'You already reviewed all items in this order.', timer:1600, showConfirmButton:false});
    return;
  }
  CURRENT_REVIEW_ORDER_ID = orderId;
  document.getElementById('reviewOrderHeader').textContent =
    `Order #${order.Order_ID} • ${order.Order_Date}`;

  const container = document.getElementById('reviewItemsContainer');
  const itemsHtml = pendingItems.map(it => {
    const pid = it.Product_ID || it.product_id || it.ProductId || it.id || 0;  // robust fallback
    return `
      <div class="card product-review-card mb-3" data-product-id="${pid}" data-rating="0" data-locked="0"
           style="border-radius:16px; overflow:hidden;">
        <div class="card-body d-flex align-items-start">
          <img src="../admin/uploads/products/${it.Product_Image}" alt="${it.Product_Name}"
               style="width:64px;height:64px;object-fit:cover;border-radius:10px;">
          <div class="ms-3 flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold" style="color:var(--text-dark)">${it.Product_Name}</div>
                <div class="text-muted small">Please rate your item</div>
              </div>
              <span class="badge bg-light text-dark">x${it.Quantity}</span>
            </div>
            <div class="mt-2">${buildStarsHtml(0)}</div>
            <textarea class="form-control form-control-sm mt-3 review-text" rows="2"
              placeholder="Share a short review (optional)"></textarea>
          </div>
        </div>
      </div>
    `;
  }).join('');
  container.innerHTML = itemsHtml;

  bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}

// Single handler: close My Orders first to avoid stacked modals (prevents aria-hidden warning)
document.getElementById('myOrdersModal').addEventListener('click', e=>{
  const btn = e.target.closest('[data-action]');
  if(!btn) return;
  const {action,id} = btn.dataset;

  if(action==="review"){
    const ordersEl = document.getElementById('myOrdersModal');
    const ordersModal = bootstrap.Modal.getInstance(ordersEl);
    if (ordersEl.classList.contains('show')) {
      ordersEl.addEventListener('hidden.bs.modal', () => openReviewModalByOrderId(id), { once:true });
      ordersModal.hide();
    } else {
      openReviewModalByOrderId(id);
    }
    return;
  }

  if(action==="cancel"){
    // Allow cancel only when current UI status is Pending
    const order = ORDERS_CACHE.find(o => String(o.Order_ID) === String(id));
    const uiStatus = order ? deriveUiStatus(order) : null;
    if (uiStatus !== 'Pending') {
      Swal.fire({icon:'info', title:'Cannot cancel', text:'Only pending orders can be canceled.', confirmButtonColor:'#FFB27A'});
      return;
    }

    Swal.fire({title:'Cancel this order?',icon:'warning',showCancelButton:true,confirmButtonColor:'#FFB27A'})
      .then(async r=>{ 
        if(r.isConfirmed){ 
          try{
            const resp = await fetch('cancel_order.php', {
              method:'POST',
              headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','Accept':'application/json'},
              body: new URLSearchParams({order_id: id}).toString()
            });
            const data = await resp.json().catch(()=>({success:false,message:'Invalid response'}));
            console.log('cancel_order.php response', resp.status, data);
            if(resp.ok && data.success){
              const idx = ORDERS_CACHE.findIndex(o=>String(o.Order_ID)===String(id));
              if(idx!==-1){ ORDERS_CACHE[idx].order_status = 'Cancelled'; }
              renderStatusChips();
              renderOrders();
              Swal.fire({icon:'success',title:'Order canceled',timer:1200,showConfirmButton:false});
            } else {
              Swal.fire({icon:'error',title:data.message||'Unable to cancel',timer:1800,showConfirmButton:false});
            }
          }catch(err){
            Swal.fire({icon:'error',title:'Network error',text:String(err).slice(0,160),confirmButtonColor:'#FFB27A'});
          }
        }
      });
  } else if(action==="confirm"){
    Swal.fire({title:'Confirm receipt?',icon:'question',showCancelButton:true,confirmButtonColor:'#FFB27A'})
      .then(r=>{ if(r.isConfirmed){ Swal.fire({icon:'success',title:'Thank you!',timer:1200,showConfirmButton:false}); }});
  }
});

// Submit reviews: ensure numeric product_id
document.getElementById('submitReviewsBtn').addEventListener('click', async ()=>{
  const cards = Array.from(document.querySelectorAll('#reviewItemsContainer .product-review-card'));
  const raw = cards
    .filter(c => c.dataset.locked !== '1')
    .map(c => {
    const datasetRating = Number(c.dataset.rating) || 0;
    const countedRating = c.querySelectorAll('.review-star.active').length;
    const rating = datasetRating || countedRating || 0;
    const product_id = Number(c.dataset.productId) || 0;
    const review_text = c.querySelector('.review-text')?.value?.trim() || '';
    return { product_id, rating, review_text };
  });
  const payload = raw.filter(x => x.product_id > 0 && x.rating > 0);

  if(payload.length === 0){
    // Diagnose why it's empty: missing ids or ratings?
    const missingIds = raw.filter(x => x.rating > 0 && x.product_id <= 0).length;
    const zeroRatings = raw.filter(x => x.product_id > 0 && x.rating <= 0).length;
    const noneInteracted = raw.every(x => x.rating <= 0);
    let text = 'Please tap on the stars to rate at least one item.';
    if (missingIds > 0 && !noneInteracted) {
      text = 'We could not link your rating to a product. Please close My Orders, reopen it, then try again.';
    }
    if (missingIds > 0 && zeroRatings > 0) {
      text += ' (Some items are missing product IDs; refresh the page if this persists.)';
    }
    Swal.fire({icon:'warning', title:'Please rate at least one item.', text, confirmButtonColor:'#FFB27A'});
    return;
  }

  const btn = document.getElementById('submitReviewsBtn');
  btn.disabled = true;
  const original = btn.textContent;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

  try {
    const results = await Promise.all(payload.map(async (p) => {
      const form = new FormData();
      form.append('product_id', String(p.product_id));
      form.append('rating', String(p.rating));
      form.append('review_text', p.review_text);
      const res = await fetch('submit_review.php', { method:'POST', body: form });
      return res.json();
    }));
    const failed = results.filter(r => !r?.success);
    if(failed.length===0){
      // Mark items as reviewed in cache
      const order = ORDERS_CACHE.find(o => String(o.Order_ID) === String(CURRENT_REVIEW_ORDER_ID));
      if (order && Array.isArray(order.items)) {
        order.items.forEach(it => {
          if (payload.some(p => p.product_id === (it.Product_ID||it.product_id||it.ProductId||it.id))) {
            it.Already_Reviewed = true;
          }
        });
      }
      Swal.fire({icon:'success', title:'Thank you for your reviews!', timer:1500, showConfirmButton:false});
      bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
      // Re-render orders so the Review button can disable if all reviewed
      renderOrders();
    } else {
      Swal.fire({icon:'error', title:'Some reviews failed', text: failed.map(f=>f.message||'Error').join('\n'), confirmButtonColor:'#FFB27A'});
    }
  } catch(err){
    Swal.fire({icon:'error', title:'Unable to submit reviews', text:String(err||'Error'), confirmButtonColor:'#FFB27A'});
  } finally {
    btn.disabled = false;
    btn.textContent = original;
  }
});

// Enable clicking stars in the Review Items modal (event delegation)
(function(){
  const reviewModalEl = document.getElementById('reviewModal');
  if(!reviewModalEl) return;

  reviewModalEl.addEventListener('click', (e) => {
    const star = e.target.closest('.review-star');
    if (!star) return;
    const card = star.closest('.product-review-card');
    if (!card) return;
  if (card.dataset.locked === '1') return; // ignore interactions on locked cards
    const val = Number(star.dataset.value) || 0;
    card.dataset.rating = String(val);
    const stars = card.querySelectorAll('.review-star');
    stars.forEach(s => {
      const active = Number(s.dataset.value) <= val;
      s.classList.toggle('active', active);
      s.setAttribute('aria-checked', active ? 'true' : 'false');
      s.setAttribute('tabindex', '0');
    });
  });
})();

// Replace card click handlers to load add-ons into product modal
function buildProductModalHtml(product, addons){
  const allergens = product.Product_allergens || 'None';
  const basePrice = Number(product.Price_Amount||0);
  const priceDisplay = '₱' + basePrice.toFixed(2);
  const addonsHtml = (addons||[]).map(a=>`
    <label class="d-flex align-items-center justify-content-between border rounded p-2 bg-white mb-2">
      <div class="form-check">
        <input class="form-check-input addon-choice" type="checkbox" value="${a.Addon_ID}" data-name="${a.Addon_Name}" data-price="${a.Addon_Price}">
        <span>${a.Addon_Name}</span>
      </div>
      <span>₱ ${Number(a.Addon_Price).toFixed(2)}</span>
    </label>
  `).join('') || '<div class="text-muted">No add-ons available.</div>';

  return `
    <div class="row g-3">
      <div class="col-lg-5">
        <div class="text-center mb-3">
          <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}" style="max-width:260px;max-height:260px;border-radius:12px;object-fit:cover;">
        </div>
        <h4 class="mb-2">${product.Product_Name}</h4>
        <div class="mb-2"><strong>Description:</strong><br>${product.Product_desc || ''}</div>
        <div class="mb-2"><strong>Allergens:</strong> ${allergens}</div>
        <div class="mb-2"><strong>Base Price:</strong> ${priceDisplay}</div>
      </div>
      <div class="col-lg-7">
        <h5 class="mb-2">Add-ons</h5>
        <div id="productAddonsList">${addonsHtml}</div>
      </div>
    </div>`;
}

function updateProductModalTotal(basePrice){
  const checks = Array.from(document.querySelectorAll('#productAddonsList .addon-choice:checked'));
  const extra = checks.reduce((sum,c)=> sum + (Number(c.getAttribute('data-price'))||0), 0);
  const total = Number(basePrice||0) + extra;
  const el = document.getElementById('productWithAddonsTotal');
  if (el) el.textContent = '₱' + total.toFixed(2);
}

async function openProductDetailsWithAddons(product){
  try{
    const res = await fetch('get_product_addons.php?product_id='+product.Product_ID+'&t='+Date.now());
    const data = await res.json();
    const addons = data.success ? (data.addons||[]) : [];
    document.getElementById('productDetailsContent').innerHTML = buildProductModalHtml(product, addons);
    updateProductModalTotal(Number(product.Price_Amount||0));
    document.querySelectorAll('#productAddonsList .addon-choice').forEach(cb=>{
      cb.addEventListener('change', ()=> updateProductModalTotal(Number(product.Price_Amount||0)));
    });

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productDetailsModal'));
    modal.show();

    document.getElementById('modalAddToCartBtn').onclick = ()=>{
      const selected = Array.from(document.querySelectorAll('#productAddonsList .addon-choice:checked'))
        .map(c=>({ id:Number(c.value), name:c.getAttribute('data-name'), price:Number(c.getAttribute('data-price'))||0, qty:1 }));
      const found = cart.find(i => i.name === product.Product_Name);
      if (found) {
        found.qty += 1;
        found.addons = selected;
      } else {
        cart.push({ name: product.Product_Name, qty: 1, addons: selected });
      }
      updateCartBadge();
      renderCartItems();
      Swal.fire({toast:true, position:'top-end', icon:'success', title:'Added to cart!', showConfirmButton:false, timer:1200});
      modal.hide();
    };
  }catch(err){
    console.error('openProductDetailsWithAddons failed', err);
  }
}

// Hook into existing card click flows to use the new modal
(function(){
  function attachCardHandlers(container){
    if(!container) return;
    container.querySelectorAll('.menu-card').forEach(card => {
      card.addEventListener('click', e => {
        if (e.target.closest('.add-to-cart-btn')) return; // handled separately
        const pid = card.dataset.productId;
        const product = (<?php echo json_encode($all_products); ?> || []).find(p => String(p.Product_ID) === String(pid));
        if (!product) return;
        openProductDetailsWithAddons(product);
      });
    });
    container.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault(); e.stopPropagation();
        const name = btn.getAttribute('data-product');
        const product = (<?php echo json_encode($all_products); ?> || []).find(p => p.Product_Name === name);
        if (!product) return;
        openProductDetailsWithAddons(product);
      });
    });
  }
  // Initial attachment for recommended and menu lists after render functions run
  const origRenderRecommended = (typeof renderRecommendedCards === 'function') ? renderRecommendedCards : null;
  const origRenderMenu = (typeof renderMenuCards === 'function') ? renderMenuCards : null;
})();
  </script>

  <script>
  async function openAddonsModal(productId, productName){
    PENDING_ADD_TO_CART = { productId, productName };
    try{
      const res = await fetch('get_product_addons.php?product_id='+productId+'&t='+Date.now());
      const data = await res.json();
      const list = data.success ? (data.addons||[]) : [];
      const wrap = document.getElementById('addonsList');
      if (!list.length) {
        // No add-ons: add directly
        const found = cart.find(i => i.name === productName);
        if (found) found.qty += 1; else cart.push({ name: productName, qty: 1, addons: [] });
        updateCartBadge();
        renderCartItems();
        Swal.fire({toast:true, position:'top-end', icon:'success', title:'Added to cart!', showConfirmButton:false, timer:1200});
        return;
      }
      wrap.innerHTML = list.map(a=>`
        <label class="d-flex align-items-center justify-content-between border rounded p-2 bg-white">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${a.Addon_ID}" data-name="${a.Addon_Name}" data-price="${a.Addon_Price}">
            <span>${a.Addon_Name}</span>
          </div>
          <span>₱ ${Number(a.Addon_Price).toFixed(2)}</span>
        </label>
      `).join('');
      bootstrap.Modal.getOrCreateInstance(document.getElementById('addonsModal')).show();
    }catch(err){
      console.error('addons fetch failed', err);
      const found = cart.find(i => i.name === productName);
      if (found) found.qty += 1; else cart.push({ name: productName, qty: 1, addons: [] });
      updateCartBadge();
      renderCartItems();
      Swal.fire({toast:true, position:'top-end', icon:'success', title:'Added to cart!', showConfirmButton:false, timer:1200});
    }
  }

  document.getElementById('confirmAddonsBtn').addEventListener('click', ()=>{
    const modalEl = document.getElementById('addonsModal');
    const checks = Array.from(modalEl.querySelectorAll('input[type="checkbox"]:checked'));
    const addons = checks.map(c=>({ id:Number(c.value), name:c.getAttribute('data-name'), price:Number(c.getAttribute('data-price'))||0, qty:1 }));
    if (PENDING_ADD_TO_CART){
      const found = cart.find(i => i.name === PENDING_ADD_TO_CART.productName);
      if (found) {
        found.qty += 1;
        found.addons = addons; // last selection wins for simplicity
      } else {
        cart.push({ name: PENDING_ADD_TO_CART.productName, qty: 1, addons });
      }
      updateCartBadge();
      renderCartItems();
      Swal.fire({toast:true, position:'top-end', icon:'success', title:'Added to cart!', showConfirmButton:false, timer:1200});
    }
    PENDING_ADD_TO_CART = null;
    bootstrap.Modal.getInstance(modalEl).hide();
  });
  </script>

</body>
</html>