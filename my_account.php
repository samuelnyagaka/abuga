<?php
/**
 * Customer Marketplace Portal & Dashboard
 * Tailor Business Management & Marketing System
 */

$page_title = 'My Account & Orders | Customer Portal';
$page_desc = 'Manage your uniform production orders, saved measurement profiles, and expedited quote requests.';

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Strict Customer Guard
require_customer_login();

$customerId = (int)$_SESSION['customer_id'];
$customer = db_fetch_one("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);

if (!$customer) {
    // Session out of sync
    header("Location: " . BASE_URL . "/logout.php");
    exit;
}

// Handle Add/Save Measurement Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_measurements'])) {
    $profile_name  = sanitize($_POST['profile_name'] ?? 'General Measurements');
    $gender        = in_array($_POST['gender'] ?? '', ['male', 'female', 'boy', 'girl', 'unisex']) ? $_POST['gender'] : 'unisex';
    $unit          = in_array($_POST['unit'] ?? '', ['inches', 'cm']) ? $_POST['unit'] : 'inches';
    $neck          = (float)($_POST['neck'] ?? 0);
    $chest_bust    = (float)($_POST['chest_bust'] ?? 0);
    $waist         = (float)($_POST['waist'] ?? 0);
    $hips          = (float)($_POST['hips'] ?? 0);
    $shoulder      = (float)($_POST['shoulder'] ?? 0);
    $sleeve_length = (float)($_POST['sleeve_length'] ?? 0);
    $shirt_length  = (float)($_POST['shirt_length'] ?? 0);
    $trouser_waist = (float)($_POST['trouser_waist'] ?? 0);
    $trouser_length= (float)($_POST['trouser_length'] ?? 0);
    $inseam        = (float)($_POST['inseam'] ?? 0);
    $thigh         = (float)($_POST['thigh'] ?? 0);
    $custom_specs  = sanitize($_POST['custom_specs'] ?? '');
    $notes         = sanitize($_POST['notes'] ?? '');

    $newProfileId = db_insert('measurement_profiles', [
        'customer_id'    => $customerId,
        'profile_name'   => $profile_name,
        'gender'         => $gender,
        'unit'           => $unit,
        'neck'           => $neck ?: null,
        'chest_bust'     => $chest_bust ?: null,
        'waist'          => $waist ?: null,
        'hips'           => $hips ?: null,
        'shoulder'       => $shoulder ?: null,
        'sleeve_length'  => $sleeve_length ?: null,
        'shirt_length'   => $shirt_length ?: null,
        'trouser_waist'  => $trouser_waist ?: null,
        'trouser_length' => $trouser_length ?: null,
        'inseam'         => $inseam ?: null,
        'thigh'          => $thigh ?: null,
        'custom_specs'   => $custom_specs,
        'notes'          => $notes
    ]);

    if ($newProfileId) {
        set_flash_message('success', "Measurement profile '{$profile_name}' saved to your account.");
    } else {
        set_flash_message('danger', 'Failed to save measurement profile. Please verify your inputs.');
    }
    header("Location: " . BASE_URL . "/my_account.php#measurements");
    exit;
}

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    $phone = sanitize($_POST['phone'] ?? '');
    $physical_address = sanitize($_POST['physical_address'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if ($customer['type'] === 'institution') {
        $contact_person = sanitize($_POST['contact_person'] ?? '');
        $institution_name = sanitize($_POST['institution_name'] ?? '');
        
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                set_flash_message('danger', 'New password must be at least 6 characters.');
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                db_query(
                    "UPDATE customers SET institution_name = ?, name = ?, contact_person = ?, phone = ?, physical_address = ?, password_hash = ? WHERE id = ?",
                    [$institution_name, $institution_name, $contact_person, $phone, $physical_address, $hash, $customerId]
                );
                set_flash_message('success', 'Profile and password updated successfully.');
            }
        } else {
            db_query(
                "UPDATE customers SET institution_name = ?, name = ?, contact_person = ?, phone = ?, physical_address = ? WHERE id = ?",
                [$institution_name, $institution_name, $contact_person, $phone, $physical_address, $customerId]
            );
            set_flash_message('success', 'Institution profile updated successfully.');
        }

        $_SESSION['customer_name'] = $institution_name;
        $_SESSION['customer_institution'] = $institution_name;
    } else {
        $name = sanitize($_POST['name'] ?? '');

        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                set_flash_message('danger', 'New password must be at least 6 characters.');
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                db_query(
                    "UPDATE customers SET name = ?, phone = ?, physical_address = ?, password_hash = ? WHERE id = ?",
                    [$name, $phone, $physical_address, $hash, $customerId]
                );
                set_flash_message('success', 'Profile and password updated successfully.');
            }
        } else {
            db_query(
                "UPDATE customers SET name = ?, phone = ?, physical_address = ? WHERE id = ?",
                [$name, $phone, $physical_address, $customerId]
            );
            set_flash_message('success', 'Profile updated successfully.');
        }

        $_SESSION['customer_name'] = $name;
    }

    $_SESSION['customer_phone'] = $phone;
    header("Location: " . BASE_URL . "/my_account.php#profile");
    exit;
}

