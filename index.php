<?php
/**
 * Home Page - StitchCraft Masters
 * Tailor Business Management & Marketing System
 */

$page_title = 'Uniform Production & Bespoke Tailoring Nairobi';
$page_desc = 'Specialist tailoring workshop in Nairobi producing high-durability school uniforms, corporate blazers, security apparel, and handcrafted custom suits.';

require_once __DIR__ . '/includes/header.php';

// Fetch featured gallery items from database
$featured_gallery = db_fetch_all("SELECT * FROM gallery WHERE is_featured = 1 ORDER BY sort_order ASC LIMIT 6");

// Fetch approved testimonials
$testimonials = db_fetch_all("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY sort_order ASC LIMIT 3");

// Fetch latest 2 SEO articles
$recent_articles = db_fetch_all("SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 2");
?>

<!-- Hero Section -->
<section class="hero-wrapper">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="badge-hero">
          <i class="bi bi-patch-check-fill text-warning"></i> Contract Tailor for 120+ Kenyan Schools & Corporates
        </div>
        <h1 class="hero-title">
          Durable School Uniforms & <span class="text-gold">Precision Bespoke</span> Tailoring.
        </h1>
        <p class="hero-subtitle">
          From full institutional uniform tenders (with guaranteed anti-fade fabrics and student size breakdowns) to handcrafted Italian wool suits tailored for your most critical milestones.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-lg">
            <i class="bi bi-building-check me-2"></i> Request Bulk Uniform Tender
          </a>
          <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-outline-light-custom btn-lg">
            <i class="bi bi-scissors me-2"></i> Custom Bespoke Quote
          </a>
          <?php if (!is_customer_logged_in() && !is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-light btn-lg text-dark fw-semibold d-inline-flex align-items-center">
              <i class="bi bi-person-circle me-2 text-primary"></i> Client Portal Login
            </a>
          <?php else: ?>
            <a href="<?= is_customer_logged_in() ? BASE_URL . '/my_account.php' : BASE_URL . '/admin/dashboard.php' ?>" class="btn btn-light btn-lg text-dark fw-semibold d-inline-flex align-items-center">
              <i class="bi bi-speedometer2 me-2 text-primary"></i> Go to My Portal
            </a>
          <?php endif; ?>
        </div>
        <div class="mt-4 pt-2 text-white-50 small d-flex flex-wrap gap-4 align-items-center">
          <span><i class="bi bi-shield-lock text-warning me-1"></i> Color-Fast Fabrics</span>
          <span><i class="bi bi-rulers text-warning me-1"></i> Exact Sizing Matrix</span>
          <span><i class="bi bi-truck text-warning me-1"></i> Countrywide Delivery</span>
        </div>
      </div>
      
      <div class="col-lg-5 mt-5 mt-lg-0">
        <!-- Interactive Instant Quote Estimator Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(10px);">
          <div class="card-header bg-primary text-white py-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-calculator me-2"></i> Quick Bulk Price Estimator</h5>
              <span class="badge bg-warning text-dark">Instant</span>
            </div>
          </div>
          <div class="card-body p-4 text-dark">
            <p class="small text-muted mb-3">Calculate approximate tier pricing for bulk school or corporate orders:</p>
            <div class="mb-3">
              <label class="form-label small fw-bold">Select Garment Type</label>
              <select id="calc_garment_type" class="form-select">
                <option value="blazer" data-base-price="3200">High School / Corporate Blazer (Poly-Viscose)</option>
                <option value="shirt" data-base-price="1200">Oxford Cotton Shirt / Blouse (Embroidered)</option>
                <option value="skirt" data-base-price="1800">Box-Pleated Uniform Skirt / Trouser</option>
                <option value="tracksuit" data-base-price="2600">Fleece Lined Sports Tracksuit</option>
                <option value="security" data-base-price="2900">Security Guard Heavy Drill Uniform</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Estimated Quantity (Units)</label>
              <input type="number" id="calc_quantity" class="form-control" value="100" min="10" step="10">
              <div class="form-text small">Discounts: 5% (50+ pcs), 10% (200+ pcs), 15% (500+ pcs).</div>
            </div>
            <div class="p-3 bg-light rounded-3 text-center mb-3">
              <div class="small text-muted">Estimated Investment:</div>
              <div id="calc_estimate_output" class="fs-4 fw-bold text-primary">KSh 288,000 (Includes 10% bulk tier savings)</div>
            </div>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-primary w-100 fw-semibold">
              Lock in This Tender Rate <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Counter Bar -->
<div class="container">
  <div class="stats-bar">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3 border-end-md">
        <div class="stat-number">45,000+</div>
        <div class="stat-label">Uniform Sets Delivered</div>
      </div>
      <div class="col-6 col-md-3 border-end-md">
        <div class="stat-number">120+</div>
        <div class="stat-label">Schools & Corporates</div>
      </div>
      <div class="col-6 col-md-3 border-end-md">
        <div class="stat-number">100%</div>
        <div class="stat-label">On-Time Opening Day Record</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-number">14+</div>
        <div class="stat-label">Master Tailors & Cutters</div>
      </div>
    </div>
  </div>
</div>

<!-- Core Offerings Section -->
<section class="py-5 mt-4">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h6 class="text-primary fw-bold text-uppercase tracking-wider">What We Produce</h6>
      <h2 class="display-6 fw-bold">Two Specialized Divisions, One Standard of Excellence</h2>
      <p class="text-muted">Whether manufacturing hundreds of uniforms for a leading academy or creating a single bespoke suit for your wedding day, our patternmakers deliver uncompromising quality.</p>
    </div>

    <div class="row g-4">
      <!-- Division 1: Institutional Uniforms -->
      <div class="col-lg-6">
        <div class="feature-card">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="feature-icon-wrapper">
              <i class="bi bi-building-fill-check"></i>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">Schools & Corporate</span>
          </div>
          <h3 class="h4 fw-bold">Institutional Uniform Tenders</h3>
          <p class="text-muted">
            Engineered specifically for durability under heavy use and frequent industrial washing. We supply turn-key solutions with structured partial delivery schedules.
          </p>
          <ul class="list-unstyled mb-4">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Anti-Pill & Color-Fast:</strong> Premium Rivatex & imported poly-viscose that won't fade after one term.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Crest Embroidery:</strong> High-definition Japanese digitized metallic thread badges.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Student Growth Inlays:</strong> Inner seam allowances parents can adjust as children grow.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Batch Delivery Tracking:</strong> Live dispatch updates with digital receipts.</li>
          </ul>
          <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/services_uniforms.php" class="btn btn-outline-primary">Explore Uniforms</a>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-primary">Submit Tender Inquiry</a>
          </div>
        </div>
      </div>

      <!-- Division 2: Bespoke Custom Tailoring -->
      <div class="col-lg-6">
        <div class="feature-card">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="feature-icon-wrapper feature-icon-gold">
              <i class="bi bi-scissors"></i>
            </div>
            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill fw-semibold">Bespoke & Menswear</span>
          </div>
          <h3 class="h4 fw-bold">Handcrafted Custom Tailoring</h3>
          <p class="text-muted">
            Individual pattern cutting tailored to your unique anatomical posture. From Italian wool wedding tuxedos to executive boardroom power suits.
          </p>
          <ul class="list-unstyled mb-4">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> <strong>Super 120s - 150s Pure Wool:</strong> Breathable, crease-resistant luxury fabrics.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> <strong>Full Canvas Interior:</strong> Hand-stitched chest horsehair canvas that molds to your body.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> <strong>Two Fitting Stages:</strong> Initial basted fitting plus final adjustments for 100% precision.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i> <strong>Custom Personalization:</strong> Monogrammed initials, surgeon cuffs, and custom linings.</li>
          </ul>
          <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/services_custom.php" class="btn btn-outline-secondary">View Bespoke Options</a>
            <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-gold">Request Suit Fitting</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Portfolio Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
      <div>
        <h6 class="text-primary fw-bold text-uppercase tracking-wider">Craftsmanship Showcase</h6>
        <h2 class="h2 fw-bold mb-1">Recent Production Highlights</h2>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/portfolio.php" class="btn btn-outline-primary">View Complete Gallery <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
    </div>

    <div class="row g-4">
      <?php if (!empty($featured_gallery)): ?>
        <?php foreach ($featured_gallery as $item): ?>
          <div class="col-md-6 col-lg-4">
            <div class="gallery-card" data-bs-toggle="modal" data-bs-target="#lightboxModal" 
                 data-img="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" 
                 data-title="<?= e($item['title']) ?>" 
                 data-desc="<?= e($item['description']) ?>">
              <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" 
                   onerror="this.src='https://placehold.co/600x450/0f172a/white?text=<?= urlencode($item['title']) ?>'">
              <div class="gallery-overlay">
                <span class="badge bg-gold text-white align-self-start mb-2" style="background:#d97706;"><?= e(ucwords(str_replace('_', ' ', $item['category']))) ?></span>
                <h5 class="fw-bold mb-1 text-white"><?= e($item['title']) ?></h5>
                <p class="small text-white-50 mb-0"><?= e($item['description']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-4">No gallery items loaded.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h6 class="text-primary fw-bold text-uppercase tracking-wider">Client Trust</h6>
      <h2 class="display-6 fw-bold">Recommended by Institutions & Executives</h2>
    </div>

    <div class="row g-4">
      <?php foreach ($testimonials as $testimonial): ?>
        <div class="col-md-4">
          <div class="testimonial-card">
            <div>
              <div class="rating-stars">
                <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                  <i class="bi bi-star-fill"></i>
                <?php endfor; ?>
              </div>
              <p class="fst-italic text-secondary">
                "<?= e($testimonial['quote_text']) ?>"
              </p>
            </div>
            <div class="pt-3 border-top mt-3">
              <h6 class="fw-bold mb-0 text-dark"><?= e($testimonial['author_name']) ?></h6>
              <div class="small text-muted"><?= e($testimonial['organization_title']) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);">
  <div class="container text-center py-4">
    <h2 class="display-6 fw-bold mb-3">Planning an Upcoming Uniform Tender or Milestone?</h2>
    <p class="lead text-white-50 max-w-700 mx-auto mb-4">
      Our team visits school boards and corporate headquarters in Nairobi for fabric sample demonstrations, sizing matrix reviews, and official quotation submissions.
    </p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-lg px-4">
        <i class="bi bi-file-earmark-text me-2"></i> Submit Tender Specifications
      </a>
      <a href="<?= e(whatsapp_url("Hello StitchCraft! I would like to schedule a uniform sample viewing meeting.")) ?>" target="_blank" class="btn btn-success btn-lg px-4">
        <i class="bi bi-whatsapp me-2"></i> Instant WhatsApp Consultation
      </a>
    </div>
  </div>
</section>

<!-- Lightbox Modal for Gallery Images -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-labelledby="lightboxTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-white border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fs-6 text-white-50" id="lightboxTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-2">
        <img id="lightboxImage" src="" class="img-fluid rounded" alt="Garment sample" style="max-height: 75vh; object-fit: contain;">
        <p id="lightboxDesc" class="mt-3 text-secondary small px-3 text-center"></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
