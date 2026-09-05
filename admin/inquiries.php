<?php
/**
 * Leads & Inquiries CRM
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Leads & Inquiries CRM';
require_once __DIR__ . '/admin_header.php';

// Filter parameters
$status_filter = sanitize($_GET['status'] ?? '');
$source_filter = sanitize($_GET['source'] ?? '');
$search_query  = sanitize($_GET['q'] ?? '');

$sql = "SELECT * FROM leads WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($source_filter)) {
    $sql .= " AND source = ?";
    $params[] = $source_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (contact_person LIKE ? OR institution_name LIKE ? OR phone LIKE ? OR email LIKE ? OR garment_type LIKE ?)";
    $like = '%' . $search_query . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$sql .= " ORDER BY id DESC";

$leads = db_fetch_all($sql, $params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Marketing Inquiries & Lead Pipeline</h4>
    <p class="text-muted small mb-0">Track quotes, bulk uniform tenders, and marketing channel conversion.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/bulk_inquiry.php" target="_blank" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-box-arrow-up-right me-1"></i> Open Public Tender Form
    </a>
  </div>
</div>

<!-- Status Filter Pills & Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= BASE_URL ?>/admin/inquiries.php" class="row g-2 align-items-center">
      
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search by name, school, phone, or garment..." value="<?= e($search_query) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Statuses --</option>
          <option value="new" <?= $status_filter == 'new' ? 'selected' : '' ?>>New</option>
          <option value="contacted" <?= $status_filter == 'contacted' ? 'selected' : '' ?>>Contacted</option>
          <option value="quoted" <?= $status_filter == 'quoted' ? 'selected' : '' ?>>Quoted</option>
          <option value="converted" <?= $status_filter == 'converted' ? 'selected' : '' ?>>Converted</option>
          <option value="lost" <?= $status_filter == 'lost' ? 'selected' : '' ?>>Lost</option>
        </select>
      </div>

      <div class="col-md-3">
        <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Marketing Sources --</option>
          <option value="organic_search" <?= $source_filter == 'organic_search' ? 'selected' : '' ?>>Organic Search (Google)</option>
          <option value="social_facebook" <?= $source_filter == 'social_facebook' ? 'selected' : '' ?>>Facebook</option>
          <option value="social_instagram" <?= $source_filter == 'social_instagram' ? 'selected' : '' ?>>Instagram</option>
          <option value="social_tiktok" <?= $source_filter == 'social_tiktok' ? 'selected' : '' ?>>TikTok</option>
          <option value="whatsapp" <?= $source_filter == 'whatsapp' ? 'selected' : '' ?>>Direct WhatsApp</option>
          <option value="referral" <?= $source_filter == 'referral' ? 'selected' : '' ?>>Client Referral</option>
          <option value="walk_in" <?= $source_filter == 'walk_in' ? 'selected' : '' ?>>Walk-In / Showroom</option>
        </select>
      </div>

      <div class="col-md-1">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
      </div>

    </form>
  </div>
</div>

<!-- Leads List Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Ref / Date</th>
          <th>Client / Institution</th>
          <th>Garment Type</th>
          <th>Qty</th>
          <th>Target Date</th>
          <th>Source</th>
          <th>Status</th>
          <th>Follow-Up</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($leads)): ?>
          <?php foreach ($leads as $lead): ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/admin/inquiry_view.php?id=<?= $lead['id'] ?>" class="fw-bold text-decoration-none">
                  #<?= $lead['id'] ?>
                </a>
                <div class="small text-muted"><?= format_date($lead['created_at']) ?></div>
              </td>
              <td>
                <div class="fw-bold"><?= e($lead['contact_person']) ?></div>
                <?php if (!empty($lead['institution_name'])): ?>
                  <span class="badge bg-primary-subtle text-primary"><?= e($lead['institution_name']) ?></span>
                <?php else: ?>
                  <span class="badge bg-light text-dark border">Individual</span>
                <?php endif; ?>
                <div class="small text-muted mt-1">
                  <a href="tel:<?= e($lead['phone']) ?>" class="text-decoration-none text-secondary">
                    <i class="bi bi-telephone me-1"></i> <?= e($lead['phone']) ?>
                  </a>
                </div>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= e($lead['garment_type']) ?></div>
                <?php if (!empty($lead['sample_file'])): ?>
                  <span class="badge bg-info-subtle text-info"><i class="bi bi-image me-1"></i> Sample Attached</span>
                <?php endif; ?>
              </td>
              <td class="fw-bold text-center"><?= (int)$lead['estimated_quantity'] ?></td>
              <td><?= format_date($lead['target_deadline']) ?></td>
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
                <?php if ($lead['follow_up_date']): ?>
                  <span class="small fw-semibold <?= strtotime($lead['follow_up_date']) <= strtotime('today') ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <?= format_date($lead['follow_up_date']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="<?= e(whatsapp_url("Hello {$lead['contact_person']}! This is StitchCraft following up on your inquiry for {$lead['garment_type']}.")) ?>" 
                     target="_blank" class="btn btn-outline-success" title="Chat on WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                  </a>
                  <a href="<?= BASE_URL ?>/admin/inquiry_view.php?id=<?= $lead['id'] ?>" class="btn btn-outline-primary" title="View Full Details">
                    <i class="bi bi-eye"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">No inquiries found matching the selected filters.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