// Fetch Customer's Orders
$orders = db_fetch_all("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC", [$customerId]);

// Fetch Customer's Measurement Profiles
$measurements = db_fetch_all("SELECT * FROM measurement_profiles WHERE customer_id = ? ORDER BY updated_at DESC", [$customerId]);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4 my-2">
  
  <!-- Profile Header Card -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 bg-white">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle <?= $customer['type'] === 'institution' ? 'bg-primary' : 'bg-gold' ?> text-<?= $customer['type'] === 'institution' ? 'white' : 'dark' ?> d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm flex-shrink-0" style="width: 60px; height: 60px;">
            <?= strtoupper(substr($customer['name'], 0, 1)) ?>
          </div>
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <h4 class="fw-bold mb-0 text-dark"><?= e($customer['name']) ?></h4>
              <?php if ($customer['type'] === 'institution'): ?>
                <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                  <i class="bi bi-buildings"></i> Institutional Partner
                </span>
              <?php else: ?>
                <span class="badge bg-gold text-dark d-inline-flex align-items-center gap-1">
                  <i class="bi bi-scissors"></i> Bespoke Client
                </span>
              <?php endif; ?>
            </div>
            <div class="small text-muted mt-1 d-flex flex-wrap gap-3">
              <span><i class="bi bi-envelope me-1"></i> <?= e($customer['email']) ?></span>
              <span><i class="bi bi-telephone me-1"></i> <?= e($customer['phone']) ?></span>
              <?php if (!empty($customer['physical_address'])): ?>
                <span><i class="bi bi-geo-alt me-1"></i> <?= e($customer['physical_address']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <?php if ($customer['type'] === 'institution'): ?>
            <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-sm px-3 fw-semibold">
              <i class="bi bi-plus-circle me-1"></i> New Uniform Tender
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-primary btn-sm px-3 fw-semibold">
              <i class="bi bi-plus-circle me-1"></i> Request Custom Quote
            </a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm px-3">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Metric Badges -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
        <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
          <i class="bi bi-bag-check-fill"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= count($orders) ?></div>
          <div class="small text-muted">Total Orders Placed</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
        <div class="rounded-3 bg-warning-subtle text-warning p-3 fs-4">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <?php 
            $activeCount = 0;
            foreach ($orders as $o) {
                if (in_array($o['status'], ['confirmed', 'in_production', 'ready_for_pickup'])) {
                    $activeCount++;
                }
            }
          ?>
          <div class="fs-4 fw-bold text-dark"><?= $activeCount ?></div>
          <div class="small text-muted">Active in Production</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
        <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
          <i class="bi bi-rulers"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold text-dark"><?= count($measurements) ?></div>
          <div class="small text-muted">Saved Sizing Profiles</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Navigation -->
  <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm" id="customerPortalTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active px-4 py-2 fw-semibold rounded-3" id="tab-orders-btn" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button" role="tab">
        <i class="bi bi-truck me-1"></i> My Orders & Manufacturing Pipeline (<?= count($orders) ?>)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link px-4 py-2 fw-semibold rounded-3" id="tab-measurements-btn" data-bs-toggle="pill" data-bs-target="#tab-measurements" type="button" role="tab">
        <i class="bi bi-rulers me-1"></i> Saved Measurements (<?= count($measurements) ?>)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link px-4 py-2 fw-semibold rounded-3" id="tab-profile-btn" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">
        <i class="bi bi-person-gear me-1"></i> Profile & Security
      </button>
    </li>
  </ul>

  <!-- Tab Content Area -->
  <div class="tab-content" id="customerPortalTabContent">
    
    <!-- TAB 1: ORDERS & PIPELINE -->
    <div class="tab-pane fade show active" id="tab-orders" role="tabpanel">
      <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
          <div class="mb-3 text-muted display-4"><i class="bi bi-inbox"></i></div>
          <h5 class="fw-bold text-dark">No orders placed yet</h5>
          <p class="text-muted small mx-auto" style="max-width: 450px;">
            Your uniform batches and bespoke garments will appear here once commissioned. You will be able to follow real-time manufacturing and batch deliveries.
          </p>
          <div class="mt-2">
            <?php if ($customer['type'] === 'institution'): ?>
              <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold px-4 fw-semibold">Submit Institutional Tender</a>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/quote_request.php" class="btn btn-primary px-4 fw-semibold">Request Custom Quote</a>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-4">
          <?php foreach ($orders as $o): ?>
            <?php 
              // Fetch line items
              $items = db_fetch_all("SELECT * FROM order_items WHERE order_id = ?", [$o['id']]);
              // Fetch partial deliveries
              $deliveries = db_fetch_all("SELECT * FROM order_deliveries WHERE order_id = ? ORDER BY delivery_date DESC", [$o['id']]);
            ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
              <div class="card-header bg-light border-bottom p-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-dark fw-normal"><?= e($o['order_number']) ?></span>
                  <h6 class="mb-0 fw-bold text-dark"><?= e($o['order_title']) ?></h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?= get_order_status_badge($o['status']) ?>
                  <?= get_payment_status_badge($o['payment_status']) ?>
                </div>
              </div>

              <div class="card-body p-4">
                
                <!-- Pipeline Visual Tracker -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between small text-muted mb-2">
                    <span class="fw-semibold text-dark"><i class="bi bi-activity text-primary me-1"></i> Workshop Progress</span>
                    <span>Deadline: <strong><?= format_date($o['deadline']) ?></strong></span>
                  </div>

                  <?php 
                    $stages = [
                      'confirmed' => ['label' => 'Order Confirmed', 'pct' => 25],
                      'in_production' => ['label' => 'Fabric Cut & Stitching', 'pct' => 60],
                      'ready_for_pickup' => ['label' => 'Ready for Delivery / Pickup', 'pct' => 90],
                      'completed' => ['label' => 'Delivered & Complete', 'pct' => 100],
                    ];
                    $currentStagePct = $stages[$o['status']]['pct'] ?? 15;
                  ?>
                  <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $currentStagePct ?>%;"></div>
                  </div>
                  <div class="d-flex justify-content-between small text-muted mt-1" style="font-size: 0.72rem;">
                    <span>Order Placed</span>
                    <span>Cutting & Tailoring</span>
                    <span>Quality Inspected</span>
                    <span>Delivered</span>
                  </div>
                </div>

                <!-- Financial & Quantity Summary -->
                <div class="row g-3 mb-3 p-3 bg-light rounded-3 text-center">
                  <div class="col-6 col-md-3">
                    <div class="text-muted small">Total Garments</div>
                    <div class="fw-bold text-dark"><?= (int)$o['total_quantity'] ?> pcs</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted small">Delivered to Date</div>
                    <div class="fw-bold text-success"><?= (int)$o['delivered_quantity'] ?> pcs</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted small">Order Total</div>
                    <div class="fw-bold text-dark"><?= format_currency($o['total_amount']) ?></div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted small">Outstanding Balance</div>
                    <div class="fw-bold text-danger"><?= format_currency($o['total_amount'] - $o['paid_amount']) ?></div>
                  </div>
                </div>

                <!-- Items Breakdown Table -->
                <?php if (!empty($items)): ?>
                  <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle small mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Item / Garment Specification</th>
                          <th>Size</th>
                          <th>Qty Ordered</th>
                          <th>Delivered</th>
                          <th class="text-end">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $item): ?>
                          <tr>
                            <td>
                              <span class="fw-semibold text-dark"><?= e($item['item_title']) ?></span>
                              <?php if (!empty($item['fabric_details'])): ?>
                                <small class="text-muted d-block"><?= e($item['fabric_details']) ?></small>
                              <?php endif; ?>
                            </td>
                            <td><code><?= e($item['size_label']) ?></code></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int)$item['delivered_quantity'] ?></span></td>
                            <td class="text-end fw-semibold"><?= format_currency($item['subtotal_price']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>

                <!-- Partial Delivery Log -->
                <?php if (!empty($deliveries)): ?>
                  <div class="p-3 bg-white border rounded-3 mb-3">
                    <h6 class="small fw-bold text-dark mb-2"><i class="bi bi-box-seam me-1 text-primary"></i> Batch Deliveries Logged:</h6>
                    <div class="d-flex flex-wrap gap-3">
                      <?php foreach ($deliveries as $d): ?>
                        <div class="badge bg-light text-dark border p-2 small text-start">
                          <div><strong><?= (int)$d['quantity_delivered'] ?> units</strong> on <?= format_date($d['delivery_date']) ?></div>
                          <small class="text-muted">Received by: <?= e($d['received_by'] ?: 'Designated Recipient') ?></small>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <!-- Action Footer -->
                <div class="d-flex justify-content-between align-items-center pt-2">
                  <span class="small text-muted">Created: <?= format_date($o['created_at']) ?></span>
                  <a href="<?= BASE_URL ?>/admin/invoice.php?order_id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-printer me-1"></i> View Printable Invoice / Proforma
                  </a>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- TAB 2: MEASUREMENTS -->
    <div class="tab-pane fade" id="tab-measurements" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="fw-bold mb-0">Saved Measurement Profiles</h5>
          <p class="text-muted small mb-0">Custom sizing on record for your uniforms and tailored garments.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addMeasurementModal">
          <i class="bi bi-plus-lg me-1"></i> Add New Sizing Profile
        </button>
      </div>

      <?php if (empty($measurements)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
          <div class="mb-3 text-muted display-4"><i class="bi bi-rulers"></i></div>
          <h5 class="fw-bold text-dark">No measurement profiles saved yet</h5>
          <p class="text-muted small mx-auto" style="max-width: 450px;">
            Save your chest, waist, sleeve, and shoulder dimensions once, and our master tailors will craft all future orders with precision fit.
          </p>
          <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addMeasurementModal">
            Enter My Measurements
          </button>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($measurements as $m): ?>
            <div class="col-lg-6">
              <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-light border-bottom p-3 px-4 d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                      <i class="bi bi-person"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-0 text-dark"><?= e($m['profile_name']) ?></h6>
                      <small class="text-muted"><?= ucfirst($m['gender']) ?> &bull; Unit: <strong><?= e($m['unit']) ?></strong></small>
                    </div>
                  </div>
                  <span class="badge bg-light text-muted border">Updated: <?= format_date($m['updated_at']) ?></span>
                </div>

                <div class="card-body p-4">
                  <div class="row g-2 text-center small mb-3">
                    <?php if ($m['chest_bust']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Chest / Bust</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['chest_bust'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['waist']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Waist</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['waist'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['hips']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Hips</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['hips'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['shoulder']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Shoulder</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['shoulder'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['sleeve_length']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Sleeve</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['sleeve_length'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['shirt_length']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Shirt Length</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['shirt_length'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['trouser_waist']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Trouser Waist</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['trouser_waist'] ?>"</div>
                      </div>
                    <?php endif; ?>
                    <?php if ($m['trouser_length']): ?>
                      <div class="col-4 col-sm-3 p-2 border rounded bg-light">
                        <div class="text-muted" style="font-size: 0.72rem;">Trouser Len</div>
                        <div class="fw-bold fs-6 text-dark"><?= $m['trouser_length'] ?>"</div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($m['custom_specs'])): ?>
                    <div class="small text-muted mb-2">
                      <strong>Special Styling:</strong> <?= e($m['custom_specs']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($m['notes'])): ?>
                    <div class="small text-secondary fst-italic">
                      "<?= e($m['notes']) ?>"
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- TAB 3: PROFILE SETTINGS -->
    <div class="tab-pane fade" id="tab-profile" role="tabpanel">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h5 class="fw-bold mb-1 text-dark">Profile & Account Settings</h5>
            <p class="text-muted small mb-4">Keep your contact details and security credentials up to date.</p>

            <form method="POST" action="">
              <input type="hidden" name="action_update_profile" value="1">

              <?php if ($customer['type'] === 'institution'): ?>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Institution / School Name</label>
                  <input type="text" name="institution_name" class="form-control" value="<?= e($customer['institution_name'] ?: $customer['name']) ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Contact Person Name</label>
                  <input type="text" name="contact_person" class="form-control" value="<?= e($customer['contact_person']) ?>" required>
                </div>
              <?php else: ?>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= e($customer['name']) ?>" required>
                </div>
              <?php endif; ?>

              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email Address</label>
                  <input type="email" class="form-control bg-light text-muted" value="<?= e($customer['email']) ?>" readonly>
                  <div class="form-text small">Email address serves as your primary login ID.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Phone Number</label>
                  <input type="tel" name="phone" class="form-control" value="<?= e($customer['phone']) ?>" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Physical Address / Delivery Location</label>
                <input type="text" name="physical_address" class="form-control" value="<?= e($customer['physical_address']) ?>" placeholder="e.g. Westlands, Nairobi">
              </div>

              <hr class="my-4 text-muted">

              <div class="mb-4">
                <label class="form-label small fw-bold">
                  Change Password 
                  <span class="text-muted fw-normal">(leave blank to keep current password)</span>
                </label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter at least 6 characters" minlength="6">
              </div>

              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Save Changes
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal: Add New Measurement Profile -->
<div class="modal fade" id="addMeasurementModal" tabindex="-1" aria-labelledby="addMeasurementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" action="">
        <input type="hidden" name="action_save_measurements" value="1">
        
        <div class="modal-header border-bottom bg-light px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-rulers"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="addMeasurementModalLabel">Add Measurement Profile</h5>
              <small class="text-muted">Save your body sizing for uniform and custom suit production</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Profile Label <span class="text-danger">*</span></label>
              <input type="text" name="profile_name" class="form-control" placeholder="e.g. Executive 3-Piece Suit or Grade 8 Boy Uniform" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Gender / Fit</label>
              <select name="gender" class="form-select">
                <option value="male">Male / Men</option>
                <option value="female">Female / Women</option>
                <option value="boy">Boy (School)</option>
                <option value="girl">Girl (School)</option>
                <option value="unisex">Unisex</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Measurement Unit</label>
              <select name="unit" class="form-select">
                <option value="inches" selected>Inches (")</option>
                <option value="cm">Centimeters (cm)</option>
              </select>
            </div>
          </div>

          <h6 class="small fw-bold text-primary border-bottom pb-2 mb-3">Upper Body Measurements</h6>
          <div class="row g-2 mb-3 text-center">
            <div class="col-6 col-md-3">
              <label class="form-label small">Chest / Bust</label>
              <input type="number" step="0.25" name="chest_bust" class="form-control form-control-sm text-center" placeholder="38.0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Waist</label>
              <input type="number" step="0.25" name="waist" class="form-control form-control-sm text-center" placeholder="32.0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Shoulder Width</label>
              <input type="number" step="0.25" name="shoulder" class="form-control form-control-sm text-center" placeholder="17.5">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Sleeve Length</label>
              <input type="number" step="0.25" name="sleeve_length" class="form-control form-control-sm text-center" placeholder="24.5">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Neck / Collar</label>
              <input type="number" step="0.25" name="neck" class="form-control form-control-sm text-center" placeholder="15.5">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Shirt / Blazer Len</label>
              <input type="number" step="0.25" name="shirt_length" class="form-control form-control-sm text-center" placeholder="29.0">
            </div>
          </div>

          <h6 class="small fw-bold text-primary border-bottom pb-2 mb-3">Lower Body Measurements</h6>
          <div class="row g-2 mb-3 text-center">
            <div class="col-6 col-md-3">
              <label class="form-label small">Trouser Waist</label>
              <input type="number" step="0.25" name="trouser_waist" class="form-control form-control-sm text-center" placeholder="33.0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Trouser Length</label>
              <input type="number" step="0.25" name="trouser_length" class="form-control form-control-sm text-center" placeholder="40.0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Hips</label>
              <input type="number" step="0.25" name="hips" class="form-control form-control-sm text-center" placeholder="39.0">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small">Inseam</label>
              <input type="number" step="0.25" name="inseam" class="form-control form-control-sm text-center" placeholder="31.0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Custom Styling / Fit Notes</label>
            <input type="text" name="custom_specs" class="form-control form-control-sm" placeholder="e.g. Slim fit, elastic waistband, double vent back, pleated front...">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold">Personal Sizing Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Any special notes for our cutters and tailors..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">Save Sizing Profile</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
