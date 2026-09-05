<?php
/**
 * Order Pipeline & Management
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Orders & Production Pipeline';
require_once __DIR__ . '/admin_header.php';

$status_filter = sanitize($_GET['status'] ?? '');
$type_filter   = sanitize($_GET['type'] ?? '');
$search        = sanitize($_GET['q'] ?? '');

$sql = "
    SELECT o.*, c.name as customer_name, c.institution_name, c.phone as customer_phone 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND o.order_type = ?";
    $params[] = $type_filter;
}

if (!empty($search)) {
    $sql .= " AND (o.order_number LIKE ? OR o.order_title LIKE ? OR c.name LIKE ? OR c.institution_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " ORDER BY o.id DESC";
$orders = db_fetch_all($sql, $params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Production Pipeline & Orders</h4>
    <p class="text-muted small mb-0">Monitor cutting, sewing, partial deliveries, and invoice settlements.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/order_create.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i> Create New Order
  </a>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= BASE_URL ?>/admin/orders.php" class="row g-2 align-items-center">
      
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search order #, title, school, or client..." value="<?= e($search) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Pipeline Stages --</option>
          <option value="quoted" <?= $status_filter == 'quoted' ? 'selected' : '' ?>>Quoted (Proforma)</option>
          <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed (Deposit Paid)</option>
          <option value="in_production" <?= $status_filter == 'in_production' ? 'selected' : '' ?>>In Production (Cutting/Sewing)</option>
          <option value="ready_for_pickup" <?= $status_filter == 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Delivery</option>
          <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="col-md-3">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Order Types --</option>
          <option value="school_uniform" <?= $type_filter == 'school_uniform' ? 'selected' : '' ?>>School Uniforms</option>
          <option value="corporate_uniform" <?= $type_filter == 'corporate_uniform' ? 'selected' : '' ?>>Corporate Attire</option>
          <option value="bespoke_suit" <?= $type_filter == 'bespoke_suit' ? 'selected' : '' ?>>Bespoke Custom Suit</option>
          <option value="general_clothing" <?= $type_filter == 'general_clothing' ? 'selected' : '' ?>>General Tailoring</option>
          <option value="security_workwear" <?= $type_filter == 'security_workwear' ? 'selected' : '' ?>>Security & Workwear</option>
        </select>
      </div>

      <div class="col-md-1">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
      </div>

    </form>
  </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Account / Customer</th>
          <th>Order Title</th>
          <th>Delivery Progress</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Deadline</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $o): ?>
            <?php 
              $percent = $o['total_quantity'] > 0 ? min(100, round(($o['delivered_quantity'] / $o['total_quantity']) * 100)) : 0;
            ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $o['id'] ?>" class="fw-bold text-decoration-none">
                  <?= e($o['order_number']) ?>
                </a>
              </td>
              <td>
                <div class="fw-semibold"><?= e($o['institution_name'] ?: $o['customer_name']) ?></div>
                <small class="text-muted"><?= e($o['customer_phone']) ?></small>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= e($o['order_title']) ?></div>
                <span class="badge bg-light text-dark border small"><?= e(ucwords(str_replace('_', ' ', $o['order_type']))) ?></span>
              </td>
              <td style="min-width: 160px;">
                <div class="d-flex justify-content-between small mb-1">
                  <span><strong><?= (int)$o['delivered_quantity'] ?></strong> / <?= (int)$o['total_quantity'] ?></span>
                  <span class="text-primary fw-bold"><?= $percent ?>%</span>
                </div>
                <div class="delivery-progress-container">
                  <div class="delivery-progress-bar" style="width: <?= $percent ?>%;"></div>
                </div>
              </td>
              <td class="fw-bold">
                <?php if (has_role('admin')): ?>
                  <?= format_currency($o['total_amount']) ?>
                  <?php if ($o['total_amount'] - $o['paid_amount'] > 0): ?>
                    <small class="d-block text-danger font-monospace" style="font-size: 0.72rem;">Bal: <?= format_currency($o['total_amount'] - $o['paid_amount']) ?></small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= get_payment_status_badge($o['payment_status']) ?></td>
              <td>
                <span class="small <?= strtotime($o['deadline']) <= strtotime('+3 days') ? 'text-danger fw-bold' : 'text-dark' ?>">
                  <?= format_date($o['deadline']) ?>
                </span>
              </td>
              <td><?= get_order_status_badge($o['status']) ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="<?= BASE_URL ?>/admin/order_view.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary" title="View & Manage">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?= BASE_URL ?>/admin/invoice.php?order_id=<?= $o['id'] ?>" class="btn btn-outline-secondary" title="Print Invoice / Proforma">
                    <i class="bi bi-printer"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">No orders found matching the criteria.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
