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
  <!-- Leaflet CSS for interactive map picker -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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
  <!-- Leaflet JS for map picker -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9vmQ9vR4C0R8y+4U6jk=" crossorigin=""></script>
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
const ordersBadge = document.getElementById('orders-badge');
const cartItemsList = document.getElementById('cart-items-list');

// Count ONLY strictly 'Pending' orders (exclude Processing, To Ship, etc.)
function derivePendingOrders(list){
  if(!Array.isArray(list)) return 0;
  let c=0; for(const o of list){
    const st=(o.order_status||o.ui_status||'').trim();
    if(st === 'Pending') c++;
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
  // Auto refresh entire page after cart modal closes per request
  try { setTimeout(()=>{ window.location.reload(); }, 80); } catch(e){}
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

function moneyPhp(n){ return '₱' + (Number(n||0).toFixed(2)); }

function getProductPriceByName(name){
  try{
    const allProducts = <?php echo json_encode($all_products); ?>;
    const p = (allProducts||[]).find(pp => pp.Product_Name === name);
    return Number(p?.Price_Amount || 0);
  }catch(e){ return 0; }
}

function computeCartSubtotal(){
  // Base product total plus independent addon totals (addon qty not multiplied by product qty)
  return cart.reduce((sum, item)=>{
    const base = getProductPriceByName(item.name) * (item.qty||1);
    const addons = (item.addons||[]).reduce((s,a)=> s + (Number(a.price)||0) * (a.qty||1), 0);
    return sum + base + addons;
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

function updateOrderSummary(){
  const subtotal = computeCartSubtotal();
  const orderType = document.querySelector('input[name="orderType"]:checked')?.value || 'Pick Up';
  let fee = 0;
  let distance = null;
  if (orderType === 'Delivery'){
    const lat = parseFloat(summary.latInput?.value);
    const lng = parseFloat(summary.lngInput?.value);
    if (isFinite(lat) && isFinite(lng)){
      distance = haversineKm(STORE_COORDS.lat, STORE_COORDS.lng, lat, lng);
      fee = computeDeliveryFee(distance);
      if(summary.distanceInfo){ summary.distanceInfo.textContent = `Distance: ${distance.toFixed(2)} km • Fee: ${moneyPhp(fee)}`; }
    } else {
      if(summary.distanceInfo){ summary.distanceInfo.textContent = 'Tip: Use your location to estimate the delivery fee.'; }
    }
  } else {
    if(summary.distanceInfo){ summary.distanceInfo.textContent = ''; }
  }
  if(summary.subtotalEl) summary.subtotalEl.textContent = moneyPhp(subtotal);
  if(summary.deliveryEl) summary.deliveryEl.textContent = moneyPhp(fee);
  if(summary.totalEl) summary.totalEl.textContent = moneyPhp(subtotal + fee);
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
    document.getElementById('gcashFields').style.display = this.value === 'GCash' ? 'block' : 'none';
    document.getElementById('creditFields').style.display = this.value === 'Credit Card' ? 'block' : 'none';
    if (this.value === 'Credit Card') {
      document.getElementById('generatedCardNumber').textContent = generateCreditCardNumber();
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
  // If GCash via PayMongo: create source first, then submit order only after returning success (simplified: we create order immediately as pending then redirect)
  if (paymentMethod === 'GCash') {
    // Calculate total locally (reuse summary) to send to PayMongo
    try {
      const subtotal = cart.reduce((sum,i)=>{
        const prod = (<?php echo json_encode($all_products); ?>||[]).find(p=>p.Product_Name===i.name);
        if(!prod) return sum;
        let base = Number(prod.Price_Amount||0) * (i.qty||1);
        if (Array.isArray(i.addons)) {
          i.addons.forEach(ad=>{ base += (Number(ad.price)||0) * (i.qty||1); });
        }
        return sum + base;
      },0);
      // Rough delivery fee (already computed when modal opened & summary updated) - parse from DOM
      let deliveryFee = 0; const dfEl = document.getElementById('summaryDelivery');
      if (dfEl) { const m = dfEl.textContent.match(/([0-9]+(?:\.[0-9]+)?)/); if (m) deliveryFee = parseFloat(m[1])||0; }
      const grand = subtotal + deliveryFee;
      // Create PayMongo source
      const pmRes = await fetch('paymongo_create_source.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({amount:grand})});
      const pmJson = await pmRes.json();
      if(!pmJson.success){ throw new Error(pmJson.message||'GCash source error'); }
      // Redirect user to GCash checkout
      window.location.href = pmJson.redirect;
      return; // stop normal order creation until success callback
    } catch(err){
      Swal.fire({icon:'error', title:'GCash Error', text: err.message||'Unable to start GCash payment', confirmButtonColor:'#FFB27A'});
      return;
    }
  }

  // Send data to PHP (non-GCash flows)
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
      lat,
      lng,
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
        // Quick view: refresh orders via lightweight endpoint & open modal
        if (typeof refreshOrdersAjax === 'function') {
          refreshOrdersAjax({ open:true, focusOrderId: data.order_id || null });
        }
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

// === Quick Orders AJAX Refresh ===
if(typeof refreshOrdersAjax==='undefined'){
  async function refreshOrdersAjax(opts={open:false,focusOrderId:null}) {
    try {
      const r = await fetch('ajax/refresh_new_order.php?t='+Date.now(), {cache:'no-store'});
      const json = await r.json();
      if(!json.success) return;
      const list = json.orders||[];
      if(Array.isArray(list)) {
        ORDERS_CACHE = list;
        if(typeof updateOrdersBadgeFromCache==='function') updateOrdersBadgeFromCache();
        if(opts.open){
          const modalEl = document.getElementById('myOrdersModal');
          if(modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        const modalShown = document.getElementById('myOrdersModal')?.classList.contains('show');
        if(modalShown || opts.open){
          if(typeof renderStatusChips==='function') renderStatusChips();
          if(typeof renderOrders==='function') renderOrders();
        }
      }
    } catch(e){ console.warn('refreshOrdersAjax failed', e); }
  }
}
// Hook into existing checkout success if not already present
if(typeof __ordersRefreshHookInstalled==='undefined'){
  __ordersRefreshHookInstalled=true;
  const origFetch = window.fetch;
  // (Optional advanced interception omitted for simplicity)
}
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