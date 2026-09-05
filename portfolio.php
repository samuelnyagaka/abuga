<?php
/**
 * Gallery / Portfolio Showcase
 * Tailor Business Management & Marketing System
 */

$page_title = 'Garment Portfolio & Craftsmanship Showcase';
$page_desc = 'Browse our extensive portfolio of manufactured school uniforms, executive corporate attire, bespoke wedding suits, and protective workwear.';

require_once __DIR__ . '/includes/header.php';

// Fetch all gallery items from database
$gallery_items = db_fetch_all("SELECT * FROM gallery ORDER BY sort_order ASC, id DESC");
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-gold text-white px-3 py-2 rounded-pill fw-semibold mb-2" style="background: #d97706;">Our Craft in Action</span>
    <h1 class="display-5 fw-bold">Garment Portfolio & Finished Work</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      Every stitch represents our dedication to structural integrity, fabric endurance, and aesthetic harmony.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    
    <!-- Filter Pills -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
      <button class="btn btn-primary gallery-filter-btn active" data-filter="all">All Garments</button>
      <button class="btn btn-outline-secondary gallery-filter-btn" data-filter="school_uniforms">School Uniforms</button>
      <button class="btn btn-outline-secondary gallery-filter-btn" data-filter="corporate_uniforms">Corporate Attire</button>
      <button class="btn btn-outline-secondary gallery-filter-btn" data-filter="security_medical">Security & Workwear</button>
      <button class="btn btn-outline-secondary gallery-filter-btn" data-filter="suits_blazers">Bespoke Suits</button>
      <button class="btn btn-outline-secondary gallery-filter-btn" data-filter="dresses_traditional">African & Ceremonial</button>
    </div>

    <!-- Gallery Grid -->
    <div class="row g-4" id="galleryContainer">
      <?php if (!empty($gallery_items)): ?>
        <?php foreach ($gallery_items as $item): ?>
          <div class="col-md-6 col-lg-4 gallery-item-col" data-category="<?= e($item['category']) ?>">
            <div class="gallery-card" data-bs-toggle="modal" data-bs-target="#lightboxModal"
                 data-img="<?= BASE_URL ?>/<?= e($item['image_path']) ?>"
                 data-title="<?= e($item['title']) ?>"
                 data-desc="<?= e($item['description']) ?>">
              <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>"
                   onerror="this.src='https://placehold.co/600x450/0f172a/white?text=<?= urlencode($item['title']) ?>'">
              <div class="gallery-overlay">
                <span class="badge bg-gold text-white align-self-start mb-2" style="background: #d97706;"><?= e(ucwords(str_replace('_', ' ', $item['category']))) ?></span>
                <h5 class="fw-bold mb-1 text-white"><?= e($item['title']) ?></h5>
                <p class="small text-white-50 mb-2"><?= e($item['description']) ?></p>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-white-50">
                  <span class="small text-warning"><i class="bi bi-zoom-in me-1"></i> Click to Zoom</span>
                  <a href="<?= e(whatsapp_url("Hello! I saw your portfolio photo of '" . $item['title'] . "' and would like to order something similar.")) ?>" 
                     target="_blank" 
                     class="btn btn-xs btn-success text-white py-1 px-2" 
                     style="font-size: 0.75rem;" 
                     onclick="event.stopPropagation();">
                    <i class="bi bi-whatsapp me-1"></i> Inquire
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-5">
          <h5>No portfolio items loaded yet.</h5>
          <p>Please check back soon as our master cutters update our catalog.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Banner to Tender Form -->
    <div class="mt-5 p-4 p-md-5 rounded-4 bg-light border text-center">
      <h3 class="fw-bold mb-2">Need Custom Designs for Your Institution?</h3>
      <p class="text-muted max-w-700 mx-auto mb-4">We can digitize your crest, produce physical prototypes in your school colors, and prepare tender sample boards.</p>
      <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-primary btn-lg">
        <i class="bi bi-file-earmark-plus me-2"></i> Submit Custom Tender Request
      </a>
    </div>

  </div>
</section>

<!-- Lightbox Modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-labelledby="lightboxTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-white border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fs-6 text-white-50" id="lightboxTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-3">
        <img id="lightboxImage" src="" class="img-fluid rounded shadow" alt="Garment item" style="max-height: 75vh; object-fit: contain;">
        <p id="lightboxDesc" class="mt-3 text-secondary small px-3 text-center"></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
