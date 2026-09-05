<?php
/**
 * Public Site Footer Template
 * Tailor Business Management & Marketing System
 */

// Handle Newsletter Form Submission if posted to footer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_newsletter'])) {
    $newsletter_email = filter_var($_POST['newsletter_email'] ?? '', FILTER_VALIDATE_EMAIL);
    if ($newsletter_email) {
        // Insert into database
        $existing = db_fetch_one("SELECT id FROM newsletters WHERE email = ? LIMIT 1", [$newsletter_email]);
        if (!$existing) {
            db_insert('newsletters', [
                'email' => $newsletter_email,
                'source_page' => basename($_SERVER['PHP_SELF'])
            ]);
            // Optional Brevo welcome email
            send_brevo_email(
                $newsletter_email,
                'Subscriber',
                'Welcome to ' . BUSINESS_NAME . ' Updates',
                '<p>Thank you for subscribing to ' . BUSINESS_NAME . ' updates. We will share fabric care tips, school uniform order deadlines, and exclusive tailoring offers.</p>'
            );
            set_flash_message('success', 'Thank you for subscribing! Check your email for confirmation.');
        } else {
            set_flash_message('info', 'You are already on our priority mailing list.');
        }
    } else {
        set_flash_message('warning', 'Please enter a valid email address to subscribe.');
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>

  <!-- Floating WhatsApp Click-to-Chat Button -->
  <a href="<?= e(whatsapp_url("Hello StitchCraft! I would like to inquire about uniform prices and custom tailoring.")) ?>" 
     class="whatsapp-float shadow-lg" 
     target="_blank" 
     rel="noopener noreferrer" 
     title="Chat with Master Tailor on WhatsApp">
    <div class="whatsapp-pulse"></div>
    <i class="bi bi-whatsapp"></i>
  </a>

  <!-- Main Footer -->
  <footer class="footer-wrapper mt-5">
    <div class="container">
      <div class="row g-4 mb-5">
        
        <!-- Company Info -->
        <div class="col-lg-4 col-md-6">
          <div class="mb-3">
            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= e(BUSINESS_NAME) ?>" height="45" style="filter: brightness(0) invert(1);">
          </div>
          <p class="text-secondary small pe-lg-4">
            Specialist manufacturers and custom tailors in Nairobi. We supply durable, anti-fade school uniforms, sharp corporate identity apparel, and luxury bespoke menswear.
          </p>
          <div class="d-flex gap-3 mt-3">
            <a href="https://facebook.com" target="_blank" class="text-white fs-5" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="https://instagram.com" target="_blank" class="text-white fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="https://tiktok.com" target="_blank" class="text-white fs-5" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
            <a href="https://linkedin.com" target="_blank" class="text-white fs-5" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <!-- Uniform Solutions -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-title">Uniform Solutions</h6>
          <ul class="list-unstyled">
            <li><a href="<?= BASE_URL ?>/services_uniforms.php" class="footer-link">Primary & High School</a></li>
            <li><a href="<?= BASE_URL ?>/services_uniforms.php" class="footer-link">Embroidered Blazers</a></li>
            <li><a href="<?= BASE_URL ?>/services_uniforms.php" class="footer-link">Corporate Staff Shirts</a></li>
            <li><a href="<?= BASE_URL ?>/services_uniforms.php" class="footer-link">Security & Guard Uniforms</a></li>
            <li><a href="<?= BASE_URL ?>/services_uniforms.php" class="footer-link">Chef & Medical Scrubs</a></li>
            <li><a href="<?= BASE_URL ?>/bulk_inquiry.php" class="footer-link text-warning fw-semibold">Institutional Tender Form</a></li>
          </ul>
        </div>

        <!-- Custom & Quick Links -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-title">Bespoke & Tools</h6>
          <ul class="list-unstyled">
            <li><a href="<?= BASE_URL ?>/services_custom.php" class="footer-link">Bespoke Wedding Suits</a></li>
            <li><a href="<?= BASE_URL ?>/quote_request.php" class="footer-link">Individual Quote Form</a></li>
            <li><a href="<?= BASE_URL ?>/portfolio.php" class="footer-link">Garment Gallery</a></li>
            <li><a href="<?= BASE_URL ?>/articles.php" class="footer-link">Fitting & Sizing Guides</a></li>
            <li><a href="<?= BASE_URL ?>/track_order.php" class="footer-link text-info">Track Order Status</a></li>
            <li><a href="<?= BASE_URL ?>/admin/login.php" class="footer-link text-secondary" style="font-size: 0.8rem;">Staff Portal Login</a></li>
          </ul>
        </div>

        <!-- Newsletter / Brevo Capture -->
        <div class="col-lg-4 col-md-6">
          <h6 class="footer-title">Stay Updated on Uniform Tenders</h6>
          <p class="text-secondary small">Get seasonal fabric discount updates, school calendar ordering deadlines, and fitting tips.</p>
          <form method="POST" action="<?= BASE_URL ?>/includes/footer.php" class="mb-3">
            <input type="hidden" name="action_newsletter" value="1">
            <div class="input-group">
              <input type="email" name="newsletter_email" class="form-control form-control-sm" placeholder="Your work/school email" required>
              <button class="btn btn-gold btn-sm px-3" type="submit">Subscribe</button>
            </div>
          </form>
          <div class="small text-secondary">
            <i class="bi bi-shield-check text-success me-1"></i> No spam. Instant unsubscribe at any time.
          </div>
        </div>

      </div>

      <hr style="border-color: rgba(255, 255, 255, 0.1);">

      <div class="row pt-2 align-items-center">
        <div class="col-md-6 text-center text-md-start small text-secondary">
          &copy; <?= date('Y') ?> <?= e(BUSINESS_NAME) ?>. All rights reserved. Registered in Kenya.
        </div>
        <div class="col-md-6 text-center text-md-end small text-secondary mt-2 mt-md-0">
          Precision Made in Nairobi, Kenya | <a href="<?= BASE_URL ?>/contact.php" class="text-secondary text-decoration-none">Terms & Workshop Visit</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
  <!-- Custom Application JavaScript with Cache-Busting -->
  <script src="<?= asset_url('assets/js/main.js') ?>"></script>
</body>
</html>
