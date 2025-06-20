<?php
session_start();
$customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : 'Guest';
$first_name = explode(' ', trim($customer_name))[0];
require_once "classes/database.php";
$db = new database();
$products = $db->fetchAllProducts();
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
    <a href="logout.php" class="btn btn-outline-soft-orange w-100 mb-2">Logout</a>
    <button type="button" class="btn btn-soft-orange w-100" data-bs-dismiss="offcanvas">Close</button>
  </div>
</div>

  <!-- Cart Floating Action Button -->
  <a href="#" id="cartFab" class="cart-fab" title="View Cart" aria-label="Cart" data-bs-toggle="modal" data-bs-target="#cartModal">
    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16" style="display:block;">
      <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 14H4a.5.5 0 0 1-.491-.408L1.01 2H.5a.5.5 0 0 1-.5-.5zm3.14 4l1.25 6h7.22l1.25-6H3.14zM5.5 16a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm7 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
    </svg>
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
        <div class="text-center text-muted" style="font-size:1.1rem;">Your cart is empty.</div>
        <!-- You can dynamically fill cart items here -->
      </div>
      <div class="modal-footer" style="background:var(--beige);border-bottom-left-radius:24px;border-bottom-right-radius:24px;">
        <button type="button" class="btn btn-outline-soft-orange" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-soft-orange" disabled>Checkout</button>
      </div>
    </div>
  </div>
</div>

  <!-- Footer -->
  <footer class="footer">
    &copy; 2025 Nai Tsa &mdash; Coffee & Milk Tea. Designed with <span style="color: var(--soft-orange);">&#10084;</span>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

    // Add to Cart functionality (simple alert for demo)
    document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const product = this.getAttribute('data-product');
        alert(product + " added to cart!");
      });
    });

    // Animate cart button when modal opens/closes
    const cartFab = document.getElementById('cartFab');
    const cartModal = document.getElementById('cartModal');

    cartModal.addEventListener('show.bs.modal', function () {
      cartFab.classList.add('hide');
    });
    cartModal.addEventListener('hidden.bs.modal', function () {
      cartFab.classList.remove('hide');
    });
  </script>
</body>
</html>