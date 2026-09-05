<?php
/**
 * Professional Printable Invoice & Quotation
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) {
    die("Invalid Order ID.");
}

$order = db_fetch_one("
    SELECT o.*, c.name as customer_name, c.institution_name, c.contact_person, c.phone, c.email, c.physical_address 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = ? 
    LIMIT 1
", [$order_id]);

if (!$order) {
    die("Order record not found.");
}

$items = db_fetch_all("SELECT * FROM order_items WHERE order_id = ?", [$order_id]);
$invoice = db_fetch_one("SELECT * FROM invoices WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$order_id]);

$doc_type = 'TAX INVOICE';
if ($order['status'] === 'lead' || $order['status'] === 'quoted') {
    $doc_type = 'OFFICIAL PROFORMA QUOTATION';
} elseif ($order['payment_status'] === 'paid') {
    $doc_type = 'INVOICE & RECEIPT';
}

$invoiceNumber = $invoice['invoice_number'] ?? 'INV-' . date('Y') . '-' . str_pad($order['id'], 4, '0', STR_PAD_LEFT);
$balanceDue = max(0, $order['total_amount'] - $order['paid_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($doc_type) ?> - <?= e($order['order_number']) ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      color: #0f172a;
      padding: 2rem 0;
    }
    .invoice-card {
      max-width: 800px;
      margin: 0 auto;
      background: #ffffff;
      padding: 3rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      border: 1px solid #e2e8f0;
    }
    .table-invoice th {
      background-color: #f1f5f9;
      color: #334155;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .payment-box {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 1.25rem;
    }
    @media print {
      body {
        background: #ffffff;
        padding: 0;
      }
      .invoice-card {
        border: none;
        box-shadow: none;
        padding: 0;
        max-width: 100%;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- Top Action Bar for screen view -->
  <div class="container text-center mb-4 no-print" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center">
      <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $order['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Order
      </a>
      <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
          <i class="bi bi-printer-fill me-1"></i> Print / Save PDF
        </button>
      </div>
    </div>
  </div>

  <div class="invoice-card">
    
    <!-- Invoice Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
      <div>
        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= e(BUSINESS_NAME) ?>" height="50" class="mb-2">
        <div class="small text-muted">
          <?= e(BUSINESS_NAME) ?><br>
          <?= e(BUSINESS_ADDRESS) ?><br>
          Tel: <?= e(BUSINESS_PHONE) ?> | Email: <?= e(BUSINESS_EMAIL) ?>
        </div>
      </div>
      <div class="text-end">
        <h4 class="fw-bold text-primary mb-1" style="font-family: 'Outfit', sans-serif;"><?= e($doc_type) ?></h4>
        <div class="fs-6 fw-bold"><?= e($invoiceNumber) ?></div>
        <div class="small text-muted">Order Ref: <strong><?= e($order['order_number']) ?></strong></div>
        <div class="small text-muted">Date: <?= format_date($order['created_at']) ?></div>
        <div class="small text-muted">Due Date: <?= format_date($order['deadline']) ?></div>
      </div>
    </div>

    <!-- Client & Delivery Destination -->
    <div class="row mb-4">
      <div class="col-6">
        <h6 class="small text-muted text-uppercase fw-bold mb-2">Billed To:</h6>
        <div class="fw-bold fs-6"><?= e($order['institution_name'] ?: $order['customer_name']) ?></div>
        <?php if ($order['contact_person'] && $order['institution_name']): ?>
          <div class="small text-secondary">Attn: <?= e($order['contact_person']) ?></div>
        <?php endif; ?>
        <div class="small text-secondary">Phone: <?= e($order['phone']) ?></div>
        <?php if ($order['email']): ?>
          <div class="small text-secondary"><?= e($order['email']) ?></div>
        <?php endif; ?>
      </div>
      <div class="col-6 text-end">
        <h6 class="small text-muted text-uppercase fw-bold mb-2">Delivery Destination:</h6>
        <div class="small text-secondary"><?= nl2br(e($order['delivery_address'] ?: 'Workshop Moi Avenue Showroom')) ?></div>
        <div class="mt-2">
          <span class="badge bg-light text-dark border">
            Status: <?= strtoupper($order['status']) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-invoice align-middle">
        <thead>
          <tr>
            <th style="width: 50%;">Garment Description & Specifications</th>
            <th class="text-center" style="width: 15%;">Size</th>
            <th class="text-center" style="width: 10%;">Qty</th>
            <th class="text-end" style="width: 12%;">Unit Price</th>
            <th class="text-end" style="width: 13%;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($item['item_title']) ?></div>
                <?php if (!empty($item['fabric_details'])): ?>
                  <small class="text-muted d-block"><?= e($item['fabric_details']) ?></small>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= e($item['size_label']) ?></td>
              <td class="text-center fw-bold"><?= (int)$item['quantity'] ?></td>
              <td class="text-end"><?= format_currency($item['unit_price']) ?></td>
              <td class="text-end fw-bold"><?= format_currency($item['subtotal_price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals Calculation -->
    <div class="row justify-content-end mb-4">
      <div class="col-md-5">
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Subtotal:</span>
          <span><?= format_currency($order['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">VAT (0% / Inclusive):</span>
          <span>KSh 0.00</span>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom fw-bold fs-6">
          <span>Total Order Value:</span>
          <span class="text-primary"><?= format_currency($order['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small text-success">
          <span>Amount Paid / Deposit:</span>
          <span>- <?= format_currency($order['paid_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-2 fw-bold fs-6">
          <span>Balance Outstanding:</span>
          <span class="<?= $balanceDue > 0 ? 'text-danger' : 'text-success' ?>">
            <?= format_currency($balanceDue) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Bank / M-Pesa Payment Instructions -->
    <div class="payment-box mb-4">
      <h6 class="fw-bold mb-2 small text-dark"><i class="bi bi-bank me-1 text-primary"></i> Payment Details & Instructions:</h6>
      <div class="row g-2 small text-secondary">
        <div class="col-sm-6">
          <strong>M-Pesa Buy Goods / Paybill:</strong><br>
          Business No: <code>522522</code> | Account: <code>STITCH-<?= e($order['order_number']) ?></code>
        </div>
        <div class="col-sm-6">
          <strong>Bank Transfer (Stanbic Kenya):</strong><br>
          A/C Name: StitchCraft Masters Ltd | A/C: <code>0100098234710</code>
        </div>
      </div>
    </div>

    <!-- Terms & Signature -->
    <div class="d-flex justify-content-between align-items-end pt-4 border-top text-muted small">
      <div>
        <p class="mb-0">
          <em>Thank you for partnering with <?= e(BUSINESS_NAME) ?>. Quality guaranteed.</em>
        </p>
      </div>
      <div class="text-center" style="min-width: 180px;">
        <div style="height: 35px;"></div>
        <div class="border-top pt-1 text-dark fw-bold">Authorized Signatory</div>
      </div>
    </div>

  </div>

</body>
</html>
