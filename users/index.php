<?php
session_start();
// Attempt to restore session from remember-me cookie if present
//just EDIT hello
require_once __DIR__ . '/../includes/remember.php';
if (!isset($_SESSION['customer_id'])) {
  header('Location: ../login.php'); // was login.php edit ito
  exit();
}
$customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : 'Guest';
$first_name = explode(' ', trim($customer_name))[0];
require_once "classes/database.php";
require_once "classes/shop_repositories.php"; // Repositories for cleaner OOP access
require_once "classes/order.php"; // Include the Order class

$db = new database();
$productRepo  = new ProductRepository($db);
$categoryRepo = new CategoryRepository($db);
$orderRepo    = new OrderRepository($db);
$addressRepo  = new AddressRepository($db);
$reviewRepo   = new ReviewRepository($db);
$orderObj = new Order(); // Create an instance of the Order class
$products = $productRepo->all();
$order_id = 123; // The order you want to view
$items = $orderObj->getOrderItems($order_id); // Get the order items

// Fetch categories for menu dropdown
$categories = $categoryRepo->all();

// Fetch user's orders grouped by status
$user_id = $_SESSION['customer_id'] ?? null;
$orders_by_status = [
    'To Ship' => [],
    'To Receive' => [],
    'Delivered' => []
];
if ($user_id) {
  $orders_by_status = $orderRepo->getOrdersByStatus($user_id);
}

// Fetch average ratings for all products
$avg_ratings = $reviewRepo->getAverageRatings();

// Fetch recommended products for the user
$recommended = $productRepo->recommendedForCustomer($_SESSION['customer_id']);

// Fetch bestsellers (e.g., top 4 by order count)
$bestsellers = $productRepo->bestsellers(4); // Uses repository wrapper
$all_products = $productRepo->all();

