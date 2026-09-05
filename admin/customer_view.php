<?php
/**
 * Customer Profile & Measurements History
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Customer Profile';
require_once __DIR__ . '/admin_header.php';

$customer_id = (int)($_GET['id'] ?? 0);
if (!$customer_id) {
    header("Location: " . BASE_URL . "/admin/customers.php");
    exit;
}

$customer = db_fetch_one("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customer_id]);
if (!$customer) {
    set_flash_message('danger', 'Customer record not found.');
    header("Location: " . BASE_URL . "/admin/customers.php");
    exit;
}

// Handle Add Measurement Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_measurement'])) {
    $profile_name  = sanitize($_POST['profile_name'] ?? 'Standard Fit');
    $gender        = sanitize($_POST['gender'] ?? 'unisex');
    $unit          = sanitize($_POST['unit'] ?? 'inches');
    $neck          = !empty($_POST['neck']) ? (float)$_POST['neck'] : null;
    $chest         = !empty($_POST['chest_bust']) ? (float)$_POST['chest_bust'] : null;
    $waist         = !empty($_POST['waist']) ? (float)$_POST['waist'] : null;
    $hips          = !empty($_POST['hips']) ? (float)$_POST['hips'] : null;
    $shoulder      = !empty($_POST['shoulder']) ? (float)$_POST['shoulder'] : null;
    $sleeve        = !empty($_POST['sleeve_length']) ? (float)$_POST['sleeve_length'] : null;
    $shirt_length  = !empty($_POST['shirt_length']) ? (float)$_POST['shirt_length'] : null;
    $trouser_waist = !empty($_POST['trouser_waist']) ? (float)$_POST['trouser_waist'] : null;
    $trouser_len   = !empty($_POST['trouser_length']) ? (float)$_POST['trouser_length'] : null;
    $inseam        = !empty($_POST['inseam']) ? (float)$_POST['inseam'] : null;
    $thigh         = !empty($_POST['thigh']) ? (float)$_POST['thigh'] : null;
    $custom_specs  = sanitize($_POST['custom_specs'] ?? '');
    $notes         = sanitize($_POST['notes'] ?? '');

    $mId = db_insert('measurement_profiles', [
        'customer_id'    => $customer_id,
        'profile_name'   => $profile_name,
        'gender'         => $gender,
        'unit'           => $unit,
        'neck'           => $neck,
        'chest_bust'     => $chest,
        'waist'          => $waist,
        'hips'           => $hips,
        'shoulder'       => $shoulder,
        'sleeve_length'  => $sleeve,
        'shirt_length'   => $shirt_length,
        'trouser_waist'  => $trouser_waist,
        'trouser_length' => $trouser_len,
        'inseam'         => $inseam,
        'thigh'          => $thigh,
        'custom_specs'   => $custom_specs,
        'notes'          => $notes
    ]);

    if ($mId) {
        set_flash_message('success', 'New measurement profile saved.');
    } else {
        set_flash_message('danger', 'Failed to save measurement profile.');
    }
    header("Location: " . BASE_URL . "/admin/customer_view.php?id=" . $customer_id);
    exit;
}

// Fetch Stored Measurements
$measurements = db_fetch_all("SELECT * FROM measurement_profiles WHERE customer_id = ? ORDER BY id DESC", [$customer_id]);

// Fetch Customer Orders
$orders = db_fetch_all("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC", [$customer_id]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <a href="<?= BASE_URL ?>/admin/customers.php" class="text-decoration-none text-muted small mb-1 d-inline-block">
      <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
    <h4 class="fw-bold mb-0">
      <?= e($customer['institution_name'] ?: $customer['name']) ?>
      <span class="badge <?= $customer['type'] == 'institution' ? 'bg-primary' : 'bg-secondary' ?> ms-2 fs-6">
        <?= ucfirst($customer['type']) ?>
      </span>
    </h4>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <a href="<?= e(whatsapp_url("Hello {$customer['contact_person']}! This is StitchCraft.")) ?>" target="_blank" class="btn btn-success btn-sm">
      <i class="bi bi-whatsapp me-1"></i> WhatsApp
    </a>
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMeasurementModal">
      <i class="bi bi-rulers me-1"></i> Add Measurement Profile
    </button>
    <a href="<?= BASE_URL ?>/admin/order_create.php?customer_id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-circle me-1"></i> Create New Order
    </a>
  </div>
</div>

<div class="row g-4">
  
  <!-- Customer Details Card -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
      <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Account Information</h6>
      
      <?php if ($customer['institution_name']): ?>
        <div class="mb-3">
          <label class="small text-muted d-block">Institution</label>
          <span class="fw-bold"><?= e($customer['institution_name']) ?></span>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="small text-muted d-block">Contact Person</label>
        <span class="fw-bold"><?= e($customer['contact_person'] ?: $customer['name']) ?></span>
      </div>

      <div class="mb-3">
        <label class="small text-muted d-block">Phone Number</label>
        <a href="tel:<?= e($customer['phone']) ?>" class="fw-bold text-decoration-none">
          <i class="bi bi-telephone me-1"></i> <?= e($customer['phone']) ?>
        </a>
      </div>

      <?php if ($customer['email']): ?>
        <div class="mb-3">
          <label class="small text-muted d-block">Email</label>
          <span><?= e($customer['email']) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($customer['physical_address']): ?>
        <div class="mb-3">
          <label class="small text-muted d-block">Address</label>
          <small class="text-secondary"><?= nl2br(e($customer['physical_address'])) ?></small>
        </div>
      <?php endif; ?>

      <?php if ($customer['notes']): ?>
        <div class="mb-3">
          <label class="small text-muted d-block">Internal Notes</label>
          <div class="p-2 bg-light rounded small text-secondary border">
            <?= nl2br(e($customer['notes'])) ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="small text-muted">
        Customer since <?= format_date($customer['created_at']) ?>
      </div>
    </div>
  </div>

  <!-- Main Content: Measurements & Orders -->
  <div class="col-lg-8">
    
    <!-- Measurement Profiles Section -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-rulers me-2 text-primary"></i> Stored Measurement Profiles (<?= count($measurements) ?>)</h6>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMeasurementModal">
          <i class="bi bi-plus-lg me-1"></i> Add Profile
        </button>
      </div>
      <div class="card-body p-4">
        <?php if (!empty($measurements)): ?>
          <div class="row g-3">
            <?php foreach ($measurements as $m): ?>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h6 class="fw-bold mb-0 text-dark"><?= e($m['profile_name']) ?></h6>
                      <small class="text-muted text-capitalize"><?= e($m['gender']) ?> &bull; Unit: <?= e($m['unit']) ?></small>
                    </div>
                    <span class="badge bg-secondary"><?= format_date($m['created_at']) ?></span>
                  </div>
                  
                  <div class="row g-1 small text-secondary mt-2">
                    <?php if ($m['chest_bust']): ?><div class="col-6">Chest/Bust: <strong><?= $m['chest_bust'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['waist']): ?><div class="col-6">Waist: <strong><?= $m['waist'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['shoulder']): ?><div class="col-6">Shoulder: <strong><?= $m['shoulder'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['sleeve_length']): ?><div class="col-6">Sleeve: <strong><?= $m['sleeve_length'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['shirt_length']): ?><div class="col-6">Jacket/Shirt: <strong><?= $m['shirt_length'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['trouser_length']): ?><div class="col-6">Trouser Len: <strong><?= $m['trouser_length'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['inseam']): ?><div class="col-6">Inseam: <strong><?= $m['inseam'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                    <?php if ($m['neck']): ?><div class="col-6">Neck/Collar: <strong><?= $m['neck'] ?> <?= $m['unit'] ?></strong></div><?php endif; ?>
                  </div>

                  <?php if (!empty($m['custom_specs'])): ?>
                    <div class="mt-2 pt-2 border-top small text-muted">
                      <strong>Specs:</strong> <?= e($m['custom_specs']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted small text-center py-3 mb-0">No measurement profiles stored for this customer yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Orders History Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-bag-check me-2 text-primary"></i> Order History</h6>
        <a href="<?= BASE_URL ?>/admin/order_create.php?customer_id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-lg me-1"></i> New Order
        </a>
      </div>
      <div class="table-responsive">
        <table class="table admin-table mb-0">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Order Title</th>
              <th>Qty Delivered</th>
              <th>Total Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($orders)): ?>
              <?php foreach ($orders as $ord): ?>
                <tr>
                  <td>
                    <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $ord['id'] ?>" class="fw-bold text-decoration-none">
                      <?= e($ord['order_number']) ?>
                    </a>
                  </td>
                  <td><?= e($ord['order_title']) ?></td>
                  <td class="text-center">
                    <strong><?= (int)$ord['delivered_quantity'] ?></strong> / <?= (int)$ord['total_quantity'] ?>
                  </td>
                  <td class="fw-bold">
                    <?php if (has_role('admin')): ?>
                      <?= format_currency($ord['total_amount']) ?>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?= get_payment_status_badge($ord['payment_status']) ?></td>
                  <td><?= get_order_status_badge($ord['status']) ?></td>
                  <td>
                    <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-light border">
                      Manage
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No orders placed yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

<!-- Modal: Add Measurement Profile -->
<div class="modal fade" id="addMeasurementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action_add_measurement" value="1">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Record Measurement Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Profile Label <span class="text-danger">*</span></label>
              <input type="text" name="profile_name" class="form-control" placeholder="e.g. Standard Suit, Grade 8 Boy, Chef Coat" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Gender / Group</label>
              <select name="gender" class="form-select">
                <option value="male">Male / Menswear</option>
                <option value="female">Female / Womenswear</option>
                <option value="boy">School Boy</option>
                <option value="girl">School Girl</option>
                <option value="unisex">Unisex</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Unit</label>
              <select name="unit" class="form-select">
                <option value="inches" selected>Inches</option>
                <option value="cm">Centimeters</option>
              </select>
            </div>
          </div>

          <h6 class="small fw-bold text-primary border-bottom pb-1 mb-3">Upper Body Measurements</h6>
          <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
              <label class="form-label small">Chest / Bust</label>
              <input type="number" step="0.25" name="chest_bust" class="form-control form-control-sm" placeholder="e.g. 40.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Shoulder Width</label>
              <input type="number" step="0.25" name="shoulder" class="form-control form-control-sm" placeholder="e.g. 18.5">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Sleeve Length</label>
              <input type="number" step="0.25" name="sleeve_length" class="form-control form-control-sm" placeholder="e.g. 25.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Jacket / Shirt Len</label>
              <input type="number" step="0.25" name="shirt_length" class="form-control form-control-sm" placeholder="e.g. 30.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Neck / Collar</label>
              <input type="number" step="0.25" name="neck" class="form-control form-control-sm" placeholder="e.g. 16.0">
            </div>
          </div>

          <h6 class="small fw-bold text-primary border-bottom pb-1 mb-3">Lower Body Measurements</h6>
          <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
              <label class="form-label small">Trouser Waist</label>
              <input type="number" step="0.25" name="trouser_waist" class="form-control form-control-sm" placeholder="e.g. 34.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Hips</label>
              <input type="number" step="0.25" name="hips" class="form-control form-control-sm" placeholder="e.g. 40.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Trouser Length</label>
              <input type="number" step="0.25" name="trouser_length" class="form-control form-control-sm" placeholder="e.g. 41.0">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Inseam</label>
              <input type="number" step="0.25" name="inseam" class="form-control form-control-sm" placeholder="e.g. 31.5">
            </div>
            <div class="col-md-3 col-6">
              <label class="form-label small">Thigh Width</label>
              <input type="number" step="0.25" name="thigh" class="form-control form-control-sm" placeholder="e.g. 23.0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Custom Specifications & Fit Notes</label>
            <textarea name="custom_specs" class="form-control form-control-sm" rows="2" placeholder="e.g. Lapel width 3.5 inches, double vent, elasticated waistband expansion for students..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Save Measurement Profile</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
