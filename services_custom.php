<?php
/**
 * Bespoke & Custom Clothing Service Page
 * Tailor Business Management & Marketing System
 */

$page_title = 'Bespoke Suits & Handcrafted Custom Tailoring';
$page_desc = 'Master tailoring in Nairobi for bespoke wedding suits, executive boardroom blazers, traditional ceremonial wear, and precision anatomical garments.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #090e17 0%, #1e293b 100%);">
  <div class="container py-3">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">Individual Bespoke Craft</span>
        <h1 class="display-5 fw-bold">Bespoke Suits & Custom Tailoring</h1>
        <p class="lead text-secondary mb-4">
          Handcrafted garments sculpted to your unique anatomical posture using luxury Italian and British worsted wools, cashmere blends, and bespoke linings.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-gold btn-lg">
            <i class="bi bi-scissors me-2"></i> Book a Fitting & Quote
          </a>
          <a href="<?= e(whatsapp_url("Hello StitchCraft! I would like to book an appointment for a bespoke suit fitting.")) ?>" target="_blank" class="btn btn-outline-light btn-lg">
            <i class="bi bi-whatsapp text-success me-2"></i> WhatsApp Master Cutter
          </a>
        </div>
      </div>
      <div class="col-lg-4 d-none d-lg-block text-end">
        <div class="p-4 bg-secondary bg-opacity-25 rounded-4 border border-secondary border-opacity-50 text-start">
          <h6 class="text-warning fw-bold"><i class="bi bi-gem me-2"></i> Zero Off-The-Rack Compromises</h6>
          <p class="small text-white-50 mb-0">Every jacket pattern is drawn from scratch on brown paper, customized for your exact shoulder slope, chest arch, and sleeve pitch.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Offerings Grid -->
<section class="py-5">
  <div class="container">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h6 class="text-primary fw-bold text-uppercase">Bespoke Divisions</h6>
      <h2 class="h2 fw-bold">Individual Tailoring Tailored for Distinction</h2>
      <p class="text-muted">Explore our signature handcrafted categories for weddings, boardrooms, and ceremonial milestones.</p>
    </div>

    <div class="row g-4">
      
      <!-- 1. Executive Boardroom Suits -->
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper feature-icon-gold mb-3">
              <i class="bi bi-person-workspace"></i>
            </div>
            <h5 class="fw-bold">Executive Boardroom</h5>
            <p class="small text-muted">Power suits that command respect. Super 120s to 150s worsted wool with breathable natural stretch.</p>
            <ul class="list-unstyled small text-secondary">
              <li>&bull; 2-Piece & 3-Piece options</li>
              <li>&bull; Hand-stitched lapel edges</li>
              <li>&bull; Functional surgeon sleeve cuffs</li>
              <li>&bull; Personalized inside monogram</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- 2. Wedding & Tuxedos -->
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper feature-icon-gold mb-3">
              <i class="bi bi-heart-fill"></i>
            </div>
            <h5 class="fw-bold">Wedding & Black Tie</h5>
            <p class="small text-muted">Unforgettable groom tuxedos, shawl collar dinner jackets, and coordinated groomsmen packages.</p>
            <ul class="list-unstyled small text-secondary">
              <li>&bull; Satin & silk grosgrain lapels</li>
              <li>&bull; Horseshoe tuxedo vests</li>
              <li>&bull; Groomsmen group fitting service</li>
              <li>&bull; Guaranteed wedding delivery dates</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- 3. Contemporary African -->
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper feature-icon-gold mb-3">
              <i class="bi bi-stars"></i>
            </div>
            <h5 class="fw-bold">Modern African Elegance</h5>
            <p class="small text-muted">Sophisticated African ceremonial wear blending classic tailoring silhouettes with premium traditional textiles.</p>
            <ul class="list-unstyled small text-secondary">
              <li>&bull; Modern embroidered Agbada</li>
              <li>&bull; Mandarin & Nehru collar suits</li>
              <li>&bull; Custom Ankara print accents</li>
              <li>&bull; Cultural engagement attire</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- 4. Wardrobe Restyling & Alterations -->
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="feature-icon-wrapper feature-icon-gold mb-3">
              <i class="bi bi-magic"></i>
            </div>
            <h5 class="fw-bold">Precision Alterations</h5>
            <p class="small text-muted">Breathe new life into high-value garments with master-level restructuring and nip-and-tuck tailoring.</p>
            <ul class="list-unstyled small text-secondary">
              <li>&bull; Jacket shoulder tapering</li>
              <li>&bull; Trouser hem cuffing & tapering</li>
              <li>&bull; Waist take-in & let-out</li>
              <li>&bull; Full lining replacement</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- The Bespoke Experience -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h6 class="text-primary fw-bold text-uppercase">The Bespoke Journey</h6>
        <h2 class="display-6 fw-bold mb-4">How Your Masterpiece Takes Shape</h2>
        
        <div class="d-flex gap-3 mb-4">
          <div class="fw-bold fs-4 text-warning">01.</div>
          <div>
            <h5 class="fw-bold mb-1">Style Consultation & Fabric Selection</h5>
            <p class="small text-muted">Select from hundreds of Italian, English, and local swatches. Discuss lapel widths, lining patterns, button materials, and pocket styles.</p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <div class="fw-bold fs-4 text-warning">02.</div>
          <div>
            <h5 class="fw-bold mb-1">28-Point Anatomical Measurement</h5>
            <p class="small text-muted">We record 28 distinct measurements, documenting posture, shoulder pitch, arm reach, and neck incline.</p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <div class="fw-bold fs-4 text-warning">03.</div>
          <div>
            <h5 class="fw-bold mb-1">The Basted Fitting</h5>
            <p class="small text-muted">You try on a preliminary skeleton suit sewn with white basting thread. The cutter pins and sculpts the garment live on your body.</p>
          </div>
        </div>

        <div class="d-flex gap-3">
          <div class="fw-bold fs-4 text-warning">04.</div>
          <div>
            <h5 class="fw-bold mb-1">Final Hand Finish & Pressing</h5>
            <p class="small text-muted">Buttonholes are cut and sewn by hand, linings set, and the suit is shaped with heavy industrial steam irons.</p>
          </div>
        </div>

      </div>

      <div class="col-lg-6 mt-5 mt-lg-0 text-center">
        <div class="p-5 bg-white rounded-4 shadow-sm border">
          <div class="display-4 text-warning mb-3"><i class="bi bi-patch-check"></i></div>
          <h4 class="fw-bold mb-2">Ready to Upgrade Your Style?</h4>
          <p class="text-muted mb-4">Book your consultation at our Moi Avenue showroom or request our traveling tailor service for VIP home/office fitting.</p>
          <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-gold btn-lg w-100 mb-2">
            Request Custom Suit Quote
          </a>
          <div class="small text-muted mt-3">Fitting turnaround: 10 - 18 days depending on fabric choice.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