$savedAddress = [];
try {
  if (!empty($user_id)) {
    $savedAddress = $addressRepo->getSavedDeliveryAddress((int)$user_id) ?: [];
  }
} catch (Throwable $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nai Tsa - Coffee & Milk Tea</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons for social/phone logos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts: Poppins for modern look -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600&display=swap" rel="stylesheet">
  <!-- Your custom CSS -->
  <link rel="stylesheet" href="assets/style.css">
  <!-- Leaflet CSS for interactive map picker -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
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
    </div>
  </section>

  <!-- Contact Section -->
  <section class="section" id="contact" style="background-image: url('assets/bg10.jpg');">
    <div class="section-overlay"></div>
    <div class="section-content">
      <h2 class="section-title">Contact Us</h2>
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
      <form method="POST" action="../send_contact.php" novalidate>
        <div class="row">
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" name="name" placeholder="Your Name" maxlength="100" value="<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <input type="email" class="form-control" name="email" placeholder="Your Email" maxlength="150" value="<?= htmlspecialchars($_SESSION['customer_email'] ?? '') ?>" required>
          </div>
        </div>
        <textarea class="form-control mb-3" name="message" rows="3" placeholder="Your Message" maxlength="1000" required></textarea>
        <!-- Honeypot field to reduce spam -->
  <input type="hidden" name="website" value="">
        <!-- Tell handler to return to users page -->
        <input type="hidden" name="return_to" value="users/index.php">
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
<?php 
  $totalOrders = 0; $pendingOrders = 0; 
  if (isset($orders_by_status) && is_array($orders_by_status)) {
      $totalOrders = array_sum(array_map('count', $orders_by_status));
      $pendingOrders = (int)(($orders_by_status['To Ship'] ?? []) ? count($orders_by_status['To Ship']) : 0) + (int)(($orders_by_status['To Receive'] ?? []) ? count($orders_by_status['To Receive']) : 0);
  }
?>
<!-- Quick Orders Status Floating Button (badge matches cart badge) -->
<a href="#" id="ordersFab" class="cart-fab position-fixed" title="My Orders" aria-label="My Orders" data-bs-toggle="modal" data-bs-target="#myOrdersModal" style="bottom:108px;right:32px;display:flex;align-items:center;justify-content:center;">
  <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" class="bi bi-list-check" viewBox="0 0 16 16" style="display:block;">
    <path fill-rule="evenodd" d="M10.854 6.146a.5.5 0 0 0-.708.708L11.293 8l-.647.646a.5.5 0 0 0 .708.708l.646-.647.647.647a.5.5 0 0 0 .708-.708L12.707 8l.647-.646a.5.5 0 0 0-.708-.708L12 7.293l-.646-.647z"/>
    <path d="M4.5 5.5a.5.5 0 0 0 0 1H9a.5.5 0 0 0 0-1H4.5zm0 3a.5.5 0 0 0 0 1H9a.5.5 0 0 0 0-1H4.5zm0 3a.5.5 0 0 0 0 1H9a.5.5 0 0 0 0-1H4.5z"/>
    <path d="M2.5 6.5A.5.5 0 1 1 2.5 7a.5.5 0 0 1 0-1zm0 3A.5.5 0 1 1 2.5 10a.5.5 0 0 1 0-1zm0 3A.5.5 0 1 1 2.5 13a.5.5 0 0 1 0-1z"/>
  </svg>
  <span id="orders-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:<?= $pendingOrders>0?'inline-block':'none';?>;">
    <?= (int)$pendingOrders ?>
  </span>
  <span class="visually-hidden">Open My Orders (<?= $totalOrders ?> total)</span>
</a>

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
              <input type="text" class="form-control" name="street" placeholder="Street" value="<?= isset($savedAddress['Street']) ? htmlspecialchars($savedAddress['Street']) : '' ?>">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="barangay" placeholder="Barangay" value="<?= isset($savedAddress['Barangay']) ? htmlspecialchars($savedAddress['Barangay']) : '' ?>">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="city" placeholder="City" value="<?= isset($savedAddress['City']) ? htmlspecialchars($savedAddress['City']) : '' ?>">
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" name="contact" placeholder="Contact Number" value="<?= isset($savedAddress['Contact_Number']) ? htmlspecialchars($savedAddress['Contact_Number']) : '' ?>">
            </div>
            <div class="mb-3">
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-soft-orange btn-sm" type="button" id="useMyLocationBtn">Use my current location</button>
                <span id="distanceInfo" class="small text-muted" aria-live="polite"></span>
              </div>
              <input type="hidden" name="lat" id="latInput">
              <input type="hidden" name="lng" id="lngInput">
            </div>
            <div class="mb-2 d-flex align-items-center gap-2 flex-wrap">
              <button class="btn btn-outline-secondary btn-sm" type="button" id="findOnMapBtn">Find address on map</button>
              <span class="small text-muted">Move the map so the pin is centered on your delivery spot, then Confirm.</span>
              <button class="btn btn-soft-orange btn-sm ms-auto" type="button" id="confirmPinBtn">Confirm Pin Location</button>
            </div>
            <div class="mb-3" id="mapPickerWrap" style="height:260px; border-radius:12px; overflow:hidden; display:none;">
              <div id="mapPicker" style="height:100%; width:100%;"></div>
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
              <label class="form-check-label" for="gcash">GCash (upload receipt)</label>
            </div>
            <div id="gcashFields" class="mt-2" style="display:none;">
              <div class="alert alert-info mb-2" role="status" aria-live="polite" style="font-size:0.95rem;">
                Transfer payment to <strong>09940780881</strong>, then upload your receipt image and enter the GCash Reference Number. Your order will be processed after admin verification.
              </div>
              <div class="mb-2">
                <label class="form-label">GCash Reference Number</label>
                <input type="text" class="form-control" id="gcashRef" placeholder="e.g. 1234 5678 9012">
              </div>
              <div class="mb-2">
                <label class="form-label">Amount Paid (₱)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="gcashAmt" placeholder="0.00">
              </div>
              <div class="mb-2">
                <label class="form-label">Upload Receipt Image</label>
                <input type="file" accept="image/*" class="form-control" id="gcashFile">
              </div>
            </div>
          </div>
          <!-- Debug QR: show a scannable GCash QR at checkout -->
          <div class="mb-3" id="qrDebugBlock">
            <div class="card" style="border-radius:12px;">
              <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="small text-muted">Scan to Pay (GCash) — Debug</span>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleQrBtn">Hide</button>
                </div>
                <div id="qrWrap">
                  <img id="gcashQrImg" src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=09940780881" alt="GCash QR Code for 09940780881" style="width:240px;height:240px;image-rendering:pixelated;border-radius:8px;border:1px solid #eee;"/>
                </div>
                <div class="mt-2 small">
                  GCash number: <strong id="gcashNumber">09940780881</strong>
                  <button type="button" class="btn btn-sm btn-soft-orange ms-2" id="copyGcashBtn">Copy</button>
                </div>
                <div class="form-text mt-1">For testing only — showing QR does not change your selected payment method.</div>
              </div>
            </div>
          </div>
          <!-- Order Summary -->
          <div id="orderSummary" class="card" style="border-radius:12px;">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between"><span>Subtotal</span><span id="summarySubtotal">₱0.00</span></div>
              <div class="d-flex justify-content-between"><span>Delivery Fee</span><span id="summaryDelivery">₱0.00</span></div>
              <hr class="my-2">
              <div class="d-flex justify-content-between fw-semibold"><span>Total</span><span id="summaryTotal">₱0.00</span></div>
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
          <style>
            /* Stabilize My Orders modal height to prevent jump when switching filters */
            #myOrdersModal .orders-scroll-region{max-height:55vh;min-height:300px;overflow-y:auto;overscroll-behavior:contain;padding-right:4px;}
            #myOrdersModal .orders-scroll-region::-webkit-scrollbar{width:8px;}
            #myOrdersModal .orders-scroll-region::-webkit-scrollbar-track{background:rgba(0,0,0,0.05);border-radius:4px;}
            #myOrdersModal .orders-scroll-region::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.25);border-radius:4px;}
            @media (max-height:620px){#myOrdersModal .orders-scroll-region{max-height:48vh;}}
          </style>
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
          <div class="orders-scroll-region">
            <div id="ordersList"></div>
          </div>
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
          <!-- Footer total mirrors dynamic modal total; separate id to avoid duplicate -->
          <div class="fw-semibold">Total: <span id="productWithAddonsFooterTotal">₱0.00</span></div>
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
  <!-- Leaflet JS for map picker -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
    // QR (debug) helpers
    (function(){
      const copyBtn = document.getElementById('copyGcashBtn');
      const numEl = document.getElementById('gcashNumber');
      const toggleBtn = document.getElementById('toggleQrBtn');
      const wrap = document.getElementById('qrWrap');
      if(copyBtn && numEl){
        copyBtn.addEventListener('click', async ()=>{
          try { await navigator.clipboard.writeText(numEl.textContent.trim());
            // lightweight toast via SweetAlert2
            if(window.Swal){ Swal.fire({toast:true, position:'top-end', timer:1200, showConfirmButton:false, icon:'success', title:'Copied'}); }
          } catch(e){}
        });
      }
      if(toggleBtn && wrap){
        toggleBtn.addEventListener('click', ()=>{
          const hidden = wrap.style.display === 'none';
          wrap.style.display = hidden ? 'block' : 'none';
          toggleBtn.textContent = hidden ? 'Hide' : 'Show';
        });
      }
    })();
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
const ordersBadge = document.getElementById('orders-badge');
const cartItemsList = document.getElementById('cart-items-list');

// Badge: count all active (not yet completed) orders excluding Delivered & Cancelled
function derivePendingOrders(list){
  if(!Array.isArray(list)) return 0;
  let c=0; for(const o of list){
    const st=(o.order_status||o.ui_status||'').trim().toLowerCase();
  if(st && st !== 'delivered' && st !== 'cancelled' && st !== 'received') c++;
  }
  return c;
}

function updateOrdersBadgeFromCache(){
  if(!ordersBadge) return;
  const pending = derivePendingOrders(window.ORDERS_CACHE||[]);
  ordersBadge.textContent = pending;
  ordersBadge.style.display = pending>0 ? 'inline-block' : 'none';
}
window.updateOrdersBadgeFromCache = updateOrdersBadgeFromCache;

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
    const unit = item.unitPrice != null ? Number(item.unitPrice) : 0;
    let addonsTotal = 0;
    const addonsHtml = (item.addons && item.addons.length)
      ? `<div class=\"ms-2 small text-muted\">${item.addons.map(a=>{ addonsTotal += Number(a.price||0)*Number(a.qty||1); return `${a.name} x${a.qty} (+₱${money(a.price)})`; }).join(', ')}</div>`
      : '';
    const line = (unit * item.qty) + addonsTotal;
    return `
    <div class=\"d-flex align-items-center justify-content-between border-bottom py-2\">
      <div>
        <strong>${item.name}</strong> ${item.size ? `<span class='badge bg-info text-dark ms-1'>${item.size}</span>`:''}
        ${addonsHtml}
        ${item.instruction ? `<div class=\"small fst-italic text-muted ms-2\">${item.instruction}</div>`:''}
      </div>
      <div class=\"d-flex flex-column align-items-end gap-1\">
        <span class=\"badge bg-secondary\">x${item.qty}</span>
        <div class=\"fw-semibold\">₱${money(line)}</div>
        <button class=\"remove-cart-item btn btn-sm btn-outline-danger mt-1\" data-idx=\"${idx}\" title=\"Remove item\" aria-label=\"Remove item\">
          <i class=\"bi bi-trash\"></i>
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
  // Refresh orders badge via AJAX without reloading the entire page
  if (typeof refreshOrdersAjax === 'function') {
    refreshOrdersAjax({ open:false });
  } else {
    // Fallback to lightweight endpoint if helper not yet defined
    fetch('ajax/refresh_new_order.php?t=' + Date.now())
      .then(r=>r.ok?r.json():null)
      .then(j=>{
        if(j && j.success && Array.isArray(j.orders)) {
          window.ORDERS_CACHE = j.orders;
          if (typeof updateOrdersBadgeFromCache === 'function') updateOrdersBadgeFromCache();
        }
      })
      .catch(()=>{});
  }
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
const STORE_COORDS = { lat: 13.929589, lng: 121.09449 }; // PJ LIZA STORE (store location)
const summary = {
  subtotalEl: document.getElementById('summarySubtotal'),
  deliveryEl: document.getElementById('summaryDelivery'),
  totalEl: document.getElementById('summaryTotal'),
  distanceInfo: document.getElementById('distanceInfo'),
  latInput: document.getElementById('latInput'),
  lngInput: document.getElementById('lngInput')
};

// Saved delivery address injected from PHP (empty object if none)
const SAVED_ADDRESS = <?php echo json_encode([
  'street' => $savedAddress['Street'] ?? '',
  'barangay' => $savedAddress['Barangay'] ?? '',
  'city' => $savedAddress['City'] ?? '',
  'contact' => $savedAddress['Contact_Number'] ?? ''
]); ?>;

function autofillDeliveryIfEmpty(){
  if (!deliveryFields) return;
  const streetEl = paymentForm.querySelector('input[name="street"]');
  const barangayEl = paymentForm.querySelector('input[name="barangay"]');
  const cityEl = paymentForm.querySelector('input[name="city"]');
  const contactEl = paymentForm.querySelector('input[name="contact"]');
  if (streetEl && !streetEl.value && SAVED_ADDRESS.street) streetEl.value = SAVED_ADDRESS.street;
  if (barangayEl && !barangayEl.value && SAVED_ADDRESS.barangay) barangayEl.value = SAVED_ADDRESS.barangay;
  if (cityEl && !cityEl.value && SAVED_ADDRESS.city) cityEl.value = SAVED_ADDRESS.city;
  if (contactEl && !contactEl.value && SAVED_ADDRESS.contact) contactEl.value = SAVED_ADDRESS.contact;
}

// When selecting Delivery radio, auto fill blanks
document.querySelectorAll('input[name="orderType"]').forEach(r => {
  r.addEventListener('change', () => {
    const v = document.querySelector('input[name="orderType"]:checked')?.value;
    if (v === 'Delivery') {
      deliveryFields.style.display = '';
      autofillDeliveryIfEmpty();
    }
  });
});

function moneyPhp(n){ return '₱' + (Number(n||0).toFixed(2)); }

function getProductPriceByName(name){
  try{
    const allProducts = <?php echo json_encode($all_products); ?>;
    const p = (allProducts||[]).find(pp => pp.Product_Name === name);
    return Number(p?.Price_Amount || 0);
  }catch(e){ return 0; }
}

function computeCartSubtotal(){
  // Sum using size-specific unitPrice when available; fallback to legacy base lookup
  return cart.reduce((sum, item)=>{
    const unit = (item.unitPrice != null) ? Number(item.unitPrice) : getProductPriceByName(item.name);
    const baseLine = unit * (item.qty||1);
    const addons = (item.addons||[]).reduce((s,a)=> s + (Number(a.price)||0) * (a.qty||1), 0);
    return sum + baseLine + addons;
  }, 0);
}

function haversineKm(lat1, lon1, lat2, lon2){
  function toRad(v){ return v * Math.PI / 180; }
  const R = 6371; // km
  const dLat = toRad(lat2-lat1);
  const dLon = toRad(lon2-lon1);
  const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

function computeDeliveryFee(distanceKm){
  if (!isFinite(distanceKm) || distanceKm <= 0) return 0;
  // Tiered pricing: <=2km ₱29, <=5km ₱49, <=8km ₱69, <=12km ₱89, >12km => ₱99 + ₱8/km (ceil) beyond 12
  if (distanceKm <= 2) return 29;
  if (distanceKm <= 5) return 49;
  if (distanceKm <= 8) return 69;
  if (distanceKm <= 12) return 89;
  const extra = Math.max(0, Math.ceil(distanceKm - 12));
  return 99 + (8 * extra);
}

// Rewritten (previous version malformed causing syntax error)
function updateOrderSummary(){
  try {
    const subtotal = computeCartSubtotal();
    const orderType = document.querySelector('input[name="orderType"]:checked')?.value || 'Pick Up';
    let fee = 0;
    if (orderType === 'Delivery') {
      // Attempt distance-based fee only if we have coords
      const latVal = parseFloat(summary.latInput?.value || '');
      const lngVal = parseFloat(summary.lngInput?.value || '');
      if (isFinite(latVal) && isFinite(lngVal)) {
        const distKm = haversineKm(STORE_COORDS.lat, STORE_COORDS.lng, latVal, lngVal);
        fee = computeDeliveryFee(distKm);
        if(summary.distanceInfo){
          summary.distanceInfo.textContent = `Distance: ${distKm.toFixed(2)} km • Delivery Fee: ₱${fee.toFixed(2)}`;
        }
      } else {
        if(summary.distanceInfo){ summary.distanceInfo.textContent = 'Tip: Use your location or pin the map to estimate the delivery fee.'; }
      }
    } else {
      if(summary.distanceInfo){ summary.distanceInfo.textContent = ''; }
    }
    if(summary.subtotalEl) summary.subtotalEl.textContent = moneyPhp(subtotal);
    if(summary.deliveryEl) summary.deliveryEl.textContent = moneyPhp(fee);
    if(summary.totalEl) summary.totalEl.textContent = moneyPhp(subtotal + fee);
  } catch(err){
    console.warn('updateOrderSummary failed', err);
  }
}

// Show/hide delivery fields based on order type
document.querySelectorAll('input[name="orderType"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('deliveryFields').style.display =
      this.value === 'Delivery' ? 'block' : 'none';
  updateOrderSummary();
    if (this.value === 'Delivery') {
      try { ensureMapPicker(); } catch (e) {}
    }
  });
});

