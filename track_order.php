<?php
/**
 * Public Customer Order Tracker
 * Tailor Business Management & Marketing System
 */

$page_title = 'Track Your Uniform or Custom Order';
$page_desc = 'Real-time production progress tracker for school uniforms, corporate orders, and bespoke tailoring.';

require_once __DIR__ . '/includes/header.php';

$order_found = false;
$order = null;
$order_items = [];
$deliveries = [];
$lookup_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['order_number'])) {
    $order_number = sanitize($_POST['order_number'] ?? $_GET['order_number'] ?? '');
    $phone_or_email = sanitize($_POST['verification'] ?? $_GET['verification'] ?? '');

    if (empty($order_number)) {
        $lookup_error = 'Please enter your Order Number (e.g. ORD-2026-1001).';
    } else {
        // Query order with customer check for privacy
        $sql = "
            SELECT o.*, c.name as customer_name, c.institution_name, c.phone, c.email 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            WHERE o.order_number = ?
        ";
        $params = [$order_number];

        if (!empty($phone_or_email)) {
            $sql .= " AND (c.phone LIKE ? OR c.whatsapp_number LIKE ? OR c.email = ?)";
            $wildcardPhone = '%' . preg_replace('/[^0-9]/', '', $phone_or_email) . '%';
            $params[] = $wildcardPhone;
            $params[] = $wildcardPhone;
            $params[] = $phone_or_email;
        }

        $order = db_fetch_one($sql, $params);

        if ($order) {
            $order_found = true;
            $order_items = db_fetch_all("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
            $deliveries = db_fetch_all("SELECT * FROM order_deliveries WHERE order_id = ? ORDER BY delivery_date DESC", [$order['id']]);
        } else {
            $lookup_error = 'No order found matching those details. Please double-check your Order Number and phone number.';
        }
    }
}
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold mb-2">Live Status</span>
    <h1 class="display-5 fw-bold">Track Your Order Production</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      Enter your Order Number and phone number to see live cutting, sewing, and delivery progress.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <!-- Search Box -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
          <form method="POST" action="<?= BASE_URL ?>/track_order.php" class="row g-3 align-items-end">
            <div class="col-md-5">
              <label class="form-label small fw-bold">Order Number <span class="text-danger">*</span></label>
              <input type="text" name="order_number" class="form-control" placeholder="e.g. ORD-2026-1001" required value="<?= e($_POST['order_number'] ?? $_GET['order_number'] ?? '') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-bold">Phone or Email on Order</label>
              <input type="text" name="verification" class="form-control" placeholder="e.g. 0722000000 or email" value="<?= e($_POST['verification'] ?? $_GET['verification'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-search me-1"></i> Track
              </button>
            </div>
          </form>
        </div>

        <?php if (!empty($lookup_error)): ?>
          <div class="alert alert-warning border-0 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= e($lookup_error) ?>
          </div>
        <?php endif; ?>

        <?php if ($order_found && $order): ?>
          
          <!-- Order Result Card -->
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 bg-white">
            <div class="card-header bg-dark text-white p-4 d-flex flex-wrap justify-content-between align-items-center">
              <div>
                <span class="badge bg-gold text-white mb-1" style="background: #d97706;"><?= e(strtoupper($order['order_type'])) ?></span>
                <h4 class="mb-0 fw-bold"><?= e($order['order_title']) ?></h4>
                <div class="small text-white-50">Order # <strong class="text-white"><?= e($order['order_number']) ?></strong></div>
              </div>
              <div class="mt-2 mt-md-0">
                <?= get_order_status_badge($order['status']) ?>
              </div>
            </div>

            <div class="card-body p-4">
              
              <!-- Client & Timeline Info -->
              <div class="row g-3 mb-4 pb-3 border-bottom">
                <div class="col-6 col-md-3">
                  <div class="small text-muted">Customer / Institution</div>
                  <div class="fw-bold"><?= e($order['institution_name'] ?: $order['customer_name']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="small text-muted">Order Date</div>
                  <div class="fw-bold"><?= format_date($order['created_at']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="small text-muted">Target Completion</div>
                  <div class="fw-bold text-primary"><?= format_date($order['deadline']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="small text-muted">Payment Status</div>
                  <div><?= get_payment_status_badge($order['payment_status']) ?></div>
                </div>
              </div>

              <!-- Bulk Delivery Progress Meter (for uniform orders) -->
              <div class="p-4 bg-light rounded-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i> Production & Delivery Progress</h6>
                  <span class="fw-bold text-primary">
                    <?= (int)$order['delivered_quantity'] ?> of <?= (int)$order['total_quantity'] ?> Units Delivered
                    <?php 
                      $percent = $order['total_quantity'] > 0 ? min(100, round(($order['delivered_quantity'] / $order['total_quantity']) * 100)) : 0;
                    ?>
                    (<?= $percent ?>%)
                  </span>
                </div>
                <div class="progress" style="height: 12px;">
                  <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <?php if ($order['total_quantity'] - $order['delivered_quantity'] > 0): ?>
                  <div class="small text-muted mt-2">
                    <i class="bi bi-arrow-repeat me-1"></i> Remainder (<?= $order['total_quantity'] - $order['delivered_quantity'] ?> units) currently in final quality inspection and pressing.
                  </div>
                <?php else: ?>
                  <div class="small text-success fw-bold mt-2">
                    <i class="bi bi-check-all me-1"></i> 100% of garments manufactured and dispatched!
                  </div>
                <?php endif; ?>
              </div>

              <!-- Finished Garment Photo (if uploaded by workshop) -->
              <?php if (!empty($order['completion_photo'])): ?>
                <div class="card border-0 bg-light p-3 rounded-4 mb-4 text-center">
                  <h6 class="fw-bold text-dark mb-2"><i class="bi bi-camera me-1 text-primary"></i> Finished Garment Photo from Workshop</h6>
                  <div>
                    <a href="<?= BASE_URL ?>/<?= e($order['completion_photo']) ?>" target="_blank">
                      <img src="<?= BASE_URL ?>/<?= e($order['completion_photo']) ?>" alt="Finished tailored garment" class="img-fluid rounded-3 shadow-sm" style="max-height: 280px; object-fit: contain;">
                    </a>
                  </div>
                  <small class="text-muted mt-2 d-block">Click photo to view full high-resolution image.</small>
                </div>
              <?php endif; ?>

              <!-- Order Line Items Breakdown -->
              <?php if (!empty($order_items)): ?>
                <h6 class="fw-bold mb-3 text-dark">Garment Sizing Matrix:</h6>
                <div class="table-responsive mb-4">
                  <table class="table table-bordered table-sm align-middle small">
                    <thead class="table-light">
                      <tr>
                        <th>Item Description</th>
                        <th>Size</th>
                        <th class="text-center">Total Qty</th>
                        <th class="text-center">Delivered</th>
                        <th>Fabric / Details</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($order_items as $item): ?>
                        <tr>
                          <td class="fw-semibold"><?= e($item['item_title']) ?></td>
                          <td><span class="badge bg-secondary"><?= e($item['size_label']) ?></span></td>
                          <td class="text-center"><?= (int)$item['quantity'] ?></td>
                          <td class="text-center text-success fw-bold"><?= (int)$item['delivered_quantity'] ?></td>
                          <td class="text-muted"><?= e($item['fabric_details'] ?: '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Action Bar -->
              <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top">
                <div>
                  <span class="small text-muted">Need to adjust sizing or delivery schedule?</span>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                  <a href="<?= e(whatsapp_url("Hello StitchCraft! I am checking on my Order " . $order['order_number'] . " (" . $order['order_title'] . ").")) ?>" 
                     target="_blank" class="btn btn-success btn-sm">
                    <i class="bi bi-whatsapp me-1"></i> WhatsApp Tailor
                  </a>
                </div>
              </div>

            </div>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
