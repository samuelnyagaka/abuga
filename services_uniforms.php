<?php
/**
 * School & Corporate Uniforms Service Page
 * Tailor Business Management & Marketing System
 */

$page_title = 'School & Corporate Uniform Production';
$page_desc = 'Contract uniform manufacturing for primary & secondary schools, corporate organizations, and security firms across Kenya. Anti-fade, high-durability fabrics.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-3">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold mb-3">Bulk Institutional Production</span>
        <h1 class="display-5 fw-bold">School, Corporate & Security Uniforms</h1>
        <p class="lead text-secondary mb-4">
          Engineered for severe daily use, consistent institutional branding, and 100% on-time opening day deliveries across Kenya.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-lg">
            <i class="bi bi-send-check me-2"></i> Request Bulk Tender Quotation
          </a>
          <a href="<?= e(whatsapp_url("Hello StitchCraft! I would like to discuss our school/company uniform requirements.")) ?>" target="_blank" class="btn btn-outline-light btn-lg">
            <i class="bi bi-whatsapp text-success me-2"></i> WhatsApp Procurement Desk
          </a>
        </div>
      </div>
      <div class="col-lg-4 d-none d-lg-block text-end">
        <div class="p-4 bg-secondary bg-opacity-25 rounded-4 border border-secondary border-opacity-50 text-start">
          <h6 class="text-warning fw-bold"><i class="bi bi-shield-check me-2"></i> Quality Guarantee</h6>
          <p class="small text-white-50 mb-0">Every batch undergoes rigorous seam tension, color-fastness, and shrink testing before delivery to your institution.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Uniform Categories Grid -->
<section class="py-5">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h6 class="text-primary fw-bold text-uppercase">Specialized Sectors</h6>
      <h2 class="h2 fw-bold">Built for Durability & Professional Identity</h2>
      <p class="text-muted">We supply tailored uniforms customized with your school crest, company logo, and color hierarchy.</p>
    </div>

    <div class="row g-4">
      
      <!-- School Uniforms -->
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper mb-3">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h4 class="card-title fw-bold">Primary & High Schools</h4>
            <p class="text-muted small">Comprehensive uniform sets manufactured for junior and senior schools.</p>
            <ul class="list-unstyled small text-secondary mb-4">
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Heavy poly-viscose anti-pill blazers</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Oxford long & short sleeve shirts</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Knife-pleated and box-pleated skirts</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Trousers with adjustable elastic growth waistbands</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Heavy knit V-neck school sweaters</li>
            </ul>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php?sector=school" class="btn btn-outline-primary btn-sm w-100">Inquire for School Tender</a>
          </div>
        </div>
      </div>

      <!-- Corporate Attire -->
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper mb-3">
              <i class="bi bi-briefcase-fill"></i>
            </div>
            <h4 class="card-title fw-bold">Corporate & Office Staff</h4>
            <p class="text-muted small">Sophisticated front-office apparel that projects authority, cohesion, and prestige.</p>
            <ul class="list-unstyled small text-secondary mb-4">
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Executive slim-cut single/double breasted blazers</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Custom embroidered corporate cotton shirts</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Coordinated tailored trousers & pencil skirts</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Premium branded polo shirts for informal Fridays</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Name badges and customized neckties/scarves</li>
            </ul>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php?sector=corporate" class="btn btn-outline-primary btn-sm w-100">Inquire for Corporate Staff</a>
          </div>
        </div>
      </div>

      <!-- Security & Workwear -->
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper mb-3">
              <i class="bi bi-shield-shaded"></i>
            </div>
            <h4 class="card-title fw-bold">Security, Medical & Industrial</h4>
            <p class="text-muted small">Reinforced functional workwear for safety officers, nurses, and logistics personnel.</p>
            <ul class="list-unstyled small text-secondary mb-4">
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Heavy ripstop drill security uniforms with epaulettes</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Hi-vis reflective safety vests and cargo jackets</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Antimicrobial hospital scrubs and lab coats</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Chef jackets and hospitality apron sets</li>
              <li class="mb-2"><i class="bi bi-check text-primary me-1"></i> Heavy gauge combat and industrial trousers</li>
            </ul>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php?sector=workwear" class="btn btn-outline-primary btn-sm w-100">Inquire for Workwear</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 5-Stage Institutional Procurement Process -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h6 class="text-primary fw-bold text-uppercase">How We Work With You</h6>
      <h2 class="h2 fw-bold">Our Seamless Bulk Delivery Process</h2>
      <p class="text-muted">Designed to take the headache out of large-volume uniform procurement.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-2 text-center">
        <div class="fs-1 fw-bold text-primary mb-2">01</div>
        <h6 class="fw-bold">Tender Review</h6>
        <p class="small text-muted">We evaluate your uniform specifications, sample garments, and estimated headcount.</p>
      </div>
      <div class="col-md-2 text-center">
        <div class="fs-1 fw-bold text-primary mb-2">02</div>
        <h6 class="fw-bold">Physical Swatches</h6>
        <p class="small text-muted">We provide physical fabric swatches and embroidered crest prototypes for board approval.</p>
      </div>
      <div class="col-md-3 text-center">
        <div class="fs-1 fw-bold text-primary mb-2">03</div>
        <h6 class="fw-bold">Sizing Breakdown</h6>
        <p class="small text-muted">We provide custom measurement forms or dispatch our fitting masters to your campus.</p>
      </div>
      <div class="col-md-3 text-center">
        <div class="fs-1 fw-bold text-primary mb-2">04</div>
        <h6 class="fw-bold">Batch Production</h6>
        <p class="small text-muted">High-speed precision automated cutting and reinforced double-needle stitching.</p>
      </div>
      <div class="col-md-2 text-center">
        <div class="fs-1 fw-bold text-primary mb-2">05</div>
        <h6 class="fw-bold">Staggered Delivery</h6>
        <p class="small text-muted">Packaged per student class or employee department with live tracking.</p>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-lg px-4">
        <i class="bi bi-file-earmark-check me-2"></i> Submit Your Institution's Uniform Needs
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