// Show/hide payment fields based on payment method
document.querySelectorAll('input[name="paymentMethod"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    const gf = document.getElementById('gcashFields');
    if (gf) gf.style.display = this.value === 'GCash' ? 'block' : 'none';
    const cf = document.getElementById('creditFields');
    if (cf) {
      cf.style.display = this.value === 'Credit Card' ? 'block' : 'none';
      if (this.value === 'Credit Card') {
        const gen = document.getElementById('generatedCardNumber');
        if (gen) gen.textContent = generateCreditCardNumber();
      }
    }
    updateOrderSummary();
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

// Geolocation button
const useMyLocationBtn = document.getElementById('useMyLocationBtn');
if (useMyLocationBtn){
  useMyLocationBtn.addEventListener('click', function(){
    if (!navigator.geolocation){
      Swal.fire({icon:'info', title:'Geolocation not supported', text:'Your browser does not support location access.', confirmButtonColor:'#FFB27A'});
      return;
    }
    useMyLocationBtn.disabled = true;
    useMyLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Locating...';
    navigator.geolocation.getCurrentPosition(function(pos){
      const { latitude, longitude } = pos.coords;
      if(summary.latInput) summary.latInput.value = String(latitude);
      if(summary.lngInput) summary.lngInput.value = String(longitude);
      updateOrderSummary();
        try{
          if (window.__mapMarker && window.__map){
            window.__mapMarker.setLatLng([latitude, longitude]);
            window.__map.setView([latitude, longitude], 16);
          } else {
            ensureMapPicker();
          }
        }catch(e){}
      // Reverse geocode to fill street / barangay / city (phone left for user)
      reverseGeocodeAndFill(latitude, longitude);
      useMyLocationBtn.disabled = false;
      useMyLocationBtn.textContent = 'Use my current location';
    }, function(err){
      console.warn('Geolocation error', err);
      Swal.fire({icon:'warning', title:'Location blocked', text:'Please allow location access to estimate the fee.', confirmButtonColor:'#FFB27A'});
      useMyLocationBtn.disabled = false;
      useMyLocationBtn.textContent = 'Use my current location';
    }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
  });
}

// Reverse geocode helper (Nominatim)
async function reverseGeocodeAndFill(lat, lng){
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1&accept-language=en`; 
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Reverse geocode failed');
    const data = await res.json();
    const addr = data.address || {};
    // Heuristics tailored for PH addressing order:
    // street field: [house/building/block/lot] + road + (subdivision)
    // barangay field: barangay only
    // city field: city / municipality only
    const housePart = addr.house_number || addr.building || '';
    // Some PH addresses encode block/lot in house_number; if display_name has 'Blk' or 'Lot' tokens early, attempt to capture
    let blockLot = '';
    if (!housePart && data.display_name){
      const m = data.display_name.match(/\b(Blk\.?\s*\d+|Block\s*\d+|Lot\s*\d+[A-Z]?)/i);
      if (m) blockLot = m[0];
    }
    const road = addr.road || addr.pedestrian || addr.footway || addr.residential || '';
    const subdivision = addr.residential || addr.neighbourhood || addr.quarter || addr.estate || '';
    // Barangay detection – Nominatim may map it to suburb or village; keep strictly barangay-level (avoid using neighbourhood already used as subdivision)
  let barangay = addr.barangay || addr.suburb || addr.village || addr.hamlet || '';
    // City / municipality
    const city = addr.city || addr.town || addr.municipality || addr.city_district || addr.county || '';
    // Compose street text
    const streetParts = [housePart || blockLot, road, subdivision && subdivision !== barangay ? subdivision : ''].filter(Boolean);
    const street = streetParts.join(', ');
    // Barangay refinement: if OSM gave a nearby/adjacent barangay (e.g., Pusil) but display_name contains a different known barangay (e.g., Inosloban), prefer the known token.
    const knownBarangays = [
      'Inosloban','Pusil','Sabang','San Jose','Santo Toribio','Banaybanay','Mataas na Lupa','Marawoy','Balintawak','Santo Niño','Dagatan','Antipolo del Norte','Antipolo del Sur','Calamias','Cumba','Sico','Sampaguita','Talisay','Lodlod','Pinagtongulan','Balagbag','Bulacnin','Kayumanggi','Latag','Mabini','Malagonlong','Malitlit','Pagolingin East','Pagolingin West','Pangao','Plaridel','Quezon','Rizal','San Benito','San Celestino','San Francisco','San Guillermo','San Isidro','San Lucas','San Salvador','Santa Cruz','Tambo','Tangob','Tipacan'
    ];
    const dn = (data.display_name || '').toLowerCase();
    const currentLower = barangay.toLowerCase();
    let bestMatch = currentLower ? barangay : '';
    for (const kb of knownBarangays){
      const kbLower = kb.toLowerCase();
      if (dn.includes(kbLower)) {
        // Prefer if current barangay empty OR current not in display name OR match length is longer / more specific
        if (!bestMatch || !dn.includes(currentLower) || kbLower.length > bestMatch.toLowerCase().length) {
          bestMatch = kb;
        }
      }
    }
    if (bestMatch && bestMatch.toLowerCase() !== currentLower) {
      barangay = bestMatch; // override with refined match
    }

    const form = document.getElementById('paymentForm');
    if (form){
      if (form.street) form.street.value = street;
      if (form.barangay) form.barangay.value = barangay;
      if (form.city) form.city.value = city;
    }
  } catch(err){
    console.warn('reverseGeocodeAndFill error', err);
  }
}

// Helper: geocode current typed address (street/barangay/city) and set lat/lng
async function geocodeTypedAddressAndSet(){
  const form = document.getElementById('paymentForm');
  const street = form.street?.value?.trim() || '';
  const barangay = form.barangay?.value?.trim() || '';
  const cityRaw = form.city?.value?.trim() || '';
  // Parse cityRaw like "Lipa City, Batangas, Philippines"
  const parts = cityRaw.split(',').map(s=>s.trim()).filter(Boolean);
  const city = parts[0] || cityRaw || '';
  // heuristics
  const county = (parts.find(p=>/batangas/i.test(p)) || '').replace(/\s+/g,' ').trim();
  const country = 'Philippines';

  async function nominatimJson(url){
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return [];
    return res.json();
  }

  const queries = [];
  // 1) Structured search with street+barangay+city
  if (street && city){
    const u = new URL('https://nominatim.openstreetmap.org/search');
    u.searchParams.set('format','json');
    u.searchParams.set('limit','1');
    u.searchParams.set('countrycodes','ph');
    u.searchParams.set('street', `${street}${barangay?`, ${barangay}`:''}`);
    u.searchParams.set('city', city);
    if (county) u.searchParams.set('county', county);
    u.searchParams.set('country', country);
    queries.push(u.toString());
  }
  // 2) Unstructured full
  const full1 = [street, barangay, cityRaw || city, 'Philippines'].filter(Boolean).join(', ');
  if (full1){
    const u = new URL('https://nominatim.openstreetmap.org/search');
    u.searchParams.set('format','json');
    u.searchParams.set('limit','1');
    u.searchParams.set('countrycodes','ph');
    u.searchParams.set('q', full1);
    queries.push(u.toString());
  }
  // 3) Street + City + Batangas + PH
  const full2 = [street, city || cityRaw, county || 'Batangas', country].filter(Boolean).join(', ');
  const u2 = new URL('https://nominatim.openstreetmap.org/search');
  u2.searchParams.set('format','json');
  u2.searchParams.set('limit','1');
  u2.searchParams.set('countrycodes','ph');
  u2.searchParams.set('q', full2);
  queries.push(u2.toString());
  // 4) Barangay + City + Batangas + PH (fallback without street)
  if (barangay || city){
    const full3 = [barangay, city || cityRaw, county || 'Batangas', country].filter(Boolean).join(', ');
    const u3 = new URL('https://nominatim.openstreetmap.org/search');
    u3.searchParams.set('format','json');
    u3.searchParams.set('limit','1');
    u3.searchParams.set('countrycodes','ph');
    u3.searchParams.set('q', full3);
    queries.push(u3.toString());
  }

  let best = null;
  for (const q of queries){
    try{
      const list = await nominatimJson(q);
      if (Array.isArray(list) && list.length){ best = list[0]; break; }
    }catch(e){ /* continue */ }
    // be polite to the public API
    await new Promise(r=>setTimeout(r, 400));
  }

  // Last resort: city center
  if (!best && (city || cityRaw)){
    const u = new URL('https://nominatim.openstreetmap.org/search');
    u.searchParams.set('format','json');
    u.searchParams.set('limit','1');
    u.searchParams.set('countrycodes','ph');
    u.searchParams.set('q', `${city || cityRaw}, ${county || 'Batangas'}, ${country}`);
    const list = await nominatimJson(u.toString());
    if (Array.isArray(list) && list.length) best = list[0];
  }

  if (!best) return null;
  const lat = parseFloat(best.lat), lng = parseFloat(best.lon);
  if(summary.latInput) summary.latInput.value = String(lat);
  if(summary.lngInput) summary.lngInput.value = String(lng);
  try{
    if (window.__mapMarker && window.__map){
      window.__mapMarker.setLatLng([lat, lng]);
      window.__map.setView([lat, lng], 16);
    } else {
      ensureMapPicker();
    }
  }catch(e){}
  updateOrderSummary();
  return { lat, lng };
}

// Debounced auto-geocode when typing address
let _geoTimer = null;
let _lastGeoQuery = '';
function debounceGeocode(){
  const form = document.getElementById('paymentForm');
  if (!form) return;
  const orderType = document.querySelector('input[name="orderType"]:checked')?.value || 'Pick Up';
  if (orderType !== 'Delivery') return;
  const street = form.street?.value?.trim() || '';
  const barangay = form.barangay?.value?.trim() || '';
  const city = form.city?.value?.trim() || '';
  const full = [street, barangay, city, 'Philippines'].filter(Boolean).join(', ');
  if (!full) return;
  if (full === _lastGeoQuery) return; // avoid re-querying same
  clearTimeout(_geoTimer);
  _geoTimer = setTimeout(async ()=>{
    const pos = await geocodeTypedAddressAndSet().catch(()=>null);
    if (pos) _lastGeoQuery = full;
  }, 800);
}

['street','barangay','city'].forEach(name => {
  const el = document.querySelector(`#paymentForm [name="${name}"]`);
  if (el){ el.addEventListener('input', debounceGeocode); }
});

// Geocode typed address and show map preview
const findOnMapBtn = document.getElementById('findOnMapBtn');
if (findOnMapBtn){
  findOnMapBtn.addEventListener('click', async function(){
    const form = document.getElementById('paymentForm');
    const street = form.street?.value?.trim() || '';
    const barangay = form.barangay?.value?.trim() || '';
    const city = form.city?.value?.trim() || '';
    const full = [street, barangay, city, 'Philippines'].filter(Boolean).join(', ');
    if (!full){
      Swal.fire({icon:'info', title:'Type your address', text:'Fill Street/Barangay/City first.', confirmButtonColor:'#FFB27A'});
      return;
    }
    findOnMapBtn.disabled = true;
    findOnMapBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Finding...';
    try{
      const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(full);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const list = await res.json();
      const best = Array.isArray(list) && list.length ? list[0] : null;
      if (!best){
        Swal.fire({icon:'warning', title:'Address not found', text:'Try refining your address.', confirmButtonColor:'#FFB27A'});
        return;
      }
      const lat = parseFloat(best.lat), lng = parseFloat(best.lon);
      if(summary.latInput) summary.latInput.value = String(lat);
      if(summary.lngInput) summary.lngInput.value = String(lng);
      updateOrderSummary();
      try{
        if (window.__mapMarker && window.__map){
          window.__mapMarker.setLatLng([lat, lng]);
          window.__map.setView([lat, lng], 16);
        } else {
          ensureMapPicker();
        }
      }catch(e){}
    }catch(err){
      console.warn('Geocode failed', err);
      Swal.fire({icon:'error', title:'Geocoding error', text:'Unable to locate that address.', confirmButtonColor:'#FFB27A'});
    } finally {
      findOnMapBtn.disabled = false;
      findOnMapBtn.textContent = 'Find address on map';
    }
  });
}

// Lazy-load Leaflet if needed (fallback to jsDelivr) then run callback
function loadLeafletIfNeeded(onReady){
  if (window.L) { onReady && onReady(); return; }
  if (window.__leafletLoading) { // wait and retry soon
    return setTimeout(() => loadLeafletIfNeeded(onReady), 300);
  }
  window.__leafletLoading = true;
  // inject CSS if missing
  if (!document.querySelector('link[href*="leaflet.css"]')){
    const l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(l);
  }
  // inject JS
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
  s.async = true;
  s.onload = function(){ window.__leafletLoading = false; onReady && onReady(); };
  s.onerror = function(){ window.__leafletLoading = false; console.warn('Leaflet failed to load'); };
  document.head.appendChild(s);
}

// Leaflet map picker setup
function ensureMapPicker(){
  const wrap = document.getElementById('mapPickerWrap');
  if (!wrap) return;
  wrap.style.display = 'block';
  // Ensure Leaflet is loaded before creating the map
  if (!window.L){
    loadLeafletIfNeeded(() => {
      // Try again after Leaflet becomes available
      ensureMapPicker();
    });
    return;
  }
  if (!window.__map){
    const mapEl = document.getElementById('mapPicker');
    if (!mapEl) return;
    const latVal = parseFloat(summary.latInput?.value);
    const lngVal = parseFloat(summary.lngInput?.value);
    const startPos = (isFinite(latVal) && isFinite(lngVal)) ? [latVal, lngVal] : [STORE_COORDS.lat, STORE_COORDS.lng];
  const map = L.map(mapEl).setView(startPos, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    // Non-draggable marker that always stays at the map center
    const marker = L.marker(startPos).addTo(map);
    // Keep marker at center as the user pans/zooms the map
    map.on('move', function(){
      try{ marker.setLatLng(map.getCenter()); }catch(e){}
    });
    window.__map = map;
    window.__mapMarker = marker;
    // Invalidate after a tick to ensure correct sizing when inside modal
    setTimeout(() => { try { map.invalidateSize(); } catch(e){} }, 300);
  }
}

// Confirm pin button ensures we have lat/lng and updates summary
const confirmPinBtn = document.getElementById('confirmPinBtn');
if (confirmPinBtn){
  confirmPinBtn.addEventListener('click', function(){
    if (!window.__map){ ensureMapPicker(); }
    const pos = window.__map?.getCenter?.();
    if (pos){
      if(summary.latInput) summary.latInput.value = String(pos.lat);
      if(summary.lngInput) summary.lngInput.value = String(pos.lng);
      updateOrderSummary();
  // Auto-fill address fields from pin (phone left for user)
  reverseGeocodeAndFill(pos.lat, pos.lng);
      Swal.fire({icon:'success', title:'Location set', timer:1000, showConfirmButton:false});
    } else {
      Swal.fire({icon:'info', title:'Drag the pin to your location first', confirmButtonColor:'#FFB27A'});
    }
  });
}

// Populate order summary when the checkout modal opens
if (paymentModalEl){
  // Use shown.bs.modal so the map container is visible before init
  paymentModalEl.addEventListener('shown.bs.modal', () => {
    try{ updateOrderSummary(); }catch(e){}
    try{

    // Accessibility: prevent aria-hidden on an ancestor while a focused element remains inside productDetailsModal
    (function(){
      const pdEl = document.getElementById('productDetailsModal');
      if(!pdEl) return;
      pdEl.addEventListener('hide.bs.modal', ()=>{
        try {
          const active = document.activeElement;
          if (active && pdEl.contains(active) && typeof active.blur === 'function') {
            active.blur();
          }
        } catch(e){}
      });
    })();
      const orderType = document.querySelector('input[name="orderType"]:checked')?.value || 'Pick Up';
      if (orderType === 'Delivery') ensureMapPicker();
    }catch(e){}
  });
}

// Handle payment form submission
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const orderType = document.querySelector('input[name="orderType"]:checked').value;
  const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
  let street = '', barangay = '', city = '', contact = '';
  // Always read current hidden inputs for lat/lng so Pickup can carry coords too
  let lat = this.lat?.value || '';
  let lng = this.lng?.value || '';
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
    // Fallback: if lat/lng missing, geocode typed address now
    if (!lat || !lng) {
      try{
        const pos = await geocodeTypedAddressAndSet();
        if (pos){ lat = String(pos.lat); lng = String(pos.lng); }
      }catch(err){ /* ignore; server will fallback */ }
    }
  }
  // Build payload including size information per cart item
  const payload = {
    orderType,
    paymentMethod,
    street, barangay, city, contact,
    lat, lng,
    cart: cart.map(it=>({
      name: it.name,
      qty: it.qty,
      addons: it.addons||[],
      instruction: it.instruction||'',
      size: it.size || '16oz'
    }))
  };
  // If GCash selected, ensure local fields are provided (reference, amount, file)
  if (paymentMethod === 'GCash') {
    const ref = document.getElementById('gcashRef')?.value?.trim();
    const amt = parseFloat(document.getElementById('gcashAmt')?.value || '0');
    const file = document.getElementById('gcashFile')?.files?.[0];
    if (!ref || !(amt > 0) || !file) {
      Swal.fire({icon:'warning', title:'GCash details required', text:'Enter the reference number, amount, and upload the receipt image.', confirmButtonColor:'#FFB27A'});
      return;
    }
  }

  // Send data to PHP (non-GCash flows)
  const orderRes = await fetch('ajax/checkout_process.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      orderType,
      paymentMethod,
      street,
      barangay,
      city,
      contact,
      lat,
      lng,
      cart
    })
  });
  const rawResp = await orderRes.text();
  let data;
  try { data = JSON.parse(rawResp); } catch(e) {
    Swal.fire({icon:'error', title:'Order Failed', text:'Invalid server response: ' + rawResp, confirmButtonColor:'#FFB27A'});
    return;
  }
  // If GCash, immediately upload the receipt linked to new order
  if (data && data.success && paymentMethod === 'GCash') {
    try {
      const fd = new FormData();
      fd.append('order_id', String(data.order_id||''));
      fd.append('ref_number', document.getElementById('gcashRef').value.trim());
      fd.append('amount', document.getElementById('gcashAmt').value.trim());
      fd.append('receipt', document.getElementById('gcashFile').files[0]);
      const up = await fetch('ajax/upload_gcash_receipt.php', { method:'POST', body: fd });
      const j = await up.json();
      if (!j.success) {
        // Soft warning; order is created but receipt missing
        console.warn('Receipt upload failed', j);
        Swal.fire({icon:'warning', title:'Receipt upload failed', text:j.message||'Please retry uploading from My Orders.', confirmButtonColor:'#FFB27A'});
      }
    } catch (e) {
      console.warn('Receipt upload exception', e);
    }
  }
  // Continue normal success flow
  if (data.success) {
      const feeLine = (typeof data.delivery_fee !== 'undefined') ? `\nDelivery Fee: ${moneyPhp(data.delivery_fee)}${data.distance_km?` (Distance: ${Number(data.distance_km).toFixed(2)} km)`:''}` : '';
      Swal.fire({
        icon: 'success',
        title: 'Order Confirmed!',
        text: 'Your order has been placed successfully.' + feeLine,
        confirmButtonColor: '#FFB27A'
      }).then(() => {
        cart.length = 0;
        updateCartBadge();
        renderCartItems();
        const payM = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
        payM && payM.hide();
        // Optimistic badge update so user sees the new pending order instantly (before orders_api fetch completes)
        try {
          if (typeof ordersBadge !== 'undefined' && ordersBadge) {
            const currentPending = parseInt(ordersBadge.textContent || '0', 10) || 0;
            ordersBadge.textContent = currentPending + 1;
            ordersBadge.style.display = 'inline-block';
          }
        } catch(e) { /* ignore */ }
        // Prefetch latest orders via new grouped endpoint so My Orders modal shows new order
        fetch('ajax/fetch_orders.php?t=' + Date.now())
          .then(r=> r.ok ? r.json() : {success:false})
          .then(payload=>{ if(payload && payload.success){
              ORDERS_CACHE = Array.isArray(payload.flat) ? payload.flat : [];
              if(payload.counts && typeof payload.counts.pending==='number'){
                ordersBadge.textContent = payload.counts.pending;
                ordersBadge.style.display = payload.counts.pending>0? 'inline-block':'none';
              } else { updateOrdersBadgeFromCache(); }
          }})
          .catch(()=>{});
      });
  } else {
      Swal.fire({
        icon: 'error',
        title: 'Order Failed',
        text: data.message || 'There was a problem processing your order.',
        confirmButtonColor: '#FFB27A'
      });
  }
  return;
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

  // Allergen icons removed per latest requirement (no allergen display).

  function renderRecommendedCards(productsArr) {
    if (!recommendedCardsDiv) return;
    if (!Array.isArray(productsArr) || productsArr.length === 0) {
      recommendedWrap && recommendedWrap.classList.add('d-none');
      return;
    }
    // Cap to 4 suggestions
    const list = productsArr.slice(0, 4);
  recommendedCardsDiv.innerHTML = list.map(product => {
      const pid = product.Product_ID;
      const avgInfo = avgRatings[pid];
      const avgVal = avgInfo ? avgInfo.avg : '0.0';
      const countVal = avgInfo ? avgInfo.count : 0;
      const priceDisplay = product.Price_Amount ? `₱${parseFloat(product.Price_Amount).toFixed(2)}` : '₱0.00';
      return `
        <div class="menu-card" data-product-id="${pid}">
          <div class="menu-card-image">
            <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}">
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
      const pid = product.Product_ID;
      const avgInfo = avgRatings[pid];
      const avgVal = avgInfo ? avgInfo.avg : '0.0';
      const countVal = avgInfo ? avgInfo.count : 0;
      const priceDisplay = product.Price_Amount ? `₱${parseFloat(product.Price_Amount).toFixed(2)}` : '₱0.00';

      return `
        <div class="menu-card" data-product-id="${pid}">
          <div class="menu-card-image">
            <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}">
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

  // Initial render: show all products (or a helpful message if empty)
  try {
    console.info('All products loaded (count):', Array.isArray(allProducts)? allProducts.length : 'not an array');
    if(Array.isArray(allProducts) && allProducts.length){
      renderMenuCards(allProducts);
    } else {
      renderMenuCards([]); // will show 'No products found.' placeholder
    }
  } catch(e){ console.warn('Initial product render failed', e); }

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

// Progress steps: branch by order_type
const DELIVERY_STEPS = ["Pending","Processing","Ready to deliver","On the way","Delivered"];
const PICKUP_STEPS   = ["Pending","Processing","Ready to pick up","Received"];
const STATUS_LABEL_MAP = {
  Pending: "Pending",
  Processing: "Preparing",
  "Ready to deliver": "Ready to deliver",
  "On the way": "On the way",
  Delivered: "Delivered",
  "Ready to pick up": "Ready to pick up",
  Received: "Received",
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
function renderProgress(current, orderType) {
  const steps = (orderType === 'Pickup') ? PICKUP_STEPS : DELIVERY_STEPS;
  const idx = steps.indexOf(current);
  return `
    <div class="order-progress d-flex align-items-center mb-2">
      ${steps.map((s,i)=>{
        const state = i < idx ? 'completed' : (i === idx ? 'active' : 'upcoming');
        return `
          <div class="step ${state}">
            <div class="dot">${i < idx ? '✓' : ''}</div>
            <div class="label">${STATUS_LABEL_MAP[s]||s}</div>
          </div>
          ${i<steps.length-1?`<div class="bar ${i<idx?'filled':''}"></div>`:""}
        `;
      }).join('')}
    </div>`;
}

// ================== ORDER LIST / FILTER UI ==================
const RAW_STATUS_STEPS = ["Pending","Processing","Ready to deliver","On the way","Delivered","Ready to pick up","Received"];
const STATUS_DISPLAY = {
  All: "All",
  Pending:"Pending",
  Processing:"Processing",
  "Ready to deliver":"Ready to deliver",
  "On the way":"On the way",
  "Ready to pick up":"Ready to pick up",
  Received:"Received",
  Delivered:"Delivered",
  Cancelled:"Cancelled"
};
const CHIP_SEQUENCE = ["All","Pending","Processing","Ready to deliver","On the way","Ready to pick up","Received","Delivered","Cancelled"];

let ORDERS_CACHE = [];
let ACTIVE_STATUS = "All";
// Pagination state for My Orders modal
let ORDERS_PAGE = 1;
const ORDERS_PER_PAGE = 10; // 10 cards per page

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
  // Derive order type heuristically (fallback to Delivery if address-like fields exist)
  const type = (o.order_type || '').trim() || ((o.Street || o.City || o.Contact_Number) ? 'Delivery' : 'Pickup');
  let raw = (o.order_status || '').trim();
  const rawLower = raw.toLowerCase();
  const RAW_MAP = {
    'pending':'Pending',
    'processing':'Processing',
    'ready to deliver':'Ready to deliver',
    'on the way':'On the way',
    'delivered':'Delivered',
    'ready to pick up':'Ready to pick up',
    'ready for pickup':'Ready to pick up',
    'received':'Received',
    'cancelled':'Cancelled',
    'canceled':'Cancelled',
    'to ship':'Ready to deliver',
    'to receive':'On the way',
    'preparing':'Processing'
  };
  if (RAW_MAP[rawLower]) raw = RAW_MAP[rawLower];
  if(!raw && o.ToShipFlag) raw = 'Ready to deliver';
  if(!raw && o.ToReceiveFlag) raw = 'On the way';
  const driver = (o.Driver_Status || '').trim();

  // Normalize driver live states first (these override some backend textual states)
  if (driver === 'on_the_way' || driver === 'picked_up') {
    // If already delivered/received/cancelled don't override
    if (['Delivered','Received','Cancelled'].includes(raw)) {
      return raw === 'Received' ? 'Received' : raw; // Delivered or Cancelled as-is
    }
    return 'On the way';
  }

  // Canonical list of statuses we expect from admin/backend
  // Pending, Processing, Ready to deliver, On the way, Delivered, Ready to pick up, Received, Cancelled
  // Legacy / alias forms we translate:
  //   To Ship -> Ready to deliver
  //   To Receive -> On the way
  //   Preparing -> Processing (if ever used)

  // Map legacy/alias to canonical
  let canonical = raw; // legacy mapped already above

  // Pickup specific terminal status normalization
  if (/pickup/i.test(type) || type === 'Pickup') {
    if (canonical === 'Delivered') canonical = 'Received'; // unify delivered -> received for pickup flow
  }

  // Guard: if order got both Received and Delivered flags historically, prioritize Received
  if (canonical === 'Delivered' && raw === 'Received') canonical = 'Received';

  // Final allow-list; if not in allow-list fallback to Pending but log once (per status)
  const ALLOW = new Set(['Pending','Processing','Ready to deliver','On the way','Delivered','Ready to pick up','Received','Cancelled']);
  if (!ALLOW.has(canonical)) {
    if (!window.__UNKNOWN_ORDER_STATUS_LOG) window.__UNKNOWN_ORDER_STATUS_LOG = {};
    if (!window.__UNKNOWN_ORDER_STATUS_LOG[canonical]) {
      console.warn('[orders] Unmapped status encountered ->', canonical, 'raw=', raw, 'type=', type, 'order_id=', o.Order_ID);
      window.__UNKNOWN_ORDER_STATUS_LOG[canonical] = true;
    }
    return 'Pending';
  }
  return canonical;
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
  ORDERS_PAGE = 1; // reset to first page on filter change
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

  const totalFiltered = processed.length;
  const totalPages = Math.max(1, Math.ceil(totalFiltered / ORDERS_PER_PAGE));
  if (ORDERS_PAGE > totalPages) ORDERS_PAGE = totalPages; // clamp
  const startIdx = (ORDERS_PAGE - 1) * ORDERS_PER_PAGE;
  const pageSlice = processed.slice(startIdx, startIdx + ORDERS_PER_PAGE);

  const summary = `${totalFiltered} order${totalFiltered!==1?'s':''} found • Page ${ORDERS_PAGE} of ${totalPages}`;
  const summaryEl = document.getElementById('ordersSummaryLine');
  if (summaryEl) summaryEl.textContent = summary;

  if(!totalFiltered){
    listEl.innerHTML = `<div class="text-muted py-4 text-center">No matching orders.</div>`;
    return;
  }
  listEl.innerHTML = pageSlice.map(o => {
  const uiStatus = o.ui_status;
  const badgeClass = (uiStatus==="Delivered"?"bg-success":
             uiStatus==="Ready to deliver"?"bg-primary":
             uiStatus==="On the way"?"bg-primary":
             uiStatus==="To Receive"||uiStatus==="Ready to pick up"||uiStatus==="Received"?"bg-warning text-dark":
             uiStatus==="Processing"?"bg-info text-dark":
             uiStatus==="Pending"?"bg-secondary":
             uiStatus==="Cancelled"?"bg-dark":"bg-secondary");
    const itemsPreview = o.items.slice(0,3).map(it=>`
      <div class="d-inline-flex align-items-center me-2 mb-1" style="font-size:.75rem;">
        <img src="../admin/uploads/products/${it.Product_Image}" style="width:34px;height:34px;object-fit:cover;border-radius:8px;margin-right:4px;">
        <span>${it.Product_Name} x ${it.Quantity}</span>
      </div>`).join('') + (o.items.length>3? `<span class="text-muted small">+${o.items.length-3} more</span>`:'');
    /*const isDelivery = (o.order_type||'').toLowerCase().includes('deliver') || (!!o.Street || !!o.City || !!o.Contact_Number);
    const needsTracking = isDelivery && !['Delivered','Received','Cancelled'].includes(uiStatus);
    const trackingHtml = needsTracking ? `
      <div class="mt-3 p-2 border rounded bg-white" style="border-radius:12px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="small">Delivery status: <span id="track-status-${o.Order_ID}" class="fw-semibold">Loading…</span></div>
          <button type="button" class="btn btn-outline-soft-orange btn-sm" data-track="${o.Order_ID}">Track</button>
        </div>
        <div id="track-map-${o.Order_ID}" class="mt-2" style="height:160px;border-radius:10px;overflow:hidden;display:none;"></div>
      </div>` : '';*/
    // Delivery tracking temporarily disabled
    const trackingHtml = '';

    return `
      <div class="card mb-2" data-order-id="${o.Order_ID}" style="border-radius:16px;">
        <div class="card-body">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
              <strong>Order #${o.Order_ID}</strong> • ${o.Order_Date}
              <div class="mt-1">${renderProgress(uiStatus, o.order_type)}</div>
            </div>
            <span class="badge ${badgeClass} order-status-badge" data-status="${uiStatus}" style="height:fit-content;">${uiStatus}</span>
          </div>
          <div class="mt-2">${itemsPreview}</div>
          <div class="mt-3 d-flex flex-wrap gap-2">
            ${actionButtons(uiStatus,o.Order_ID)}
          </div>
          <div class="mt-2 fw-semibold">Total: ₱${parseFloat(o.Order_Amount).toFixed(2)}</div>
          ${trackingHtml}
        </div>
      </div>`;
  }).join('');

  renderOrdersPagination(totalPages);
}

// Render pagination controls (Previous / numbered / Next) inside My Orders modal footer or below list
function renderOrdersPagination(totalPages){
  let pagEl = document.getElementById('ordersPagination');
  if(!pagEl){
    const listEl = document.getElementById('ordersList');
    if(!listEl) return;
    pagEl = document.createElement('div');
    pagEl.id = 'ordersPagination';
    pagEl.className = 'mt-2';
    listEl.after(pagEl);
  }
  if(totalPages <= 1){ pagEl.innerHTML = ''; return; }
  let html = '<nav aria-label="Orders pages"><ul class="pagination pagination-sm justify-content-end mb-0">';
  const disabledPrev = ORDERS_PAGE === 1 ? ' disabled' : '';
  html += `<li class="page-item${disabledPrev}"><a class="page-link" data-page="prev" href="#">Previous</a></li>`;
  for(let i=1;i<=totalPages;i++){
    const active = i===ORDERS_PAGE ? ' active' : '';
    html += `<li class="page-item${active}"><a class="page-link" data-page="${i}" href="#">${i}</a></li>`;
  }
  const disabledNext = ORDERS_PAGE === totalPages ? ' disabled' : '';
  html += `<li class="page-item${disabledNext}"><a class="page-link" data-page="next" href="#">Next</a></li>`;
  html += '</ul></nav>';
  pagEl.innerHTML = html;
  pagEl.querySelectorAll('.page-link').forEach(a=>{
    a.addEventListener('click', e=>{
      e.preventDefault();
      const val = a.getAttribute('data-page');
      if(val==='prev' && ORDERS_PAGE>1){ ORDERS_PAGE--; }
      else if(val==='next' && ORDERS_PAGE < totalPages){ ORDERS_PAGE++; }
      else if(/^[0-9]+$/.test(val)){
        const num = parseInt(val,10); if(num!==ORDERS_PAGE){ ORDERS_PAGE = num; }
      }
      renderOrders();
      try{ document.querySelector('#myOrdersModal .modal-body')?.scrollTo({top:0,behavior:'smooth'}); }catch(_){ }
    });
  });
}

function actionButtons(status,id){
  // Normalize status passed in (already canonical via deriveUiStatus)
  const order = ORDERS_CACHE.find(o => String(o.Order_ID) === String(id));
  const allReviewed = order?.items?.length ? order.items.every(it => !!it.Already_Reviewed) : false;
  const type = (order?.order_type||'').toLowerCase();
  const isPickup = type.includes('pick');
  const isDelivery = type.includes('deliver') || (!isPickup && (order?.Street || order?.City || order?.Contact_Number));

  // Decide if confirm button should show
  // Backend allows: (Pickup) Pending, Processing, Ready to pick up -> Received
  //                  (Delivery) Pending, Processing, Ready to deliver, On the way -> Delivered
  // For better UX only show once order is beyond Pending (except allow manual early confirm if desired?)
  let showConfirm = false;
  if(isPickup){
    showConfirm = ['Ready to pick up','Processing','Pending'].includes(status) && !['Received','Cancelled'].includes(status);
  } else if(isDelivery){
    showConfirm = ['Ready to deliver','On the way','Processing','Pending'].includes(status) && !['Delivered','Cancelled'].includes(status);
  }
  // Avoid showing confirm after already finalized
  if(['Delivered','Received','Cancelled'].includes(status)) showConfirm = false;

  switch(status){
    case 'Pending':
      // Offer cancel; optionally confirm (if user wants to prematurely mark). We'll keep only cancel to reduce mistakes.
      return `<div class="d-flex gap-1">`+
        `<button class="btn btn-outline-soft-orange btn-sm" data-action="cancel" data-id="${id}">Cancel</button>`+
        (showConfirm && status!=='Pending' ? `<button class="btn btn-soft-orange btn-sm" data-action="confirm" data-id="${id}">Confirm</button>`:'')+
        `</div>`;
    case 'Ready to deliver':
    case 'On the way':
    case 'Ready to pick up':
      return showConfirm ? `<button class="btn btn-soft-orange btn-sm" data-action="confirm" data-id="${id}">Confirm ${isPickup?'Pickup':'Delivery'}</button>` : '';
    case 'Delivered':
      return allReviewed
        ? `<button class="btn btn-secondary btn-sm" data-action="review" data-id="${id}" disabled>Reviewed</button>`
        : `<button class="btn btn-soft-orange btn-sm" data-action="review" data-id="${id}">Review Items</button>`;
    case 'Received':
      return allReviewed
        ? `<button class="btn btn-secondary btn-sm" data-action="review" data-id="${id}" disabled>Reviewed</button>`
        : `<button class="btn btn-soft-orange btn-sm" data-action="review" data-id="${id}">Review Items</button>`;
    default:
      return '';
  }
}

// Confirm order helper (calls ajax/confirm_order.php)
async function confirmOrder(orderId){
  const btn = document.querySelector(`#myOrdersModal [data-action="confirm"][data-id="${orderId}"]`);
  if(btn){
    btn.disabled = true;
    const original = btn.textContent;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Confirming';
    try {
      const res = await fetch('ajax/confirm_order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','Accept':'application/json'},
        body: new URLSearchParams({order_id: orderId}).toString()
      });
      const data = await res.json().catch(()=>({success:false,message:'Invalid response'}));
      console.log('confirm_order.php response', res.status, data);
      if(res.ok && data.success){
        // Update cache and re-render
        const idx = ORDERS_CACHE.findIndex(o => String(o.Order_ID) === String(orderId));
        if(idx!==-1 && data.final_status){ ORDERS_CACHE[idx].order_status = data.final_status; }
        renderStatusChips();
        renderOrders();

        // For pickup: backend returns success only when already Received by admin
        // For delivery: we set Delivered. In both cases we can offer a review prompt.
        if (data.review_prompt) {
          Swal.fire({
            icon:'success',
            title:'Confirmed',
            text:'Would you like to leave a review?',
            showCancelButton:true,
            confirmButtonText:'Yes',
            cancelButtonText:'Later',
            confirmButtonColor:'#FFB27A'
          }).then(r=>{ if(r.isConfirmed){
            // Open review modal for this order
            if (typeof openReviewModalByOrderId === 'function') {
              openReviewModalByOrderId(orderId);
            }
          }});
        } else {
          Swal.fire({icon:'success', title:'Confirmed', timer:1200, showConfirmButton:false});
        }
      } else {
        const msg = (data && data.message) ? data.message : 'Unable to confirm';
        Swal.fire({icon:'error', title:msg, timer:2000, showConfirmButton:false});
        if(btn){ btn.disabled = false; btn.textContent = original; }
      }
    } catch(err){
      Swal.fire({icon:'error', title:'Network error', text:String(err).slice(0,160), confirmButtonColor:'#FFB27A'});
      if(btn){ btn.disabled = false; btn.textContent = original; }
    }
    return;
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
// Helper: load orders via grouped endpoint (fetch_orders.php)
async function loadOrdersGrouped(){
  const listEl = document.getElementById('ordersList');
  if(listEl) listEl.innerHTML = skeletonOrders();
  ACTIVE_STATUS = "All";
  ORDERS_PAGE = 1;
  try {
    const res = await fetch('ajax/fetch_orders.php?t=' + Date.now(), {headers:{'Accept':'application/json'}});
    if(!res.ok) throw new Error('HTTP '+res.status);
    const payload = await res.json();
    if(!payload.success){
      throw new Error(payload.message || 'Failed');
    }
    // Prefer flattened list for existing rendering logic
    ORDERS_CACHE = Array.isArray(payload.flat) ? payload.flat : [];
  try { const uniqueRaw = [...new Set(ORDERS_CACHE.map(o => (o.order_status||'').toString().trim()))]; console.log('[orders] raw statuses fetched:', uniqueRaw); } catch(_e) {}
    renderStatusChips();
    renderOrders();
    // Update orders badge using counts if available
    if(payload.counts && typeof payload.counts.pending === 'number'){
      ordersBadge.textContent = payload.counts.pending;
      ordersBadge.style.display = payload.counts.pending>0 ? 'inline-block':'none';
    } else {
      updateOrdersBadgeFromCache();
    }
    // Auto-start tracking for active delivery orders (first page only to save resources)
    try {
      const forTrack = ORDERS_CACHE.filter(o => ((o.order_type||'').toLowerCase().includes('deliver') || o.Street || o.City) && !['Delivered','Received','Cancelled'].includes(deriveUiStatus(o)) ).slice(0,5);
      forTrack.forEach(o => startDeliveryTracking(o.Order_ID));
    } catch(e) { console.warn('auto track init failed', e); }
  } catch(err){
    if(listEl) listEl.innerHTML = `<div class="text-danger">Failed to load orders.</div>`;
    console.error('fetch_orders failed', err);
  }
}

document.getElementById('myOrdersModal').addEventListener('show.bs.modal', () => {
  if(!CHIP_SEQUENCE.includes(ACTIVE_STATUS)) ACTIVE_STATUS = 'All';
  ACTIVE_STATUS = 'All'; // always reset to show everything when reopening
  loadOrdersGrouped();
});

// ---- Lightweight background status polling (updates existing renderedcards) ----
let ORDER_STATUS_POLL_TIMER = null;
function applyStatusDelta(orders){
  if(!Array.isArray(orders) || !orders.length) return;
  const map = new Map(orders.map(o=>[String(o.Order_ID), o]));
  let changed = false;
  ORDERS_CACHE = ORDERS_CACHE.map(o => {
    const upd = map.get(String(o.Order_ID));
    if(!upd) return o; // order missing is fine
    const newStatus = upd.order_status;
    if(newStatus && newStatus !== o.order_status){
      o.order_status = newStatus;
      changed = true;
      try {
        const card = document.querySelector(`#ordersList .card[data-order-id='${o.Order_ID}']`);
        if(card){
          // Recompute ui status + badge class
          const uiStatus = deriveUiStatus(o);
          const badge = card.querySelector('.order-status-badge');
          if(badge){
            badge.textContent = uiStatus;
            badge.setAttribute('data-status', uiStatus);
            // Update classes (remove previous bg-*)
            badge.className = 'badge order-status-badge ' + (
              uiStatus==='Delivered' ? 'bg-success' :
              uiStatus==='Ready to deliver' ? 'bg-primary' :
              uiStatus==='On the way' ? 'bg-primary' :
              (uiStatus==='To Receive'||uiStatus==='Ready to pick up'||uiStatus==='Received') ? 'bg-warning text-dark' :
              uiStatus==='Processing' ? 'bg-info text-dark' :
              uiStatus==='Pending' ? 'bg-secondary' :
              uiStatus==='Cancelled' ? 'bg-dark' : 'bg-secondary'
            );
          }
          // Replace progress bar/steps
          const progWrap = card.querySelector('.card-body .mt-1');
          if(progWrap){ progWrap.innerHTML = renderProgress(uiStatus, o.order_type); }
        }
      } catch(e){ /* ignore */ }
    }
    if(upd.Driver_Status && upd.Driver_Status !== o.Driver_Status){
      o.Driver_Status = upd.Driver_Status; changed = true; }
    return o;
  });
  if(changed){
    // Update chips counts (cheap recompute) without full list rebuild
    renderStatusChips();
  }
}

async function pollOrderStatuses(){
  try {
    const res = await fetch('ajax/order_status.php?t=' + Date.now(), {cache:'no-store'});
    if(!res.ok) throw new Error('HTTP '+res.status);
    const data = await res.json();
    if(data.success){
      applyStatusDelta(data.orders);
      // Badge update
      if(data.counts && typeof data.counts.pending==='number'){
        ordersBadge.textContent = data.counts.pending;
        ordersBadge.style.display = data.counts.pending>0 ? 'inline-block':'none';
      }
    }
  } catch(e) { /* silent */ }
  finally {
    ORDER_STATUS_POLL_TIMER = setTimeout(pollOrderStatuses, 15000); // 15s
  }
}

// Start polling when page loads (can later pause when modal closed if desired)
pollOrderStatuses();

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
            const resp = await fetch('ajax/cancel_order.php', {
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
    Swal.fire({title:'Confirm this order?',icon:'question',showCancelButton:true,confirmButtonColor:'#FFB27A'})
      .then(r=>{ if(r.isConfirmed){ confirmOrder(id); } });
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

function buildProductModalHtml(product, addons, sizeOptions, basePrice, heading){
  heading = heading || 'Size';
  basePrice = Number(basePrice||product.Price_Amount||0);
  const priceDisplay = '₱' + basePrice.toFixed(2);
  const addonsHtml = (addons||[]).map(a=>`
    <label class="addon-card d-block py-1 px-2 border rounded">
      <div class="d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <input class="form-check-input addon-choice" type="checkbox" value="${a.Addon_ID}" data-name="${a.Addon_Name}" data-price="${a.Addon_Price}">
          <span class="addon-name">${a.Addon_Name}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="addon-qty-wrap input-group input-group-sm" style="width:110px; display:none;" data-price="${a.Addon_Price}">
            <button class="btn btn-outline-secondary addon-minus" type="button">-</button>
            <input type="number" class="form-control text-center addon-qty" value="1" min="1">
            <button class="btn btn-outline-secondary addon-plus" type="button">+</button>
          </div>
          <span class="addon-price small text-nowrap">₱ ${Number(a.Addon_Price).toFixed(2)}</span>
        </div>
      </div>
    </label>
  `).join('') || '<div class="text-muted">No add-ons available.</div>';

  return `
    <div class="product-details-grid">
      <div>
        <div class="product-hero">
          <img src="../admin/uploads/products/${product.Product_Image}" alt="${product.Product_Name}">
        </div>
        <div class="mt-3">
          <div class="d-flex justify-content-between align-items-start">
            <h4 class="product-title mb-1">${product.Product_Name}</h4>
            <div class="product-price" aria-label="Base price">${priceDisplay}</div>
          </div>
          <p class="mt-2 mb-0">${product.Product_desc || ''}</p>
        </div>
      </div>
      <div>
        <div class="mb-3">
          <h5 class="mb-2">${heading}</h5>
          <div id="productSizeChoices" class="d-flex flex-wrap gap-2">
            ${sizeOptions.map((s,i)=>{
              const finalPrice = Number(s.final_price||0);
              // Removed displayed price difference; only show the size label while still storing final price in data attribute.
              return `<label class=\"btn btn-outline-secondary btn-sm m-0 ${i===0?'active':''}\" style=\"position:relative;\">\n                <input type=\"radio\" name=\"pdSize\" class=\"d-none\" value=\"${s.code}\" data-final=\"${finalPrice.toFixed(2)}\" ${i===0?'checked':''}>\n                ${s.label}\n              </label>`;
            }).join('')}
          </div>
        </div>
        <div class="addons-section">
          <h5 class="mb-2">Add-ons</h5>
          <div id="productAddonsList" class="addons-list">${addonsHtml}</div>
        </div>
        <div class="d-flex align-items-center gap-3 mt-3">
          <div class="input-group" style="width:140px;">
            <button class="btn btn-outline-secondary" type="button" id="pdQtyMinus">-</button>
            <input type="number" class="form-control text-center" id="pdQty" value="1" min="1">
            <button class="btn btn-outline-secondary" type="button" id="pdQtyPlus">+</button>
          </div>
          <div class="ms-auto text-end modal-total-line small" style="min-width:200px;">
            <div>Products: <span id="productBaseSubtotal">₱0.00</span></div>
            <div>Add-ons: <span id="productAddonsSubtotal">₱0.00</span></div>
            <div class="fw-semibold mt-1">Total: <span id="productWithAddonsTotal">₱0.00</span></div>
          </div>
        </div>
        <!-- Order instruction textarea -->
        <div class="mt-3">
          <label for="pdInstructions" class="form-label small mb-1">Order Instruction (optional)</label>
          <textarea id="pdInstructions" class="form-control form-control-sm" rows="2" placeholder="e.g., reduce sugar content, no ice, extra spicy"></textarea>
        </div>
      </div>
    </div>`;
}

// Compute and display the modal total based on base price, selected add-ons, and quantity
// In anchor model each radio already exposes the full final unit price (data-final). Upcharge concept deprecated.
function getSelectedSizeFinal(){
  const r = document.querySelector('input[name="pdSize"]:checked');
  if(!r) return (window.__currentAnchorPrice||0);
  if(r.hasAttribute('data-final')) return Number(r.getAttribute('data-final'))||0;
  return (window.__currentAnchorPrice||0);
}

function updateProductModalTotal(anchorPrice){
  const qtyEl = document.getElementById('pdQty');
  const productQty = Math.max(1, Number(qtyEl?.value || 1));
  const selectedUnit = getSelectedSizeFinal();
  let addonsTotal = 0;
  document.querySelectorAll('#productAddonsList .addon-choice:checked').forEach(chk=>{
    const wrap = chk.closest('.addon-card')?.querySelector('.addon-qty-wrap');
    const qtyInput = wrap?.querySelector('.addon-qty');
    const aQty = Math.max(1, Number(qtyInput?.value||1));
    const price = Number(chk.getAttribute('data-price'))||0;
    addonsTotal += price * aQty; // independent of productQty
  });
  const baseSubtotal = (selectedUnit * productQty);
  // Attempt to estimate shipping if user already selected Delivery & provided coords (does NOT get added to per-item total)
  let shippingFeeDisplay = '—';
  try {
    const orderTypeSel = document.querySelector('input[name="orderType"]:checked')?.value;
    if(orderTypeSel === 'Delivery' && summary?.latInput?.value && summary?.lngInput?.value){
      const lat = parseFloat(summary.latInput.value), lng = parseFloat(summary.lngInput.value);
      if(isFinite(lat) && isFinite(lng) && typeof haversineKm === 'function' && typeof computeDeliveryFee === 'function'){
        const dist = haversineKm(STORE_COORDS.lat, STORE_COORDS.lng, lat, lng);
        const fee = computeDeliveryFee(dist);
        if(isFinite(fee)) shippingFeeDisplay = '₱' + fee.toFixed(2);
      }
    }
  } catch(e) { /* ignore */ }

  // Update breakdown elements
  const baseEl = document.getElementById('productBaseSubtotal');
  const addonsEl = document.getElementById('productAddonsSubtotal');
  const shipEl = document.getElementById('productShippingFee');
  const totalEl = document.getElementById('productWithAddonsTotal');
  const footerTotalEl = document.getElementById('productWithAddonsFooterTotal');
  if(baseEl) baseEl.textContent = '₱' + baseSubtotal.toFixed(2);
  if(addonsEl) addonsEl.textContent = '₱' + addonsTotal.toFixed(2);
  if(shipEl) shipEl.textContent = shippingFeeDisplay; // not included in item total
  if(totalEl) totalEl.textContent = '₱' + (baseSubtotal + addonsTotal).toFixed(2);
  if(footerTotalEl) footerTotalEl.textContent = '₱' + (baseSubtotal + addonsTotal).toFixed(2);
}

async function openProductDetailsWithAddons(product){
  // Unified variant fetch. Logic:
  // 1. Try new get_product_variants (sizes + flavors in one table)
  // 2. If sizes exist -> show Size section
  // 3. Else if only flavors exist -> show Variant Choices section
  // 4. Else fallback to legacy get_product_sizes (if still populated) or default Regular
  let sizeOptions = [];
  let heading = 'Size';
  let anchorPrice = Number(product.Price_Amount||0);
  let variantsFetched = false;
  try {
    const res = await fetch('ajax/get_product_variants.php?product_id='+product.Product_ID+'&t='+Date.now());
    const js = await res.json();
    if(js.success && js.variants){
      variantsFetched = true;
      const sizes = Array.isArray(js.variants.size) ? js.variants.size : [];
      const flavors = Array.isArray(js.variants.flavor) ? js.variants.flavor : [];
      if(sizes.length){
        // pick primary size anchor
        const primary = sizes.find(v=>Number(v.is_primary)===1) || sizes[0];
        anchorPrice = (primary.price_mode === 'ABSOLUTE') ? Number(primary.price_value||0) : (Number(product.Price_Amount||0) + Number(primary.price_value||0));
        sizeOptions = sizes.map(v=>{
          const final_price = (v.price_mode === 'ABSOLUTE') ? Number(v.price_value||0) : (Number(product.Price_Amount||0) + Number(v.price_value||0));
          return { code:v.code, label:v.label, final_price, is_anchor:Number(v.is_primary)||0 };
        });
        heading = 'Size';
      } else if(flavors.length){
        const primary = flavors.find(v=>Number(v.is_primary)===1) || flavors[0];
        anchorPrice = (primary.price_mode === 'ABSOLUTE') ? Number(primary.price_value||0) : (Number(product.Price_Amount||0) + Number(primary.price_value||0));
        sizeOptions = flavors.map(v=>{
          const final_price = (v.price_mode === 'ABSOLUTE') ? Number(v.price_value||0) : (Number(product.Price_Amount||0) + Number(v.price_value||0));
          return { code:v.code, label:v.label, final_price, is_anchor:Number(v.is_primary)||0 };
        });
        heading = 'Variant Choices';
      }
    }
  } catch(e){ /* ignore unified variant errors */ }

  // Legacy fallback if nothing was fetched
  if(!sizeOptions.length){
    try {
      const res2 = await fetch('ajax/get_product_sizes.php?product_id='+product.Product_ID+'&t='+Date.now());
      const js2 = await res2.json();
      if(js2.success && Array.isArray(js2.sizes) && js2.sizes.length){
        // legacy structure has base_price + sizes[] each with final_price
        anchorPrice = Number(js2.base_price||product.Price_Amount||0);
        sizeOptions = js2.sizes.map(v=>({ code:v.code, label:v.label||v.code, final_price:Number(v.final_price||anchorPrice), is_anchor:Number(v.is_anchor)||0 }));
        heading = 'Size';
      }
    } catch(e){ /* ignore legacy errors */ }
  }

  // Final fallback if still empty
  if(!sizeOptions.length){
    sizeOptions = [{ code:'default', label:'Regular', final_price:anchorPrice, is_anchor:1 }];
    heading = variantsFetched ? 'Variant Choices' : 'Size';
  }

  // Ensure anchorPrice sensible
  if(!isFinite(anchorPrice) || anchorPrice <= 0){
    anchorPrice = sizeOptions[0]?.final_price || Number(product.Price_Amount||0) || 0;
  }
  window.__currentAnchorPrice = anchorPrice;
  document.getElementById('productDetailsContent').innerHTML = buildProductModalHtml(product, [], sizeOptions, window.__currentAnchorPrice, heading);
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productDetailsModal'));
  modal.show();

  // Bind qty +/- and total updates for this render
  const basePrice = window.__currentAnchorPrice;
  const qtyEl = document.getElementById('pdQty');
  const minusEl = document.getElementById('pdQtyMinus');
  const plusEl = document.getElementById('pdQtyPlus');
  minusEl && minusEl.addEventListener('click', ()=>{ qtyEl.value = Math.max(1, Number(qtyEl.value||1)-1); updateProductModalTotal(basePrice); });
  plusEl && plusEl.addEventListener('click', ()=>{ qtyEl.value = Math.max(1, Number(qtyEl.value||1)+1); updateProductModalTotal(basePrice); });
  qtyEl && qtyEl.addEventListener('change', ()=> updateProductModalTotal(basePrice));
  // Bind size change
  document.getElementById('productDetailsContent').addEventListener('change', e=>{
    if (e.target.name === 'pdSize') updateProductModalTotal(basePrice);
  });
  // Also handle clicking on label itself to toggle the hidden radio (improves hit area reliability)
  document.getElementById('productDetailsContent').addEventListener('click', e=>{
    const lab = e.target.closest('#productSizeChoices label');
    if(!lab) return;
    const input = lab.querySelector('input[name="pdSize"]');
    if(!input) return;
    // Manually set checked and remove from others
    document.querySelectorAll('#productSizeChoices label').forEach(l=> l.classList.remove('active'));
    input.checked = true;
    lab.classList.add('active');
    updateProductModalTotal(basePrice);
  });
  updateProductModalTotal(basePrice);

  // Add to Cart handler
  document.getElementById('modalAddToCartBtn').onclick = ()=>{
    const selected = Array.from(document.querySelectorAll('#productAddonsList .addon-choice:checked'))
      .map(c=>{
        const wrap = c.closest('.addon-card')?.querySelector('.addon-qty-wrap');
        const qtyInput = wrap?.querySelector('.addon-qty');
        return { id:Number(c.value), name:c.getAttribute('data-name'), price:Number(c.getAttribute('data-price'))||0, qty: Math.max(1, Number(qtyInput?.value||1)) };
      });
    const productQty = Math.max(1, Number(document.getElementById('pdQty').value||1));
    const instruction = document.getElementById('pdInstructions')?.value?.trim() || '';
    const sizeRadio = document.querySelector('input[name="pdSize"]:checked');
    const sizeCode = sizeRadio ? sizeRadio.value : 'default';
    // New model: effective unit price is just the selected size's final price (anchor-relative already computed server-side)
    let effectiveUnitPrice = window.__currentAnchorPrice;
    if(sizeRadio && sizeRadio.hasAttribute('data-final')){
      effectiveUnitPrice = Number(sizeRadio.getAttribute('data-final'))||effectiveUnitPrice;
    }
    const found = cart.find(i => i.name === product.Product_Name);
    if (found) {
      found.qty += productQty; // only product quantity increments existing entry
      // Merge addons: if same addon id exists, add quantities
      selected.forEach(sa=>{
        const ex = (found.addons||[]).find(a=>a.id===sa.id);
        if (ex) { ex.qty += sa.qty; } else { (found.addons||[]).push(sa); }
      });
      // If new instruction provided append / merge (simple concatenation if different)
      if (instruction) {
        if (!found.instruction) found.instruction = instruction; else if (!found.instruction.includes(instruction)) found.instruction += ' | ' + instruction;
      }
      // If size differs, we create a new entry instead (avoid mixing sizes)
      if (found.size && found.size !== sizeCode) {
  cart.push({ name: product.Product_Name, qty: productQty, addons: selected, instruction, size: sizeCode, unitPrice: effectiveUnitPrice });
      } else {
        found.size = sizeCode;
        found.unitPrice = effectiveUnitPrice;
      }
    } else {
  cart.push({ name: product.Product_Name, qty: productQty, addons: selected, instruction, size: sizeCode, unitPrice: effectiveUnitPrice });
    }
    updateCartBadge();
    renderCartItems();
    Swal.fire({toast:true, position:'top-end', icon:'success', title:'Added to cart!', showConfirmButton:false, timer:1200});
    // Important: blur focused element before hiding to avoid aria-hidden warning
    try { const ae = document.activeElement; if (ae && typeof ae.blur === 'function') ae.blur(); } catch(e){}
    // Defer hide so blur applies first
    setTimeout(()=>{ modal.hide(); }, 0);
  };

  // Fetch add-ons asynchronously and render; on failure keep empty list
  try{
    const res = await fetch('ajax/get_product_addons.php?product_id='+product.Product_ID+'&t='+Date.now());
    const data = await res.json();
    const addons = data.success ? (data.addons||[]) : [];
    const listEl = document.getElementById('productAddonsList');
    if (listEl) {
      listEl.innerHTML = addons.length ? addons.map(a=>`
        <label class="addon-card d-block py-1 px-2 border rounded">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
              <input class="form-check-input addon-choice" type="checkbox" value="${a.Addon_ID}" data-name="${a.Addon_Name}" data-price="${a.Addon_Price}">
              <span class="addon-name">${a.Addon_Name}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="addon-qty-wrap input-group input-group-sm" style="width:110px; display:none;" data-price="${a.Addon_Price}">
                <button class="btn btn-outline-secondary addon-minus" type="button">-</button>
                <input type="number" class="form-control text-center addon-qty" value="1" min="1">
                <button class="btn btn-outline-secondary addon-plus" type="button">+</button>
              </div>
              <span class="addon-price small text-nowrap">₱ ${Number(a.Addon_Price).toFixed(2)}</span>
            </div>
          </div>
        </label>
      `).join('') : '<div class="text-muted">No add-ons available.</div>';

      // Bind checkbox + qty controls
      document.querySelectorAll('#productAddonsList .addon-choice').forEach(cb=>{
        cb.addEventListener('change', ()=>{
          const wrap = cb.closest('.addon-card')?.querySelector('.addon-qty-wrap');
          if (wrap) wrap.style.display = cb.checked ? 'flex' : 'none';
          updateProductModalTotal(basePrice);
        });
      });
      document.querySelectorAll('#productAddonsList .addon-minus').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const wrap = btn.closest('.addon-qty-wrap');
          const inp = wrap.querySelector('.addon-qty');
          inp.value = Math.max(1, Number(inp.value||1)-1);
          updateProductModalTotal(basePrice);
        });
      });
      document.querySelectorAll('#productAddonsList .addon-plus').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const wrap = btn.closest('.addon-qty-wrap');
          const inp = wrap.querySelector('.addon-qty');
          inp.value = Math.max(1, Number(inp.value||1)+1);
          updateProductModalTotal(basePrice);
        });
      });
      document.querySelectorAll('#productAddonsList .addon-qty').forEach(inp=>{
        inp.addEventListener('change', ()=>{ if (Number(inp.value)<1) inp.value = 1; updateProductModalTotal(basePrice); });
      });
      updateProductModalTotal(basePrice);
    }
  }catch(err){
    console.warn('Add-ons fetch failed, continuing without add-ons', err);
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
  // ===== Delivery Tracking Logic =====
  const ACTIVE_TRACKERS = {}; // order_id -> interval id
  function startDeliveryTracking(orderId){
    orderId = String(orderId);
    if (ACTIVE_TRACKERS[orderId]) return; // already tracking
    const statusEl = document.getElementById('track-status-'+orderId);
    const mapEl = document.getElementById('track-map-'+orderId);
    if(mapEl) mapEl.style.display = 'block';
    let map, marker;
    function ensureLeaflet(cb){
      if (window.L) return cb();
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
      s.onload = cb; document.head.appendChild(s);
      if(!document.querySelector('link[href*="leaflet.css"]')){
        const l=document.createElement('link'); l.rel='stylesheet'; l.href='https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css'; document.head.appendChild(l);
      }
    }
    async function tick(){
      try {
        const r = await fetch('ajax/delivery_tracking.php?order_id='+encodeURIComponent(orderId)+'&t='+Date.now());
        const j = await r.json();
        if(!j.success){ throw new Error(j.message||'fetch failed'); }
        if(statusEl) statusEl.textContent = j.derived_status || j.order_status || '—';
        if(j.lat && j.lng && mapEl){
          ensureLeaflet(()=>{
            if(!map){
              map = L.map(mapEl).setView([j.lat,j.lng], 15);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OSM'}).addTo(map);
              marker = L.marker([j.lat,j.lng]).addTo(map);
              setTimeout(()=>{ try{ map.invalidateSize(); }catch(_){} }, 400);
            } else {
              marker.setLatLng([j.lat,j.lng]);
              map.panTo([j.lat,j.lng]);
            }
          });
        }
        if(j.terminal){ stopDeliveryTracking(orderId); }
      } catch(err){
        if(statusEl) statusEl.textContent = 'Tracking error';
        console.warn('tracking error', err);
      }
    }
    tick();
    ACTIVE_TRACKERS[orderId] = setInterval(tick, 10000); // 10s
  }
  function stopDeliveryTracking(orderId){
    orderId = String(orderId);
    if(ACTIVE_TRACKERS[orderId]){ clearInterval(ACTIVE_TRACKERS[orderId]); delete ACTIVE_TRACKERS[orderId]; }
  }
  function stopAllTracking(){ Object.keys(ACTIVE_TRACKERS).forEach(stopDeliveryTracking); }
  window.startDeliveryTracking = startDeliveryTracking;
  window.stopDeliveryTracking = stopDeliveryTracking;
  window.stopAllTracking = stopAllTracking;

  // Delegate click for Track buttons
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-track]');
    if(!btn) return;
    const oid = btn.getAttribute('data-track');
    startDeliveryTracking(oid);
  });

  // Stop tracking when modal hidden
  document.getElementById('myOrdersModal').addEventListener('hidden.bs.modal', () => {
    stopAllTracking();
  });
  </script>

  <script>
  // Robust delegated click handlers to cover dynamically rendered cards/buttons
  document.addEventListener('click', function(e){
    const addBtn = e.target.closest('.add-to-cart-btn');
    if (addBtn) {
      e.preventDefault();
      e.stopPropagation();
      const productName = addBtn.getAttribute('data-product');
      try{
        const allProducts = <?php echo json_encode($all_products); ?>;
        const prod = (allProducts||[]).find(p => p.Product_Name === productName);
        if (prod) openProductDetailsWithAddons(prod);
      }catch(err){ console.warn('delegate add-to-cart failed', err); }
      return;
    }

    const card = e.target.closest('.menu-card');
    if (card && !e.target.closest('.add-to-cart-btn')){
      e.preventDefault();
      try{
        const pid = card.getAttribute('data-product-id');
        const allProducts = <?php echo json_encode($all_products); ?>;
        const prod = (allProducts||[]).find(p => String(p.Product_ID) === String(pid));
        if (prod) openProductDetailsWithAddons(prod);
      }catch(err){ console.warn('delegate card click failed', err); }
    }
  });

  async function openAddonsModal(productId, productName){
    PENDING_ADD_TO_CART = { productId, productName };
    try{
      const res = await fetch('ajax/get_product_addons.php?product_id='+productId+'&t='+Date.now());
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

  <script>
  // Clean up contact param in URL after showing the alert once (parity with landing page)
  (function(){
    try {
      const params = new URLSearchParams(window.location.search);
      if (params.get('contact')) {
        params.delete('contact');
        const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '') + window.location.hash;
        history.replaceState({}, '', newUrl);
      }
    } catch (_) { /* noop */ }
  })();
  </script>

</body>
</html>