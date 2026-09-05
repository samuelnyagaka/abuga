<?php
/**
 * Public Site Header Template
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$page_title = isset($page_title) ? $page_title . ' | ' . BUSINESS_NAME : BUSINESS_NAME . ' | School Uniforms & Bespoke Tailoring Nairobi';
$page_desc = isset($page_desc) ? $page_desc : 'Leading tailoring workshop in Nairobi, Kenya specializing in durable school uniforms, corporate office attire, security workwear, and bespoke custom suits.';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_desc) ?>">
  <meta name="keywords" content="school uniforms Kenya, corporate blazers Nairobi, custom suits Kenya, bulk uniform tailoring, school blazers, bespoke tailoring Nairobi">
  <meta name="author" content="<?= e(BUSINESS_NAME) ?>">

  <!-- OpenGraph Metadata for WhatsApp & Facebook Sharing -->
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_desc) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= BASE_URL . '/' . ltrim($current_page, '/') ?>">
  <meta property="og:image" content="<?= BASE_URL ?>/assets/images/logo.svg">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">

  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom Design System CSS with Cache-Busting -->
  <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">

  <!-- Schema.org Structured Data for Local Business SEO -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ClothingStore",
    "name": "<?= e(BUSINESS_NAME) ?>",
    "description": "<?= e($page_desc) ?>",
    "url": "<?= BASE_URL ?>",
    "telephone": "<?= e(BUSINESS_PHONE) ?>",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Biashara Plaza, Moi Avenue",
      "addressLocality": "Nairobi",
      "addressCountry": "KE"
    },
    "priceRange": "KSh 1,000 - 500,000",
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        "opens": "08:00",
        "closes": "18:00"
      }
    ]
  }
  </script>
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="bg-dark text-white py-1 px-3 d-none d-md-block" style="font-size: 0.82rem; background-color: #0b1120 !important;">
    <div class="container d-flex justify-content-between align-items-center">
      <div>
        <i class="bi bi-geo-alt text-warning me-1"></i> <?= e(BUSINESS_ADDRESS) ?>
        <span class="mx-2 text-secondary">|</span>
        <i class="bi bi-clock text-warning me-1"></i> <?= e(BUSINESS_HOURS) ?>
      </div>
      <div class="d-flex align-items-center gap-3">
        <a href="<?= e(whatsapp_url("Hello! I would like to inquire about uniforms and tailoring services.")) ?>" target="_blank" class="text-white text-decoration-none hover-gold">
          <i class="bi bi-whatsapp text-success me-1"></i> WhatsApp: <?= e(BUSINESS_PHONE) ?>
        </a>
        <span class="text-secondary">|</span>
        <a href="<?= BASE_URL ?>/track_order.php" class="text-warning text-decoration-none">
          <i class="bi bi-truck me-1"></i> Track Order
        </a>
        <span class="text-secondary">|</span>
        <?php if (is_customer_logged_in()): ?>
          <a href="<?= BASE_URL ?>/my_account.php" class="text-warning text-decoration-none fw-semibold">
            <i class="bi bi-person-check-fill me-1"></i> My Portal (<?= e(explode(' ', get_logged_in_customer()['name'])[0]) ?>)
          </a>
        <?php elseif (is_logged_in()): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-warning text-decoration-none fw-semibold">
            <i class="bi bi-speedometer2 me-1"></i> Admin Portal
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="text-white text-decoration-none hover-gold">
            <i class="bi bi-box-arrow-in-right text-warning me-1"></i> Portal Sign In
          </a>
          <span class="text-secondary">|</span>
          <a href="<?= BASE_URL ?>/register.php" class="text-white text-decoration-none hover-gold">
            <i class="bi bi-person-plus text-warning me-1"></i> Register
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
      <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= e(BUSINESS_NAME) ?>" height="42">
      </a>
      
      <button class="navbar-toggler border-0" type="button" data-bs-dismiss="offcanvas" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mb-2 mb-lg-0">
          <li class="nav-link-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
          </li>
          <li class="nav-link-item">
            <a class="nav-link <?= $current_page == 'services_uniforms.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/services_uniforms.php">
              Uniforms <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.68rem;">Bulk</span>
            </a>
          </li>
          <li class="nav-link-item">
            <a class="nav-link <?= $current_page == 'services_custom.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/services_custom.php">Bespoke Suits & Custom</a>
          </li>
          <li class="nav-link-item">
            <a class="nav-link <?= $current_page == 'portfolio.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/portfolio.php">Gallery</a>
          </li>
          <li class="nav-link-item">
            <a class="nav-link <?= in_array($current_page, ['articles.php', 'article_view.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>/articles.php">Guides & Blog</a>
          </li>
          <li class="nav-link-item">
            <a class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">Contact</a>
          </li>

          <!-- Mobile Only Authentication Links -->
          <li class="nav-link-item d-lg-none border-top pt-2 mt-2">
            <?php if (is_customer_logged_in()): ?>
              <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/my_account.php">
                <i class="bi bi-speedometer2 me-1"></i> My Customer Portal (<?= e(get_logged_in_customer()['name']) ?>)
              </a>
              <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
              </a>
            <?php elseif (is_logged_in()): ?>
              <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
              </a>
              <a class="nav-link text-danger" href="<?= BASE_URL ?>/admin/logout.php">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
              </a>
            <?php else: ?>
              <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/login.php">
                <i class="bi bi-person-circle me-1"></i> Customer & Staff Login
              </a>
              <a class="nav-link text-muted" href="<?= BASE_URL ?>/register.php">
                <i class="bi bi-person-plus me-1"></i> Register Customer Account
              </a>
            <?php endif; ?>
          </li>

          <!-- Desktop Actions & Dynamic Auth Dropdown -->
          <li class="nav-item ms-lg-3 mt-2 mt-lg-0 d-flex flex-wrap align-items-center gap-2">
            <?php if (is_customer_logged_in()): ?>
              <?php $cust = get_logged_in_customer(); ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="rounded-circle bg-white text-primary d-inline-flex align-items-center justify-content-center fw-bold" style="width: 22px; height: 22px; font-size: 0.72rem;">
                    <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                  </span>
                  <span><?= e(explode(' ', $cust['name'])[0]) ?></span>
                  <span class="badge <?= $cust['type'] === 'institution' ? 'bg-info text-dark' : 'bg-warning text-dark' ?>" style="font-size:0.65rem;">
                    <?= $cust['type'] === 'institution' ? 'Institution' : 'Client' ?>
                  </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  <li><h6 class="dropdown-header small">Signed in as <strong><?= e($cust['name']) ?></strong></h6></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/my_account.php"><i class="bi bi-speedometer2 me-2"></i> My Account & Orders</a></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/my_account.php#measurements"><i class="bi bi-rulers me-2"></i> Saved Measurements</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/quote_request.php"><i class="bi bi-scissors me-2"></i> Custom Quote</a></li>
                  <?php if ($cust['type'] === 'institution'): ?>
                    <li><a class="dropdown-item small" href="<?= BASE_URL ?>/bulk_inquiry.php"><i class="bi bi-buildings me-2"></i> Bulk Uniform Tender</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item small text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                </ul>
              </div>
            <?php elseif (is_logged_in()): ?>
              <?php $st = get_current_user_data(); ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-dark dropdown-toggle px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-shield-lock-fill text-warning"></i>
                  <span><?= e($st['username']) ?></span>
                  <span class="badge bg-secondary" style="font-size:0.65rem;"><?= ucfirst($st['role']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  <li><h6 class="dropdown-header small">Staff Console</h6></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</a></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/admin/orders.php"><i class="bi bi-bag-check me-2"></i> Manage Orders</a></li>
                  <li><a class="dropdown-item small" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item small text-danger" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                </ul>
              </div>
            <?php else: ?>
              <!-- Prominent Login Button for Customers & Staff -->
              <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline-dark px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1 rounded-3">
                <i class="bi bi-person-circle text-primary"></i> Login
              </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-sm btn-outline-primary px-3 py-2 fw-semibold d-none d-sm-inline-block">
              Custom Quote
            </a>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-sm btn-gold px-3 py-2 fw-semibold">
              Bulk Uniforms
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Flash Messages container -->
  <div class="container mt-3">
    <?= display_flash_messages() ?>
  </div>
