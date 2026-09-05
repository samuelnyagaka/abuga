<?php
/**
 * Centralized Measurement Profiles Manager
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Measurement Profiles';
require_once __DIR__ . '/admin_header.php';

$search = sanitize($_GET['q'] ?? '');

$sql = "
    SELECT m.*, c.name as customer_name, c.institution_name, c.phone 
    FROM measurement_profiles m 
    JOIN customers c ON m.customer_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.profile_name LIKE ? OR c.name LIKE ? OR c.institution_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY m.id DESC";
$profiles = db_fetch_all($sql, $params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Central Measurement Profiles</h4>
    <p class="text-muted small mb-0">Look up student size patterns, customer fitting specs, and anatomical dimensions.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/customers.php" class="btn btn-primary btn-sm">
    <i class="bi bi-person-plus me-1"></i> Customer Directory
  </a>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= BASE_URL ?>/admin/measurements.php" class="row g-2 align-items-center">
      <div class="col-md-10">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search by profile name, customer, or school..." value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Search</button>
      </div>
    </form>
  </div>
</div>

<!-- Profiles Grid -->
<div class="row g-3">
  <?php if (!empty($profiles)): ?>
    <?php foreach ($profiles as $m): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-primary-subtle text-primary text-capitalize"><?= e($m['gender']) ?></span>
                <span class="small text-muted"><?= format_date($m['created_at']) ?></span>
              </div>
              <h5 class="fw-bold mb-1 text-dark"><?= e($m['profile_name']) ?></h5>
              <div class="small text-muted mb-3">
                <a href="<?= BASE_URL ?>/admin/customer_view.php?id=<?= $m['customer_id'] ?>" class="text-decoration-none fw-semibold">
                  <i class="bi bi-person me-1"></i> <?= e($m['institution_name'] ?: $m['customer_name']) ?>
                </a>
              </div>

              <!-- Measurement Highlights -->
              <div class="p-3 bg-light rounded-3 small text-secondary mb-3 border">
                <div class="row g-1">
                  <?php if ($m['chest_bust']): ?><div class="col-6">Chest/Bust: <strong><?= $m['chest_bust'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['waist']): ?><div class="col-6">Waist: <strong><?= $m['waist'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['shoulder']): ?><div class="col-6">Shoulder: <strong><?= $m['shoulder'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['sleeve_length']): ?><div class="col-6">Sleeve: <strong><?= $m['sleeve_length'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['shirt_length']): ?><div class="col-6">Jacket/Shirt: <strong><?= $m['shirt_length'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['trouser_length']): ?><div class="col-6">Trouser Len: <strong><?= $m['trouser_length'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['inseam']): ?><div class="col-6">Inseam: <strong><?= $m['inseam'] ?>"</strong></div><?php endif; ?>
                  <?php if ($m['neck']): ?><div class="col-6">Neck: <strong><?= $m['neck'] ?>"</strong></div><?php endif; ?>
                </div>
              </div>

              <?php if (!empty($m['custom_specs'])): ?>
                <div class="small text-muted mb-3 fst-italic">
                  "<?= e($m['custom_specs']) ?>"
                </div>
              <?php endif; ?>
            </div>

            <div class="pt-2 border-top">
              <a href="<?= BASE_URL ?>/admin/customer_view.php?id=<?= $m['customer_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                View Full Customer Account
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12 text-center text-muted py-5">
      No measurement profiles found.
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
