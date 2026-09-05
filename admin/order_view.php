<?php
/**
 * Order Details, Partial Deliveries & Financial Ledger
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Manage Order';
require_once __DIR__ . '/admin_header.php';

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit;
}

$order = db_fetch_one("
    SELECT o.*, c.name as customer_name, c.institution_name, c.phone, c.email, c.physical_address,
           l.source as lead_source
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    LEFT JOIN leads l ON o.lead_id = l.id 
    WHERE o.id = ? 
    LIMIT 1
", [$order_id]);

if (!$order) {
    set_flash_message('danger', 'Order not found.');
    header("Location: " . BASE_URL . "/admin/orders.php");
    exit;
}

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_status'])) {
    $new_status = sanitize($_POST['status'] ?? $order['status']);
    db_update('orders', ['status' => $new_status], 'id = ?', [$order_id]);
    set_flash_message('success', 'Order pipeline stage updated to ' . ucfirst(str_replace('_', ' ', $new_status)));
    header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
    exit;
}

// Handle Logging a Partial Delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_log_delivery'])) {
    $delivery_date = sanitize($_POST['delivery_date'] ?? date('Y-m-d'));
    $qty_delivered = max(1, (int)($_POST['quantity_delivered'] ?? 1));
    $recipient     = sanitize($_POST['recipient_name'] ?? 'Authorized Storekeeper');
    $notes         = sanitize($_POST['delivery_notes'] ?? '');

    // Insert delivery record
    db_insert('order_deliveries', [
        'order_id'           => $order_id,
        'delivery_date'      => $delivery_date,
        'quantity_delivered' => $qty_delivered,
        'recipient_name'     => $recipient,
        'notes'              => $notes,
        'created_by'         => $user['id']
    ]);

    // Increment delivered quantity on order
    db_query("UPDATE orders SET delivered_quantity = delivered_quantity + ? WHERE id = ?", [$qty_delivered, $order_id]);

    // Check if 100% delivered, optionally advance status to ready_for_pickup or completed
    $updatedOrder = db_fetch_one("SELECT total_quantity, delivered_quantity FROM orders WHERE id = ?", [$order_id]);
    if ($updatedOrder && $updatedOrder['delivered_quantity'] >= $updatedOrder['total_quantity']) {
        db_update('orders', ['status' => 'completed'], 'id = ?', [$order_id]);
    }

    set_flash_message('success', "Logged delivery of {$qty_delivered} uniforms to {$recipient}.");
    header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
    exit;
}

// Handle Recording a Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_record_payment'])) {
    if (!has_role('admin')) {
        set_flash_message('danger', 'Only administrators can record payments.');
        header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
        exit;
    }

    $amount         = max(0.01, (float)($_POST['amount'] ?? 0));
    $payment_date   = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
    $payment_method = sanitize($_POST['payment_method'] ?? 'mpesa');
    $reference      = sanitize($_POST['reference_number'] ?? '');
    $notes          = sanitize($_POST['notes'] ?? '');

    // Insert payment record
    db_insert('payments', [
        'order_id'         => $order_id,
        'amount'           => $amount,
        'payment_date'     => $payment_date,
        'payment_method'   => $payment_method,
        'reference_number' => $reference,
        'recorded_by'      => $user['id'],
        'notes'            => $notes
    ]);

    // Update paid_amount and payment_status on order
    $newPaid = $order['paid_amount'] + $amount;
    $newPaymentStatus = ($newPaid >= $order['total_amount']) ? 'paid' : 'partial';

    db_update('orders', [
        'paid_amount'    => $newPaid,
        'payment_status' => $newPaymentStatus
    ], 'id = ?', [$order_id]);

    set_flash_message('success', 'Payment of ' . format_currency($amount) . ' recorded successfully.');
    header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
    exit;
}

// Handle Uploading Finished Garment Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_upload_order_photo'])) {
    if (!empty($_FILES['completion_photo']['name'])) {
        $upload = upload_and_resize_image($_FILES['completion_photo'], 'finished_orders', 1200, 82);
        if ($upload['success']) {
            db_update('orders', ['completion_photo' => $upload['path']], 'id = ?', [$order_id]);
            set_flash_message('success', 'Finished garment photo uploaded and attached to order.');
        } else {
            set_flash_message('danger', 'Photo upload failed: ' . $upload['error']);
        }
    }
    header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
    exit;
}

// Handle 1-Click Publish to Public Website Portfolio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_publish_to_gallery'])) {
    if (!empty($order['completion_photo'])) {
        $galleryCategory = 'suits_blazers';
        if ($order['order_type'] === 'school_uniform') $galleryCategory = 'school_uniforms';
        elseif ($order['order_type'] === 'corporate_uniform') $galleryCategory = 'corporate_uniforms';
        elseif ($order['order_type'] === 'security_workwear') $galleryCategory = 'security_medical';

        db_insert('gallery', [
            'title'       => $order['order_title'],
            'category'    => $galleryCategory,
            'image_path'  => $order['completion_photo'],
            'description' => 'Finished tailored garment for ' . ($order['institution_name'] ?: $order['customer_name']),
            'is_featured' => 1,
            'sort_order'  => 0
        ]);
        set_flash_message('success', 'Photo published to public website gallery showcase!');
    }
    header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $order_id);
    exit;
}

// Fetch Order Items
$items = db_fetch_all("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC", [$order_id]);

// Fetch Deliveries Log
$deliveries = db_fetch_all("
    SELECT d.*, u.full_name as logged_by_name 
    FROM order_deliveries d 
    LEFT JOIN users u ON d.created_by = u.id 
    WHERE d.order_id = ? 
    ORDER BY d.delivery_date DESC, d.id DESC
", [$order_id]);

// Fetch Payments Log
$payments = db_fetch_all("
    SELECT p.*, u.full_name as recorded_by_name 
    FROM payments p 
    LEFT JOIN users u ON p.recorded_by = u.id 
    WHERE p.order_id = ? 
    ORDER BY p.payment_date DESC, p.id DESC
", [$order_id]);

$balance_due = max(0, $order['total_amount'] - $order['paid_amount']);
$delivery_pct = $order['total_quantity'] > 0 ? min(100, round(($order['delivered_quantity'] / $order['total_quantity']) * 100)) : 0;
?>

<!-- Header Breadcrumb & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <a href="<?= BASE_URL ?>/admin/orders.php" class="text-decoration-none text-muted small mb-1 d-inline-block">
      <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
    <div class="d-flex align-items-center gap-2">
      <h4 class="fw-bold mb-0">[<?= e($order['order_number']) ?>] <?= e($order['order_title']) ?></h4>
      <?= get_order_status_badge($order['status']) ?>
    </div>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <a href="<?= BASE_URL ?>/admin/invoice.php?order_id=<?= $order['id'] ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-printer me-1"></i> Printable Invoice / Quote
    </a>
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logDeliveryModal">
      <i class="bi bi-truck me-1"></i> Log Partial Delivery
    </button>
    <?php if (has_role('admin')): ?>
      <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="bi bi-cash-stack me-1"></i> Record Payment
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">
  
  <!-- Main Details & Line Items -->
  <div class="col-lg-8">
    
    <!-- Bulk Delivery Progress Meter -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-primary me-2"></i> Uniform Production & Batch Deliveries</h6>
        <span class="badge bg-primary fs-6"><?= (int)$order['delivered_quantity'] ?> of <?= (int)$order['total_quantity'] ?> Delivered (<?= $delivery_pct ?>%)</span>
      </div>
      <div class="delivery-progress-container mb-3" style="height: 10px;">
        <div class="delivery-progress-bar" style="width: <?= $delivery_pct ?>%;"></div>
      </div>
      <div class="d-flex justify-content-between small text-muted">
        <span>Units Remaining: <strong><?= max(0, $order['total_quantity'] - $order['delivered_quantity']) ?></strong></span>
        <span>Target Opening Day / Deadline: <strong><?= format_date($order['deadline']) ?></strong></span>
      </div>
    </div>

    <!-- Garment Sizing & Line Items Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i> Garments & Sizing Matrix</h6>
      </div>
      <div class="table-responsive">
        <table class="table admin-table mb-0">
          <thead>
            <tr>
              <th>Garment Description</th>
              <th>Size</th>
              <th class="text-center">Total Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div class="fw-bold"><?= e($item['item_title']) ?></div>
                  <?php if (!empty($item['fabric_details'])): ?>
                    <small class="text-muted"><?= e($item['fabric_details']) ?></small>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?= e($item['size_label']) ?></span></td>
                <td class="text-center fw-bold"><?= (int)$item['quantity'] ?></td>
                <td class="text-end">
                  <?php if (has_role('admin')): ?>
                    <?= format_currency($item['unit_price']) ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td class="text-end fw-bold">
                  <?php if (has_role('admin')): ?>
                    <?= format_currency($item['subtotal_price']) ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (has_role('admin')): ?>
            <tfoot class="table-light">
              <tr>
                <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                <td class="text-end fw-bold text-primary fs-6"><?= format_currency($order['total_amount']) ?></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Partial Deliveries Audit Log -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-truck me-2 text-primary"></i> Partial Deliveries History (<?= count($deliveries) ?> Batches)</h6>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logDeliveryModal">
          <i class="bi bi-plus-lg me-1"></i> Log Batch
        </button>
      </div>
      <div class="table-responsive">
        <table class="table admin-table mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Quantity Delivered</th>
              <th>Received By / Storekeeper</th>
              <th>Notes</th>
              <th>Logged By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($deliveries)): ?>
              <?php foreach ($deliveries as $d): ?>
                <tr>
                  <td><?= format_date($d['delivery_date']) ?></td>
                  <td class="fw-bold text-success">+<?= (int)$d['quantity_delivered'] ?> units</td>
                  <td><?= e($d['recipient_name']) ?></td>
                  <td class="small text-muted"><?= e($d['notes'] ?: '-') ?></td>
                  <td class="small text-secondary"><?= e($d['logged_by_name'] ?? 'Staff') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No deliveries logged yet. Use the button above to log the first batch.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Right Column: Pipeline Stage & Financials -->
  <div class="col-lg-4">
    
    <!-- Stage Transition Card -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Order Pipeline Stage</h6>
      <form method="POST" action="">
        <input type="hidden" name="action_update_status" value="1">
        <div class="mb-3">
          <label class="form-label small fw-bold">Current Stage</label>
          <select name="status" class="form-select form-select-sm">
            <option value="quoted" <?= $order['status'] == 'quoted' ? 'selected' : '' ?>>Quoted (Awaiting Deposit)</option>
            <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed (Deposit Received)</option>
            <option value="in_production" <?= $order['status'] == 'in_production' ? 'selected' : '' ?>>In Production (Cutting / Sewing)</option>
            <option value="ready_for_pickup" <?= $order['status'] == 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Delivery / Fitting</option>
            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed (100% Delivered)</option>
            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
          Update Order Stage
        </button>
      </form>
    </div>

    <!-- Financial Ledger (Admin Only) -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h6 class="fw-bold text-dark mb-0">Financial Summary</h6>
        <?= get_payment_status_badge($order['payment_status']) ?>
      </div>

      <?php if (has_role('admin')): ?>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">Total Order Value:</span>
          <strong class="text-dark"><?= format_currency($order['total_amount']) ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">Total Paid / Deposits:</span>
          <strong class="text-success"><?= format_currency($order['paid_amount']) ?></strong>
        </div>
        <div class="d-flex justify-content-between pt-2 border-top mb-3">
          <span class="fw-bold small">Balance Remaining:</span>
          <strong class="fs-5 <?= $balance_due > 0 ? 'text-danger' : 'text-success' ?>">
            <?= format_currency($balance_due) ?>
          </strong>
        </div>

        <?php if (!empty($payments)): ?>
          <h6 class="small fw-bold text-secondary mb-2">Payment Receipts:</h6>
          <div class="list-group list-group-flush mb-3">
            <?php foreach ($payments as $p): ?>
              <div class="list-group-item px-0 py-2 small border-bottom">
                <div class="d-flex justify-content-between">
                  <span class="fw-bold text-success"><?= format_currency($p['amount']) ?></span>
                  <span class="text-muted"><?= format_date($p['payment_date']) ?></span>
                </div>
                <div class="small text-muted">
                  <?= e(strtoupper($p['payment_method'])) ?> &bull; Ref: <?= e($p['reference_number'] ?: 'N/A') ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <button type="button" class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
          <i class="bi bi-plus-circle me-1"></i> Record New Payment
        </button>
      <?php else: ?>
        <p class="small text-muted mb-0">Financial figures are restricted to administrator accounts.</p>
      <?php endif; ?>
    </div>

    <!-- Finished Garment Photo & Portfolio Card -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-camera me-1 text-primary"></i> Finished Garment Photo</h6>
        <?php if (!empty($order['completion_photo'])): ?>
          <span class="badge bg-success">Photo Attached</span>
        <?php endif; ?>
      </div>

      <?php if (!empty($order['completion_photo'])): ?>
        <div class="mb-3 text-center bg-light p-2 rounded-3 border">
          <a href="<?= BASE_URL ?>/<?= e($order['completion_photo']) ?>" target="_blank">
            <img src="<?= BASE_URL ?>/<?= e($order['completion_photo']) ?>" alt="Finished garment" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
          </a>
        </div>
        <!-- 1-Click Publish to Public Website Portfolio -->
        <form method="POST" action="" class="mb-3" id="publishToGalleryForm">
          <input type="hidden" name="action_publish_to_gallery" value="1">
          <button type="button" 
                  class="btn btn-outline-warning btn-sm w-100 text-dark fw-semibold btn-confirm-action"
                  data-title="Publish to Public Portfolio"
                  data-message="Publish this finished garment photograph directly to the public website portfolio showcase?"
                  data-confirm-text="Yes, Publish Showcase"
                  data-confirm-class="btn-warning text-dark"
                  data-form-id="publishToGalleryForm">
            <i class="bi bi-megaphone me-1 text-warning-emphasis"></i> 1-Click Publish to Public Gallery
          </button>
        </form>
      <?php endif; ?>

      <!-- Upload / Replace Photo Form -->
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action_upload_order_photo" value="1">
        <div class="mb-2">
          <label class="form-label small fw-bold"><?= !empty($order['completion_photo']) ? 'Replace Finished Photo' : 'Upload Finished Garment Photo' ?></label>
          <input type="file" name="completion_photo" class="form-control form-control-sm" accept="image/*" required>
          <div class="form-text small" style="font-size: 0.72rem;">Snap a photo on your phone or upload a picture of the finished suit/uniform.</div>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
          <i class="bi bi-upload me-1"></i> <?= !empty($order['completion_photo']) ? 'Upload New Photo' : 'Upload Finished Photo' ?>
        </button>
      </form>
    </div>

    <!-- Account Details -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
      <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Client Information</h6>
      <div class="mb-2">
        <label class="small text-muted d-block">Customer / School</label>
        <span class="fw-bold"><?= e($order['institution_name'] ?: $order['customer_name']) ?></span>
      </div>
      <div class="mb-2">
        <label class="small text-muted d-block">Contact Phone</label>
        <a href="tel:<?= e($order['phone']) ?>" class="text-decoration-none fw-semibold">
          <?= e($order['phone']) ?>
        </a>
      </div>
      <?php if ($order['delivery_address']): ?>
        <div class="mb-2">
          <label class="small text-muted d-block">Delivery Destination</label>
          <small class="text-secondary"><?= e($order['delivery_address']) ?></small>
        </div>
      <?php endif; ?>
      <?php if ($order['lead_source']): ?>
        <div class="mt-3 pt-2 border-top">
          <label class="small text-muted d-block">Marketing Channel Attribution</label>
          <span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $order['lead_source'])) ?></span>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<!-- Modal: Log Partial Delivery -->
<div class="modal fade" id="logDeliveryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action_log_delivery" value="1">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Log Uniform Delivery Batch</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Date of Delivery <span class="text-danger">*</span></label>
            <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Quantity Delivered in this Batch <span class="text-danger">*</span></label>
            <input type="number" name="quantity_delivered" class="form-control" min="1" max="<?= max(1, $order['total_quantity'] - $order['delivered_quantity']) ?>" value="<?= max(1, min(50, $order['total_quantity'] - $order['delivered_quantity'])) ?>" required>
            <div class="form-text small">Remaining undelivered: <?= max(0, $order['total_quantity'] - $order['delivered_quantity']) ?> units.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Recipient / Sign-off Storekeeper <span class="text-danger">*</span></label>
            <input type="text" name="recipient_name" class="form-control" placeholder="e.g. Mr. Bernard Kamau (School Storekeeper)" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Delivery Notes</label>
            <textarea name="delivery_notes" class="form-control" rows="2" placeholder="e.g. 50 blazers (size 34), delivery note signed and filed."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Record Batch Delivery</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action_record_payment" value="1">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Record Customer Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Payment Amount (KES) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control" value="<?= $balance_due ?>" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Date</label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Payment Method</label>
              <select name="payment_method" class="form-select">
                <option value="mpesa">M-Pesa</option>
                <option value="bank_transfer">Bank Transfer (EFT)</option>
                <option value="cheque">Institutional Cheque</option>
                <option value="cash">Cash</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Reference / Cheque Number</label>
            <input type="text" name="reference_number" class="form-control" placeholder="e.g. QJK8991LA or Cheque #00452">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Final balance cleared before delivery.">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold">Save Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
