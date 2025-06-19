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
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/500230252_692319856896013_8852028192218548547_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeF-RPA8YMrq0jSRkO2a0609EMOea9UkhbkQw55r1SSFuTfggM7u_KphE4xaukwWnHiAvNIb54Tdug6LylldXazD&_nc_ohc=8WcA0JIr4KIQ7kNvwFY78B0&_nc_oc=AdlTPV6RJW8qhyOfoECtVos5lPInQmWRuboETiLNzFvf83N1xvPdu7pD2Hn9uOOZzWU&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=Gq6LmE5At4ZrlEznx7hrpA&oh=00_AfPJo1WfXQ6mGewsL6MBctLZTzlYWpdf38MhNxyZzLAhew&oe=6856EF07",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/493052023_669980562463276_344743802743648025_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGwj-3y5c4c6RFeK3M8r6uqGjWNEW45BwQaNY0RbjkHBKNFMGyVnsaTCmP1jYD64leH77wr4A5YINiGB7s9YlbZ&_nc_ohc=OrDGRMfc9fEQ7kNvwFilZ3w&_nc_oc=AdnXPNhq_uUsbGVZ7AqplJovZMyR8sl23I19fYpi4ku-ZydHFjsGxgkpAVjUahaYbNI&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=sSGMrA8A6u3A7aKsg0EEcg&oh=00_AfO_u41aZWGJd10VxQS-Vdm_iPd5H9alPfqk4ifE7bxztQ&oe=685733E8",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/481294144_624364470358219_860891811750732829_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHLlRoNoi7zWoigya9vaAT0sMauuYYljSSwxq65hiWNJLGcaA9cPyNf2f2xk_HWL7PdxWWfDm2-1jWA8iyOk-Sf&_nc_ohc=NdgaVooQC2YQ7kNvwGhBpSG&_nc_oc=AdkWRoUiO4asm6zDypw2FhwL_dTvWpSPG7AsmYqTtn34JteLUP0dWn0Ph8nkJwcYmIc&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=tRJGqKakTzlrsQpVDPSS4Q&oh=00_AfOxjdCwWFahWc9xOG5e_Ax1dOBmWE7cGkYVIgHO5Olyjw&oe=68572601",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/492570599_669980632463269_9057156652275483609_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeG29SkbAt9d7QWX14JWhEm-LExZIFPLL9csTFkgU8sv11I0S_VliZXzutVsV2Gx44Ks-ASZnBJ8HGRV8q6DLOLt&_nc_ohc=OlAm8i4SlhUQ7kNvwFYXEhy&_nc_oc=AdlYq38S98w0Qa_D_mZQItk8hF2S-pysbMaOZaqXoOBpFmQXlmlh684zPfpuTsUMoOo&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=LkOt-NFJ_aWdkDQWhdtWrg&oh=00_AfOSqZSEDahW-56DdjknmcG4cUJHz7PX3tVK_N62hIIClA&oe=685739AC"
    ];
    const aboutImages = [
      "https://scontent.fmnl17-8.fna.fbcdn.net/v/t39.30808-6/500901111_696004739860858_5978289012828601449_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeEH83TYlMACOHgUzsoWoeVJ3IAsrcyaYDLcgCytzJpgMuAtq6jtktwB0isENcW9Sdaht7MoidUM83f6hFryWuv-&_nc_ohc=kI3z8OvKaNUQ7kNvwEaa8ls&_nc_oc=AdnC9-ME2iSBxNWwP1zTbgMrrcJ9sLLOvQPrTSHZzEEl_kwCeAcvxNC7jh818zEWEi0&_nc_zt=23&_nc_ht=scontent.fmnl17-8.fna&_nc_gid=Nejdj_KH4Yn2sZ4AZjeuzQ&oh=00_AfMIaXb4ofnbmfvRAT1-zlsn8tTPHnhvOYnPcgEy9guBTw&oe=6855F408",
      "https://scontent.fmnl17-5.fna.fbcdn.net/v/t39.30808-6/481266581_632646616196671_5652414074369422124_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeFcNU-HIfS6UfuGOi2wF5ZGKr1zqPg5v80qvXOo-Dm_zS-Q3nJLDEQa7pivLmvXaTjD1fXqGTTJQmUHVTYngIrB&_nc_ohc=_k0w6iuZoYwQ7kNvwEaKF27&_nc_oc=AdlnjU5KKKL7vxQg90U6BdIpc20yQiWfuHlnUUMv2ggUqgFfj7ZCB5VwdLZLwKS2uH0&_nc_zt=23&_nc_ht=scontent.fmnl17-5.fna&_nc_gid=MneneJyk1kqar7ahTwFbLw&oh=00_AfMyhbNIR4hpSNJqsysSaOhEP4_kfIXJBqviyoRQrkhj2w&oe=68573210",
      "https://scontent.fmnl17-5.fna.fbcdn.net/v/t39.30808-6/481348026_632646619530004_740397893899066208_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGjeWCZxoZbHgis-Iy92UoLfJc4_v2UeV98lzj-_ZR5X62vIJ6nZ8AEIKTkEyrTT0-NSz1c1Dnwv6l3ZYCKxOZO&_nc_ohc=IoXR7wyIjLUQ7kNvwHQwm6G&_nc_oc=AdnqvKOCbjlR6zsfT9Xit12YcARhX_I_8H_TIIkZAiqOAfER-36O27n62qJArtAolro&_nc_zt=23&_nc_ht=scontent.fmnl17-5.fna&_nc_gid=UbBspm8dbe-t1j0osR9U0w&oh=00_AfO1pQBw0ozt5bfHZqeLmPD4h3rM4t9nKxYsvDMc2i5E6Q&oe=68573FCE"
    ];
    const menuImages = [
      "https://scontent.fmnl17-5.fna.fbcdn.net/v/t39.30808-6/500095638_693034116824587_6703998157941781571_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=110&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGTy6t0311sDOjs3B_ZrWyH1VnQrkMgptDVWdCuQyCm0GL_ld9PBi_S6BbxUNtG8I9yjVxaij9uO3KNd-feaGTt&_nc_ohc=IKX6xKrp0DcQ7kNvwHzPi06&_nc_oc=AdlE5jvDw92Ppr48247FRBf-T8eji8SEiE5aPZ8jZZ4CMOtc9Pzm6np2Gh1hOCZJS7w&_nc_zt=23&_nc_ht=scontent.fmnl17-5.fna&_nc_gid=Bi-lBtiSn2EF8gs618683w&oh=00_AfMZmSQSb9NUaYgqB8W5VSPVzwq260NBXkOwzym0lNpYGg&oe=6856DC4D",
      "https://scontent.fmnl17-1.fna.fbcdn.net/v/t39.30808-6/499991903_691645390296793_8426167903853957134_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHj0GzHLWfS8ydU3bXViT9I2DKtLbF8mW7YMq0tsXyZbltBNovJANt-QTowUde6Yk6ndiZfAus0SP_EbS_3-ykU&_nc_ohc=4Mjv5yGP7sgQ7kNvwEhmabo&_nc_oc=AdlC_Vys80aXCM3PGCLv37crGjIfzF4tDKHliS8TcbQ01gaV4DCr9Z0Z09EZgXNA_MA&_nc_zt=23&_nc_ht=scontent.fmnl17-1.fna&_nc_gid=b8qn_TRUOfit6a8euZmoig&oh=00_AfN-6h29emr8vX7gJr4Wohzhg5FakHy0erChykWw5LcLaA&oe=6857309F",
      "https://scontent.fmnl17-3.fna.fbcdn.net/v/t39.30808-6/493078995_669980759129923_1515208114196195832_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeEdh0r3eP_-3Lu0ebA77yNX6tUI3HwRYc3q1QjcfBFhzXYcV_GFL4xSwphxiJryknoKzAPGXkUkGN1gPtMdpyZE&_nc_ohc=7AsCRVFhV80Q7kNvwFDlXkE&_nc_oc=AdlAuhwcIXrXGg_zV3_2FCVcOcKSm7dvqIyyfhloWlTfrmmRZP0es-cBWbLjJDUmoi8&_nc_zt=23&_nc_ht=scontent.fmnl17-3.fna&_nc_gid=UabsAS4mo7jkKb3FjVYq0g&oh=00_AfNm51jjiwhsKxzo0zGnca7xIGhsDJ6d6Bq5A2PmRQ3qSA&oe=6857346A"
    ];
    const contactImages = [
      "https://scontent.fmnl17-2.fna.fbcdn.net/v/t39.30808-6/481769924_632646612863338_6450550044507221992_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHG8Qo8ULMGeOjUNGCQ5X9TISMkV1GUOEwhIyRXUZQ4TOM6Tww37MXgPwu4W8OapX_jdXhf-8l8Z5a8DJ4C8New&_nc_ohc=O_F_483MXAoQ7kNvwHzT7uE&_nc_oc=AdnF6Qv5qebDjFO00L66Tmt7crSQ5oUPoNuqYeNvQpmceUsk7bW7wquCI8m4yBGB2tE&_nc_zt=23&_nc_ht=scontent.fmnl17-2.fna&_nc_gid=bx0ZbWniLljDtluDUELNUw&oh=00_AfNvkJcQEa5h0WmDsF-bPPSHsLp6c9TmxcqB3Raq5HKjqw&oe=6856D6E8",
      "https://scontent.fmnl17-8.fna.fbcdn.net/v/t39.30808-6/501120747_692235020237830_9071057615230109929_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeFxiAc3RzC-RpEVWYG_YD5jDsnbmrxLd78OyduavEt3v23-WOU8ubSKCsr_gBlHbXsALhU2XPTaLZyGJvTGgIhp&_nc_ohc=DXnoph4er4EQ7kNvwEBvZvD&_nc_oc=AdlZkEwQy6hOY3bdE9j0lYkclgYdljImEVOalkzPtGWPvb62yFHaY-K9OaFmBqyAMAY&_nc_zt=23&_nc_ht=scontent.fmnl17-8.fna&_nc_gid=RpSSevY--oyTerMHKHcnrg&oh=00_AfM13ecSOvlelp88riiTf72iRkCS90Y5O9VuZvVY6eN2Lw&oe=68573C69",
      "https://scontent.fmnl17-6.fna.fbcdn.net/v/t39.30808-6/481152341_630413519753314_3151290502681093484_n.jpg?_nc_cat=109&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGV5BOhVWRTqfmVreBcRZXjk-e0Qrn7ZqyT57RCuftmrPrH9aeyNuBfn_FaAkYCtmGBKu57GFhrcwJ0zLpmCiE-&_nc_ohc=JLw9JbaNPgsQ7kNvwHIOqzh&_nc_oc=Adn0ACV3K4qFEQPNEF8mffiP2sgUcXjWpDGLSX6OfpGGfoiO9E3jAXdEnkJtYqaEqkQ&_nc_zt=23&_nc_ht=scontent.fmnl17-6.fna&_nc_gid=efsfizgBpOmec3MY64R_ew&oh=00_AfP556dwiFuKQ1nhp5no7ZA0o4o86TRx_ib0HsXYt0zkVw&oe=6857579A"
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
  </script>
</body>
</html>