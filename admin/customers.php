<?php
/**
 * Customer Directory
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Customers Directory';
require_once __DIR__ . '/admin_header.php';

// Handle Add Customer Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_customer'])) {
    $type = sanitize($_POST['type'] ?? 'individual');
    $name = sanitize($_POST['name'] ?? '');
    $institution_name = sanitize($_POST['institution_name'] ?? null);
    $contact_person   = sanitize($_POST['contact_person'] ?? $name);
    $email            = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
    $phone            = sanitize($_POST['phone'] ?? '');
    $whatsapp         = sanitize($_POST['whatsapp_number'] ?? $phone);
    $address          = sanitize($_POST['physical_address'] ?? '');
    $notes            = sanitize($_POST['notes'] ?? '');

    if (empty($name) || empty($phone)) {
        set_flash_message('danger', 'Customer Name and Phone number are required.');
    } else {
        $newId = db_insert('customers', [
            'type'             => $type,
            'name'             => $name,
            'institution_name' => $institution_name,
            'contact_person'   => $contact_person,
            'email'            => $email,
            'phone'            => $phone,
            'whatsapp_number'  => $whatsapp,
            'physical_address' => $address,
            'notes'            => $notes
        ]);

        if ($newId) {
            set_flash_message('success', 'Customer profile created successfully.');
            header("Location: " . BASE_URL . "/admin/customer_view.php?id=" . $newId);
            exit;
        } else {
            set_flash_message('danger', 'Failed to create customer.');
        }
    }
}

// Search and Filter
$type_filter = sanitize($_GET['type'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$sql = "
    SELECT c.*, 
           COUNT(DISTINCT o.id) as orders_count, 
           COUNT(DISTINCT m.id) as measurements_count 
    FROM customers c 
    LEFT JOIN orders o ON c.id = o.customer_id 
    LEFT JOIN measurement_profiles m ON c.id = m.customer_id 
    WHERE 1=1
";
$params = [];

if (!empty($type_filter)) {
    $sql .= " AND c.type = ?";
    $params[] = $type_filter;
}

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.institution_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$customers = db_fetch_all($sql, $params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Customer & Institutional Accounts</h4>
    <p class="text-muted small mb-0">Unified directory of schools, corporate bodies, and private bespoke clients.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
    <i class="bi bi-person-plus me-1"></i> Add New Customer
  </button>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= BASE_URL ?>/admin/customers.php" class="row g-2 align-items-center">
      <div class="col-md-7">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search by customer name, school, phone..." value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Account Types --</option>
          <option value="institution" <?= $type_filter == 'institution' ? 'selected' : '' ?>>Institutions (Schools / Corporates)</option>
          <option value="individual" <?= $type_filter == 'individual' ? 'selected' : '' ?>>Individuals (Bespoke)</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Search</button>
      </div>
    </form>
  </div>
</div>

<!-- Customers Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Account Name</th>
          <th>Type</th>
          <th>Contact Info</th>
          <th>Measurements</th>
          <th>Total Orders</th>
          <th>Created</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($customers)): ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><span class="text-muted">#<?= $c['id'] ?></span></td>
              <td>
                <a href="<?= BASE_URL ?>/admin/customer_view.php?id=<?= $c['id'] ?>" class="fw-bold text-decoration-none">
                  <?= e($c['institution_name'] ?: $c['name']) ?>
                </a>
                <?php if ($c['institution_name'] && $c['contact_person']): ?>
                  <div class="small text-muted">Attn: <?= e($c['contact_person']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['type'] == 'institution'): ?>
                  <span class="badge bg-primary-subtle text-primary">Institution</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary">Individual</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="tel:<?= e($c['phone']) ?>" class="text-decoration-none text-dark small d-block">
                  <i class="bi bi-telephone me-1"></i> <?= e($c['phone']) ?>
                </a>
                <?php if ($c['email']): ?>
                  <small class="text-muted"><?= e($c['email']) ?></small>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-light text-dark border">
                  <?= (int)$c['measurements_count'] ?> profiles
                </span>
              </td>
              <td class="text-center">
                <span class="badge bg-light text-primary border fw-bold">
                  <?= (int)$c['orders_count'] ?> orders
                </span>
              </td>
              <td><small class="text-muted"><?= format_date($c['created_at']) ?></small></td>
              <td>
                <a href="<?= BASE_URL ?>/admin/customer_view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                  View Profile
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-5">No customer accounts found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New Customer -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action_add_customer" value="1">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Customer Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Account Type</label>
            <select name="type" class="form-select" id="cust_type_select" required>
              <option value="individual">Individual (Bespoke Suit / Private)</option>
              <option value="institution">Institution (School / Corporate / Security)</option>
            </select>
          </div>
          <div class="mb-3" id="institution_name_group" style="display: none;">
            <label class="form-label small fw-bold">Institution Name</label>
            <input type="text" name="institution_name" class="form-control" placeholder="e.g. St. Jude Academy">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Client / Contact Person Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="Full Name">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control" required placeholder="0712 345 678">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">WhatsApp Number</label>
              <input type="tel" name="whatsapp_number" class="form-control" placeholder="0712 345 678">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="client@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Delivery / Showroom Address</label>
            <textarea name="physical_address" class="form-control" rows="2" placeholder="Street, Building, Town"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const typeSelect = document.getElementById('cust_type_select');
  const instGroup = document.getElementById('institution_name_group');
  if (typeSelect && instGroup) {
    typeSelect.addEventListener('change', function () {
      if (this.value === 'institution') {
        instGroup.style.display = 'block';
      } else {
        instGroup.style.display = 'none';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
