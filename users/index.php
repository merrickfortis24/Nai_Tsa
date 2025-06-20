<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
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

// Fetch user's orders grouped by status
$user_id = $_SESSION['customer_id'] ?? null;
$orders_by_status = [
    'To Ship' => [],
    'To Receive' => [],
    'Delivered' => []
];
if ($user_id) {
    $stmt = $db->opencon()->prepare("
        SELECT o.*, p.payment_status 
        FROM orders o
        LEFT JOIN payment p ON o.Order_ID = p.Order_ID
        WHERE o.Customer_ID = ?
        ORDER BY o.Order_Date DESC
    ");
    $stmt->execute([$user_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_orders as $order) {
        if ($order['order_status'] === 'Pending' && $order['payment_status'] === 'Unpaid') {
            $orders_by_status['To Ship'][] = $order;
        } elseif ($order['order_status'] === 'Processing' && $order['payment_status'] === 'Paid') {
            $orders_by_status['To Receive'][] = $order;
        } elseif ($order['order_status'] === 'Delivered') {
            $orders_by_status['Delivered'][] = $order;
        }
    }
}
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
        <img src="https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/305017926_123739037082830_6536344361033765846_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeF6gojYSTdWNY4orY0VNUkSmvcRd1ll5jia9xF3WWXmODD-saAHrmXgUQmKemzloGzWiKXvFLnLMDOAGKdxzyD6&_nc_ohc=7iBKmMdkBywQ7kNvwFRDQYs&_nc_oc=AdlY_BYvScrT1IflonpxA1Qvq5KxK43IM6csPvtUSzdETsmOm1huAnaj3u8V2bhL94M&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=zr_iO0JmrhCwDGAHOAqdbQ&oh=00_AfP71h0Bxwo_zXF6XA1C60idZzXqlq6yMUdhgIvHHgnRbA&oe=6855DFB1" alt="Nai Tsa Logo">
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
        <div class="d-flex align-items-center ms-lg-auto flex-column flex-lg-row gap-2 gap-lg-0"></div>
          <!-- Profile Button -->
          <button class="btn btn-soft-orange d-flex align-items-center" style="font-weight:600;" type="button" data-bs-toggle="offcanvas" data-bs-target="#profileOffcanvas" aria-controls="profileOffcanvas">
            <span style="font-size:1.2em; margin-right:0.4em;">👤</span> <?php echo htmlspecialchars($first_name); ?>
          </button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Home Section -->
  <section class="section" id="home" style="background-image: url('https://scontent.fmnl17-5.fna.fbcdn.net/v/t39.30808-6/481348026_632646619530004_740397893899066208_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGjeWCZxoZbHgis-Iy92UoLfJc4_v2UeV98lzj-_ZR5X62vIJ6nZ8AEIKTkEyrTT0-NSz1c1Dnwv6l3ZYCKxOZO&_nc_ohc=IoXR7wyIjLUQ7kNvwHQwm6G&_nc_oc=AdnqvKOCbjlR6zsfT9Xit12YcARhX_I_8H_TIIkZAiqOAfER-36O27n62qJArtAolro&_nc_zt=23&_nc_ht=scontent.fmnl17-5.fna&_nc_gid=7SyQ3ne9y6nkAJJldJavpQ&oh=00_AfPuyO8_RiTXaOfVXlEjzq5g4I4HtOi4MssiFJUHE8K8-Q&oe=68573FCE');">
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
  <section class="section" id="menu" style="background-image: url('https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/491355857_667464796048186_5563372254686319857_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeE0EMgNYAh3sK2ELezcsoNjzv-0_uK6yJ3O_7T-4rrInUs8ZlmDenP6NZyx0xgnzkzsNGivZydRi5Li6rGMQUB3&_nc_ohc=rAv1shhALVkQ7kNvwE3w9N4&_nc_oc=Adk6Jm3AmYFGOv995P_DfsKAQr-i0f8w53QvRtw5nwXiOvVljmBRX9sq1eTUXTIavbc&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=V2fgOrY4_pomG0SQG-3PXg&oh=00_AfO6pfYqaazFFNvB1wVfZ-BRs5H1FH2ww29kww2eww47Xg&oe=685739AE');
         min-height: 140vh;">
  <div class="section-overlay"></div>
  <div class="section-content" style="max-width: 1200px;">
    <h2 class="section-title text-center w-100">Our Bestsellers</h2>
    <div class="menu-cards w-100 justify-content-center" style="margin-bottom: 1.2rem;">
      <?php foreach($products as $product): ?>
        <div class="menu-card">
          <img src="../admin/uploads/products/<?php echo htmlspecialchars($product['Product_Image']); ?>" alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
          <div class="menu-card-title"><?php echo htmlspecialchars($product['Product_Name']); ?></div>
          <div class="menu-card-desc"><?php echo htmlspecialchars($product['Product_desc']); ?></div>
          <button class="btn btn-soft-orange w-100 mt-2 add-to-cart-btn" data-product="<?php echo htmlspecialchars($product['Product_Name']); ?>">Add to Cart</button>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center w-100" style="margin-top: 0.2rem;">
      <a href="#" class="btn btn-outline-soft-orange" style="font-size:1.09rem; padding:0.7rem 2.2rem; font-weight:600;">
        More Products
      </a>
    </div>
  </div>
</section>


  <!-- About Section -->
  <section class="section" id="about" style="background-image: url('https://scontent.fmnl17-3.fna.fbcdn.net/v/t39.30808-6/492083848_665920679535931_5251080750071902474_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=103&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeFvE24HQYrGCLztoy0q1iVky-O2MrQF4tPL47YytAXi03jyIo9KNZeHG0ySxzVgnkOCZOpwBwkzQGtlkePoY-3b&_nc_ohc=jN5eptfODhcQ7kNvwG4Ef0F&_nc_oc=Adm_4leRZmGBIb2RC3Tfu5AEHz-C9iSf9PKGY2_YQT-Zi44EVk-uOSXgS3vKQGo0fEE&_nc_zt=23&_nc_ht=scontent.fmnl17-3.fna&_nc_gid=FjcLizQYXJk84MJLDTqLrg&oh=00_AfM0pK8aklYOwNXgKzFiUIIh-WYH8xAlLAo0_0sJQZqF5A&oe=6856E490');">
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
  <section class="section" id="contact" style="background-image: url('https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/481449891_632646246196708_7996399925717148248_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=100&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGy0LK87mSeUDECC8Ktj2Ei_65UWvk0DCj_rlRa-TQMKPlBzeHsEm_sMikSxW6slUTTX2Jyl5y7V6rLdhYObEvD&_nc_ohc=T8y8_qVc-1wQ7kNvwEGhA2W&_nc_oc=AdlbqtBntReIPoXt_9bpUrX7ebuEcLoLnY9Ggy3Y5psn48_znQvfNl3S3cdQne3kcoY&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=Zrm2Hk5Ah0HezAuhPSGB9A&oh=00_AfOf-gCXz3vKmMCuFQ6MjPQ2k4A-F-nXNfrD5cqSZZjhpQ&oe=6856E81F');">
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
        <a href="#" class="btn btn-soft-orange w-100 mb-2" data-bs-toggle="modal" data-bs-target="#myOrdersModal">My Orders</a>
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
        <ul class="nav nav-tabs mb-3" id="ordersTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="to-ship-tab" data-bs-toggle="tab" data-bs-target="#to-ship" type="button" role="tab">To Ship</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="to-receive-tab" data-bs-toggle="tab" data-bs-target="#to-receive" type="button" role="tab">To Receive</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="delivered-tab" data-bs-toggle="tab" data-bs-target="#delivered" type="button" role="tab">Delivered</button>
          </li>
        </ul>
        <div class="tab-content" id="ordersTabContent">
          <div class="tab-pane fade show active" id="to-ship" role="tabpanel">
            <?php if (count($orders_by_status['To Ship'])): ?>
              <?php foreach ($orders_by_status['To Ship'] as $order): ?>
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #<?= $order['Order_ID'] ?></strong> | <?= htmlspecialchars($order['Order_Date']) ?></div>
                    <div>Status: 
                      <?php if ($order['order_status'] === 'Delivered'): ?>
                        <span class="badge bg-success"><?= htmlspecialchars($order['order_status']) ?></span>
                      <?php elseif ($order['order_status'] === 'Processing'): ?>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars($order['order_status']) ?></span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($order['order_status']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div>Total: ₱<?= number_format($order['Order_Amount'], 2) ?></div>
                    <div class="mt-2" style="font-size:0.97em;">
                      <strong>Items:</strong><br>
                      <?php
                        $items = $orderObj->getOrderItems($order['Order_ID']); // Use $orderObj or $order as your Order class instance
                        foreach ($items as $item) {
                          echo htmlspecialchars($item['Product_Name']) . " x " . $item['Quantity'] . "<br>";
                        }
                      ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted">No orders to ship.</div>
            <?php endif; ?>
          </div>
          <div class="tab-pane fade" id="to-receive" role="tabpanel">
            <?php if (count($orders_by_status['To Receive'])): ?>
              <?php foreach ($orders_by_status['To Receive'] as $order): ?>
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #<?= $order['Order_ID'] ?></strong> | <?= htmlspecialchars($order['Order_Date']) ?></div>
                    <div>Status: <span class="badge bg-info text-dark"><?= htmlspecialchars($order['order_status']) ?></span></div>
                    <div>Total: ₱<?= number_format($order['Order_Amount'], 2) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted">No orders to receive.</div>
            <?php endif; ?>
          </div>
          <div class="tab-pane fade" id="delivered" role="tabpanel">
            <?php if (count($orders_by_status['Delivered'])): ?>
              <?php foreach ($orders_by_status['Delivered'] as $order): ?>
                <div class="card mb-2">
                  <div class="card-body">
                    <div><strong>Order #<?= $order['Order_ID'] ?></strong> | <?= htmlspecialchars($order['Order_Date']) ?></div>
                    <div>Status: <span class="badge bg-success"><?= htmlspecialchars($order['order_status']) ?></span></div>
                    <div>Total: ₱<?= number_format($order['Order_Amount'], 2) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted">No delivered orders.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- Footer -->
  <footer class="footer">
    &copy; 2025 Nai Tsa &mdash; Coffee & Milk Tea. Designed with <span style="color: var(--soft-orange);">&#10084;</span>
  </footer>

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

    // Images for each section
    const homeImages = [
      "https://scontent.fmnl17-5.fna.fbcdn.net/v/t39.30808-6/481348026_632646619530004_740397893899066208_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGjeWCZxoZbHgis-Iy92UoLfJc4_v2UeV98lzj-_ZR5X62vIJ6nZ8AEIKTkEyrTT0-NSz1c1Dnwv6l3ZYCKxOZO&_nc_ohc=IoXR7wyIjLUQ7kNvwHQwm6G&_nc_oc=AdnqvKOCbjlR6zsfT9Xit12YcARhX_I_8H_TIIkZAiqOAfER-36O27n62qJArtAolro&_nc_zt=23&_nc_ht=scontent.fmnl17-5.fna&_nc_gid=7SyQ3ne9y6nkAJJldJavpQ&oh=00_AfPuyO8_RiTXaOfVXlEjzq5g4I4HtOi4MssiFJUHE8K8-Q&oe=68573FCE",
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/500230252_692319856896013_8852028192218548547_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeF-RPA8YMrq0jSRkO2a0609EMOea9UkhbkQw55r1SSFuTfggM7u_KphE4xaukwWnHiAvNIb54Tdug6LylldXazD&_nc_ohc=8WcA0JIr4KIQ7kNvwFY78B0&_nc_oc=AdlTPV6RJW8qhyOfoECtVos5lPInQmWRuboETiLNzFvf83N1xvPdu7pD2Hn9uOOZzWU&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=Gq6LmE5At4ZrlEznx7hrpA&oh=00_AfPJo1WfXQ6mGewsL6MBctLZTzlYWpdf38MhNxyZzLAhew&oe=6856EF07"
    ];
    const menuImages = [
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/491355857_667464796048186_5563372254686319857_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeE0EMgNYAh3sK2ELezcsoNjzv-0_uK6yJ3O_7T-4rrInUs8ZlmDenP6NZyx0xgnzkzsNGivZydRi5Li6rGMQUB3&_nc_ohc=rAv1shhALVkQ7kNvwE3w9N4&_nc_oc=Adk6Jm3AmYFGOv995P_DfsKAQr-i0f8w53QvRtw5nwXiOvVljmBRX9sq1eTUXTIavbc&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=V2fgOrY4_pomG0SQG-3PXg&oh=00_AfO6pfYqaazFFNvB1wVfZ-BRs5H1FH2ww29kww2eww47Xg&oe=685739AE",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/493052023_669980562463276_344743802743648025_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGwj-3y5c4c6RFeK3M8r6uqGjWNEW45BwQaNY0RbjkHBKNFMGyVnsaTCmP1jYD64leH77wr4A5YINiGB7s9YlbZ&_nc_ohc=OrDGRMfc9fEQ7kNvwFilZ3w&_nc_oc=AdnXPNhq_uUsbGVZ7AqplJovZMyR8sl23I19fYpi4ku-ZydHFjsGxgkpAVjUahaYbNI&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=sSGMrA8A6u3A7aKsg0EEcg&oh=00_AfO_u41aZWGJd10VxQS-Vdm_iPd5H9alPfqk4ifE7bxztQ&oe=685733E8"
    ];
    const aboutImages = [
      "https://scontent.fmnl17-3.fna.fbcdn.net/v/t39.30808-6/492083848_665920679535931_5251080750071902474_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=103&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeFvE24HQYrGCLztoy0q1iVky-O2MrQF4tPL47YytAXi03jyIo9KNZeHG0ySxzVgnkOCZOpwBwkzQGtlkePoY-3b&_nc_ohc=jN5eptfODhcQ7kNvwG4Ef0F&_nc_oc=Adm_4leRZmGBIb2RC3Tfu5AEHz-C9iSf9PKGY2_YQT-Zi44EVk-uOSXgS3vKQGo0fEE&_nc_zt=23&_nc_ht=scontent.fmnl17-3.fna&_nc_gid=FjcLizQYXJk84MJLDTqLrg&oh=00_AfM0pK8aklYOwNXgKzFiUIIh-WYH8xAlLAo0_0sJQZqF5A&oe=6856E490",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/305017926_123739037082830_6536344361033765846_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeF6gojYSTdWNY4orY0VNUkSmvcRd1ll5jia9xF3WWXmODD-saAHrmXgUQmKemzloGzWiKXvFLnLMDOAGKdxzyD6&_nc_ohc=7iBKmMdkBywQ7kNvwFRDQYs&_nc_oc=AdlY_BYvScrT1IflonpxA1Qvq5KxK43IM6csPvtUSzdETsmOm1huAnaj3u8V2bhL94M&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=zr_iO0JmrhCwDGAHOAqdbQ&oh=00_AfP71h0Bxwo_zXF6XA1C60idZzXqlq6yMUdhgIvHHgnRbA&oe=6855DFB1"
    ];
    const contactImages = [
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/481449891_632646246196708_7996399925717148248_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=100&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGy0LK87mSeUDECC8Ktj2Ei_65UWvk0DCj_rlRa-TQMKPlBzeHsEm_sMikSxW6slUTTX2Jyl5y7V6rLdhYObEvD&_nc_ohc=T8y8_qVc-1wQ7kNvwEGhA2W&_nc_oc=AdlbqtBntReIPoXt_9bpUrX7ebuEcLoLnY9Ggy3Y5psn48_znQvfNl3S3cdQne3kcoY&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=Zrm2Hk5Ah0HezAuhPSGB9A&oh=00_AfOf-gCXz3vKmMCuFQ6MjPQ2k4A-F-nXNfrD5cqSZZjhpQ&oe=6856E81F",
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/500230252_692319856896013_8852028192218548547_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeF-RPA8YMrq0jSRkO2a0609EMOea9UkhbkQw55r1SSFuTfggM7u_KphE4xaukwWnHiAvNIb54Tdug6LylldXazD&_nc_ohc=8WcA0JIr4KIQ7kNvwFY78B0&_nc_oc=AdlTPV6RJW8qhyOfoECtVos5lPInQmWRuboETiLNzFvf83N1xvPdu7pD2Hn9uOOZzWU&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=Gq6LmE5At4ZrlEznx7hrpA&oh=00_AfPJo1WfXQ6mGewsL6MBctLZTzlYWpdf38MhNxyZzLAhew&oe=6856EF07"
    ];

    // Setup rotating backgrounds
    setupRotatingBg("home", homeImages);
    setupRotatingBg("menu", menuImages);
    setupRotatingBg("about", aboutImages);
    setupRotatingBg("contact", contactImages);

    // Cart logic
let cart = [];
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

function renderCartItems() {
  if (cart.length === 0) {
    cartItemsList.innerHTML = '<div class="text-center text-muted" style="font-size:1.1rem;">Your cart is empty.</div>';
    return;
  }
  cartItemsList.innerHTML = cart.map((item, idx) => `
    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
      <div>
        <strong>${item.name}</strong>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary">${item.qty}</span>
        <button class="remove-cart-item" data-idx="${idx}" title="Remove">
          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5.5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6zm3 .5a.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6zm-7-1A1.5 1.5 0 0 1 5.5 4h5A1.5 1.5 0 0 1 12 5.5V6h1.5A.5.5 0 0 1 14 6.5v.5a.5.5 0 0 1-.5.5H2.5a.5.5 0 0 1-.5-.5v-.5A.5.5 0 0 1 2.5 6H4v-.5zM5.5 5a.5.5 0 0 0-.5.5V6h6v-.5a.5.5 0 0 0-.5-.5h-5z"/>
          </svg>
        </button>
      </div>
    </div>
  `).join('');
  
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
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const product = this.getAttribute('data-product');
    // Check if already in cart
    const found = cart.find(item => item.name === product);
    if (found) {
      found.qty += 1;
    } else {
      cart.push({ name: product, qty: 1 });
    }
    updateCartBadge();
    renderCartItems();
    // Do NOT open the modal here
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
  </script>
</body>
</html>