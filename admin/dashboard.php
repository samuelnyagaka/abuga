<?php
/**
 * Admin Dashboard - StitchCraft Operations
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Dashboard Overview';
require_once __DIR__ . '/admin_header.php';

// Calculate KPI Metrics
$total_leads_month = (int)db_scalar("SELECT COUNT(*) FROM leads WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$active_orders = (int)db_scalar("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'in_production')");
$total_revenue_month = (float)db_scalar("SELECT SUM(amount) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
$upcoming_deadlines = (int)db_scalar("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'in_production') AND deadline <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");

// Fetch Overdue / Today Follow-ups
$follow_ups_due = db_fetch_all("
    SELECT id, customer_type, institution_name, contact_person, phone, garment_type, follow_up_date, status 
    FROM leads 
    WHERE status IN ('new', 'contacted', 'quoted') 
      AND follow_up_date IS NOT NULL 
      AND follow_up_date <= CURRENT_DATE() 
    ORDER BY follow_up_date ASC 
    LIMIT 5
");

// Fetch Recent Inquiries
$recent_leads = db_fetch_all("
    SELECT * FROM leads 
    ORDER BY id DESC 
    LIMIT 6
");

// Fetch Active Production Orders
$production_orders = db_fetch_all("
    SELECT o.*, c.name as customer_name, c.institution_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.status IN ('confirmed', 'in_production', 'ready_for_pickup') 
    ORDER BY o.deadline ASC 
    LIMIT 5
");

// Fetch Low Stock Alerts
$low_stock_items = db_fetch_all("
    SELECT * FROM inventory 
    WHERE quantity_on_hand <= reorder_threshold 
    ORDER BY quantity_on_hand ASC 
    LIMIT 4
");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-1">Welcome back, <?= e($user['full_name']) ?>!</h3>
    <p class="text-muted small mb-0">Here is an operational overview of current uniform contracts, leads, and workshop queues.</p>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <a href="<?= BASE_URL ?>/admin/inquiries.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-inbox me-1"></i> Review Leads
    </a>
    <a href="<?= BASE_URL ?>/admin/order_create.php" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i> Create New Order
    </a>
  </div>
</div>

<!-- KPI Cards Grid -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="kpi-title">Leads This Month</div>
          <div class="kpi-value text-primary"><?= $total_leads_month ?></div>
        </div>
        <div class="kpi-icon bg-primary-subtle text-primary">
          <i class="bi bi-person-plus-fill"></i>
        </div>
      </div>
      <div class="small text-muted mt-2">
        <span class="text-danger fw-semibold"><?= $new_leads_count ?></span> requiring initial review
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="kpi-title">Orders in Production</div>
          <div class="kpi-value text-warning"><?= $active_orders ?></div>
        </div>
        <div class="kpi-icon bg-warning-subtle text-warning-emphasis">
          <i class="bi bi-gear-wide-connected"></i>
        </div>
      </div>
      <div class="small text-muted mt-2">
        <span class="text-primary fw-semibold"><?= $upcoming_deadlines ?></span> orders due within 7 days
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="kpi-title">Monthly Collections</div>
          <div class="kpi-value text-success">
            <?php if (has_role('admin')): ?>
              <?= format_currency($total_revenue_month) ?>
            <?php else: ?>
              <span class="fs-5 text-muted">Restricted</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="kpi-icon bg-success-subtle text-success">
          <i class="bi bi-cash-stack"></i>
        </div>
      </div>
      <div class="small text-muted mt-2">
        M-Pesa, EFT, & Cheque receipts
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="kpi-card">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="kpi-title">Materials Alerts</div>
          <div class="kpi-value <?= $low_stock_count > 0 ? 'text-danger' : 'text-secondary' ?>"><?= $low_stock_count ?></div>
        </div>
        <div class="kpi-icon <?= $low_stock_count > 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-secondary' ?>">
          <i class="bi bi-exclamation-octagon-fill"></i>
        </div>
      </div>
      <div class="small text-muted mt-2">
        Fabrics / trims at reorder point
      </div>
    </div>
  </div>
</div>

<!-- Reminders & Low Stock Alerts Row -->
<?php if (!empty($follow_ups_due) || !empty($low_stock_items)): ?>
  <div class="row g-3 mb-4">
    <?php if (!empty($follow_ups_due)): ?>
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-header bg-warning bg-opacity-25 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history text-warning-emphasis me-2"></i> Follow-Up Reminders Due Today</h6>
            <span class="badge bg-warning text-dark"><?= count($follow_ups_due) ?> Pending</span>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <?php foreach ($follow_ups_due as $f): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                  <div>
                    <h6 class="mb-1 fw-bold">
                      <?= e($f['institution_name'] ? "{$f['institution_name']} ({$f['contact_person']})" : $f['contact_person']) ?>
                    </h6>
                    <div class="small text-muted">
                      <?= e($f['garment_type']) ?> &bull; Phone: <a href="tel:<?= e($f['phone']) ?>" class="text-decoration-none"><?= e($f['phone']) ?></a>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <a href="<?= e(whatsapp_url("Hello {$f['contact_person']}! This is StitchCraft checking in regarding your uniform inquiry.")) ?>" 
                       target="_blank" class="btn btn-xs btn-success py-1 px-2" title="WhatsApp">
                      <i class="bi bi-whatsapp"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/inquiry_view.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                      View
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($low_stock_items)): ?>
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-header bg-danger bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-box-seam me-2"></i> Low Fabric & Materials Warning</h6>
            <a href="<?= BASE_URL ?>/admin/inventory.php" class="small text-decoration-none text-danger fw-semibold">View Inventory</a>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <?php foreach ($low_stock_items as $item): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                  <div>
                    <span class="badge bg-secondary mb-1"><?= e($item['sku_code']) ?></span>
                    <h6 class="mb-0 fw-bold"><?= e($item['item_name']) ?></h6>
                    <small class="text-muted">Supplier: <?= e($item['supplier_name'] ?: 'Local Market') ?></small>
                  </div>
                  <div class="text-end">
                    <span class="text-danger fw-bold fs-6"><?= (float)$item['quantity_on_hand'] ?> <?= e($item['unit']) ?></span>
                    <div class="small text-muted">Threshold: <?= (float)$item['reorder_threshold'] ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Active Orders Pipeline Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-kanban me-2 text-primary"></i> Active Production Orders & Delivery Progress</h6>
    <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline-primary btn-sm">All Orders</a>
  </div>
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Client / School</th>
          <th>Order Title</th>
          <th>Delivery Progress</th>
          <th>Target Deadline</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($production_orders)): ?>
          <?php foreach ($production_orders as $ord): ?>
            <?php 
              $percent = $ord['total_quantity'] > 0 ? min(100, round(($ord['delivered_quantity'] / $ord['total_quantity']) * 100)) : 0;
            ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $ord['id'] ?>" class="fw-bold text-decoration-none">
                  <?= e($ord['order_number']) ?>
                </a>
              </td>
              <td>
                <div class="fw-semibold"><?= e($ord['institution_name'] ?: $ord['customer_name']) ?></div>
                <small class="text-muted"><?= e(ucfirst($ord['order_type'])) ?></small>
              </td>
              <td><?= e($ord['order_title']) ?></td>
              <td style="min-width: 170px;">
                <div class="d-flex justify-content-between small mb-1">
                  <span><strong><?= (int)$ord['delivered_quantity'] ?></strong> / <?= (int)$ord['total_quantity'] ?> units</span>
                  <span class="text-primary fw-bold"><?= $percent ?>%</span>
                </div>
                <div class="delivery-progress-container">
                  <div class="delivery-progress-bar" style="width: <?= $percent ?>%;"></div>
                </div>
              </td>
              <td>
                <span class="fw-semibold <?= strtotime($ord['deadline']) <= strtotime('+3 days') ? 'text-danger' : 'text-dark' ?>">
                  <?= format_date($ord['deadline']) ?>
                </span>
              </td>
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
            <td colspan="7" class="text-center text-muted py-4">No active orders currently in production.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Leads Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><i class="bi bi-inbox me-2 text-primary"></i> Incoming Marketing Inquiries</h6>
    <a href="<?= BASE_URL ?>/admin/inquiries.php" class="btn btn-outline-primary btn-sm">View All Leads</a>
  </div>
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Ref / Date</th>
          <th>Lead Contact</th>
          <th>Garment Type</th>
          <th>Est. Qty</th>
          <th>Source</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recent_leads)): ?>
          <?php foreach ($recent_leads as $lead): ?>
            <tr>
              <td>
                <div class="fw-bold text-primary">#<?= $lead['id'] ?></div>
                <small class="text-muted"><?= format_date($lead['created_at']) ?></small>
              </td>
              <td>
                <div class="fw-bold"><?= e($lead['contact_person']) ?></div>
                <?php if ($lead['institution_name']): ?>
                  <span class="badge bg-primary-subtle text-primary"><?= e($lead['institution_name']) ?></span>
                <?php endif; ?>
                <div class="small text-muted"><?= e($lead['phone']) ?></div>
              </td>
              <td><?= e($lead['garment_type']) ?></td>
              <td class="fw-bold"><?= (int)$lead['estimated_quantity'] ?></td>
              <td>
                <span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $lead['source'])) ?></span>
              </td>
              <td>
                <?php 
                  $leadStatusBadges = [
                    'new'       => '<span class="badge bg-danger">New</span>',
                    'contacted' => '<span class="badge bg-warning text-dark">Contacted</span>',
                    'quoted'    => '<span class="badge bg-info text-dark">Quoted</span>',
                    'converted' => '<span class="badge bg-success">Converted</span>',
                    'lost'      => '<span class="badge bg-secondary">Lost</span>',
                  ];
                  echo $leadStatusBadges[$lead['status']] ?? $lead['status'];
                ?>
              </td>
              <td>
                <a href="<?= BASE_URL ?>/admin/inquiry_view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary">
                  Review
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No recent inquiries found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
