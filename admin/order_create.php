<?php
/**
 * Create New Order Wizard
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Create New Order';
require_once __DIR__ . '/admin_header.php';

$preselected_customer = (int)($_GET['customer_id'] ?? 0);
$preselected_lead     = (int)($_GET['lead_id'] ?? 0);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id          = (int)($_POST['customer_id'] ?? 0);
    $lead_id              = !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
    $order_title          = sanitize($_POST['order_title'] ?? '');
    $order_type           = sanitize($_POST['order_type'] ?? 'school_uniform');
    $deadline             = !empty($_POST['deadline']) ? sanitize($_POST['deadline']) : null;
    $delivery_address     = sanitize($_POST['delivery_address'] ?? '');
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    $deposit_amount       = (float)($_POST['deposit_amount'] ?? 0.00);
    $payment_method       = sanitize($_POST['payment_method'] ?? 'mpesa');
    $payment_ref          = sanitize($_POST['payment_ref'] ?? '');

    $items = $_POST['items'] ?? [];

    if (!$customer_id || empty($order_title) || empty($items)) {
        set_flash_message('danger', 'Please select a customer, enter an order title, and add at least one line item.');
    } else {
        // Calculate totals
        $totalQuantity = 0;
        $totalAmount = 0.00;

        foreach ($items as $item) {
            $qty   = max(1, (int)($item['quantity'] ?? 1));
            $price = max(0, (float)($item['price'] ?? 0));
            $totalQuantity += $qty;
            $totalAmount   += ($qty * $price);
        }

        // Generate clean unique Order Number
        $orderNumber = 'ORD-' . date('Y') . '-' . rand(1000, 9999);

        // Payment status
        $payment_status = 'unpaid';
        if ($deposit_amount >= $totalAmount && $totalAmount > 0) {
            $payment_status = 'paid';
        } elseif ($deposit_amount > 0) {
            $payment_status = 'partial';
        }

        // Initial pipeline status
        $initialStatus = ($deposit_amount > 0) ? 'confirmed' : 'quoted';

        // Insert Order
        $orderId = db_insert('orders', [
            'order_number'         => $orderNumber,
            'customer_id'          => $customer_id,
            'lead_id'              => $lead_id,
            'order_title'          => $order_title,
            'order_type'           => $order_type,
            'status'               => $initialStatus,
            'total_quantity'       => $totalQuantity,
            'delivered_quantity'   => 0,
            'total_amount'         => $totalAmount,
            'deposit_amount'       => $deposit_amount,
            'paid_amount'          => $deposit_amount,
            'payment_status'       => $payment_status,
            'payment_method'       => $payment_method,
            'deadline'             => $deadline,
            'production_start_date'=> date('Y-m-d'),
            'delivery_address'     => $delivery_address,
            'special_instructions' => $special_instructions,
            'created_by'           => $user['id']
        ]);

        if ($orderId) {
            // Insert line items
            foreach ($items as $item) {
                $itemTitle  = sanitize($item['title'] ?? 'Uniform Item');
                $sizeLabel  = sanitize($item['size'] ?? 'Standard');
                $qty        = max(1, (int)($item['quantity'] ?? 1));
                $price      = max(0, (float)($item['price'] ?? 0));
                $subtotal   = $qty * $price;
                $fabricInfo = sanitize($item['fabric'] ?? '');

                db_insert('order_items', [
                    'order_id'           => $orderId,
                    'item_title'         => $itemTitle,
                    'size_label'         => $sizeLabel,
                    'quantity'           => $qty,
                    'delivered_quantity' => 0,
                    'unit_price'         => $price,
                    'subtotal_price'     => $subtotal,
                    'fabric_details'     => $fabricInfo
                ]);
            }

            // Create initial invoice record
            $invoiceNumber = 'INV-' . date('Y') . '-' . rand(1000, 9999);
            $invoiceId = db_insert('invoices', [
                'invoice_number'  => $invoiceNumber,
                'order_id'        => $orderId,
                'customer_id'     => $customer_id,
                'type'            => ($deposit_amount > 0) ? 'invoice' : 'quote',
                'issue_date'      => date('Y-m-d'),
                'due_date'        => $deadline,
                'subtotal'        => $totalAmount,
                'tax_amount'      => 0.00,
                'discount_amount' => 0.00,
                'total_amount'    => $totalAmount,
                'status'          => ($payment_status === 'paid') ? 'paid' : (($deposit_amount > 0) ? 'partial' : 'sent')
            ]);

            // If deposit was paid, log payment
            if ($deposit_amount > 0) {
                db_insert('payments', [
                    'order_id'         => $orderId,
                    'invoice_id'       => $invoiceId,
                    'amount'           => $deposit_amount,
                    'payment_date'     => date('Y-m-d'),
                    'payment_method'   => $payment_method,
                    'reference_number' => $payment_ref,
                    'recorded_by'      => $user['id'],
                    'notes'            => 'Initial deposit recorded upon order creation.'
                ]);
            }

            // If linked to lead, update lead status to 'converted'
            if ($lead_id) {
                db_update('leads', ['status' => 'converted'], 'id = ?', [$lead_id]);
            }

            set_flash_message('success', "Order {$orderNumber} successfully created!");
            header("Location: " . BASE_URL . "/admin/order_view.php?id=" . $orderId);
            exit;
        } else {
            set_flash_message('danger', 'Failed to save order. Please check database connectivity.');
        }
    }
}

// Fetch Customers
$customers = db_fetch_all("SELECT id, name, institution_name, type, phone FROM customers ORDER BY id DESC");

// Fetch Available Leads for Attribution
$available_leads = db_fetch_all("SELECT id, contact_person, institution_name, garment_type, source FROM leads ORDER BY id DESC LIMIT 30");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="<?= BASE_URL ?>/admin/orders.php" class="text-decoration-none text-muted small mb-1 d-inline-block">
      <i class="bi bi-arrow-left me-1"></i> Back to Orders Pipeline
    </a>
    <h4 class="fw-bold mb-0">Create New Production Order</h4>
  </div>
</div>

<form method="POST" action="" class="needs-validation" novalidate>
  <div class="row g-4">
    
    <!-- Left Column: Order Metadata & Customer -->
    <div class="col-lg-8">
      
      <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Account & Order Overview</h6>
        
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Customer Account <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select" required>
              <option value="">-- Select Customer / School --</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $preselected_customer == $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['institution_name'] ? "{$c['institution_name']} ({$c['name']})" : $c['name']) ?> &bull; <?= e($c['phone']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Marketing Lead Attribution</label>
            <select name="lead_id" class="form-select">
              <option value="">-- Direct / Existing Client (No Lead) --</option>
              <?php foreach ($available_leads as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $preselected_lead == $l['id'] ? 'selected' : '' ?>>
                  #<?= $l['id'] ?>: <?= e($l['institution_name'] ?: $l['contact_person']) ?> (<?= e(str_replace('_', ' ', $l['source'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small">Attributes revenue back to the marketing channel for ROI tracking.</div>
          </div>

          <div class="col-md-8">
            <label class="form-label small fw-bold">Order Title <span class="text-danger">*</span></label>
            <input type="text" name="order_title" class="form-control" placeholder="e.g. Term 1 School Blazers & Uniforms (350 Sets)" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-bold">Order Type</label>
            <select name="order_type" class="form-select">
              <option value="school_uniform">School Uniforms</option>
              <option value="corporate_uniform">Corporate Attire</option>
              <option value="bespoke_suit">Bespoke Suit</option>
              <option value="general_clothing">General Tailoring</option>
              <option value="security_workwear">Security & Workwear</option>
            </select>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Target Delivery Deadline</label>
            <input type="date" name="deadline" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Delivery Location</label>
            <input type="text" name="delivery_address" class="form-control" placeholder="e.g. School Campus Store / Workshop Moi Ave">
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold">Special Instructions / Crest Embroidery Details</label>
            <textarea name="special_instructions" class="form-control" rows="2" placeholder="e.g. School crest embroidered on left chest pocket. Gold thread. Size breakdown attached below."></textarea>
          </div>
        </div>
      </div>

      <!-- Line Items & Sizing Breakdown -->
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
          <h6 class="fw-bold text-primary mb-0">2. Garment Line Items & Sizing Matrix</h6>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btn_add_order_item">
            <i class="bi bi-plus-lg me-1"></i> Add Garment / Size
          </button>
        </div>

        <div id="order_items_container">
          <!-- Initial Row 1 -->
          <div class="row g-2 align-items-center mb-2 order-item-row border-bottom pb-2">
            <div class="col-md-4">
              <label class="form-label small fw-bold d-md-none">Garment</label>
              <input type="text" name="items[0][title]" class="form-control form-control-sm" placeholder="Garment (e.g. School Blazer)" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold d-md-none">Size</label>
              <input type="text" name="items[0][size]" class="form-control form-control-sm" placeholder="Size (e.g. 34 / M)" value="Size 34" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold d-md-none">Quantity</label>
              <input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" value="50" min="1" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold d-md-none">Unit Price</label>
              <input type="number" step="0.01" name="items[0][price]" class="form-control form-control-sm item-price" placeholder="Price" value="3200" required>
            </div>
            <div class="col-md-1 text-end">
              <span class="small fw-bold item-subtotal">KSh 160,000</span>
            </div>
            <div class="col-md-1 text-center">
              <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button>
            </div>
          </div>
        </div>

        <!-- Summary Totals -->
        <div class="d-flex justify-content-between align-items-center pt-3 mt-2 bg-light p-3 rounded-3">
          <div>
            <span class="small text-muted">Total Units:</span>
            <strong id="order_total_qty" class="text-dark fs-6 ms-1">50</strong>
          </div>
          <div>
            <span class="small text-muted">Order Grand Total:</span>
            <strong id="order_grand_total" class="text-primary fs-5 ms-1">KSh 160,000.00</strong>
          </div>
        </div>

      </div>

    </div>

    <!-- Right Column: Financials & Deposit -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Deposit & Mobilization</h6>

        <div class="mb-3">
          <label class="form-label small fw-bold">Deposit Paid Now (KES)</label>
          <input type="number" step="0.01" name="deposit_amount" id="order_deposit_amount" class="form-control" value="80000.00">
          <div class="form-text small">Recommended 50% mobilization commitment for fabric purchase.</div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Payment Method</label>
          <select name="payment_method" class="form-select form-select-sm">
            <option value="mpesa">M-Pesa Paybill / Buy Goods</option>
            <option value="bank_transfer" selected>Bank Transfer (EFT / RTGS)</option>
            <option value="cheque">Institutional Cheque</option>
            <option value="cash">Cash Showroom Receipt</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">Transaction Reference Code</label>
          <input type="text" name="payment_ref" class="form-control form-control-sm" placeholder="e.g. QJK8991LA or STANBIC-891">
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
          <i class="bi bi-check2-circle me-1"></i> Confirm & Launch Order
        </button>

      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
