<?php
/**
 * Lead Detail View & Conversion
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Inquiry Details';
require_once __DIR__ . '/admin_header.php';

$lead_id = (int)($_GET['id'] ?? 0);
if (!$lead_id) {
    header("Location: " . BASE_URL . "/admin/inquiries.php");
    exit;
}

$lead = db_fetch_one("SELECT * FROM leads WHERE id = ? LIMIT 1", [$lead_id]);
if (!$lead) {
    set_flash_message('danger', 'Lead record not found.');
    header("Location: " . BASE_URL . "/admin/inquiries.php");
    exit;
}

// Handle Status & Follow-Up Date Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_lead'])) {
    $new_status = sanitize($_POST['status'] ?? $lead['status']);
    $new_followup = !empty($_POST['follow_up_date']) ? sanitize($_POST['follow_up_date']) : null;
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

    db_update('leads', [
        'status' => $new_status,
        'follow_up_date' => $new_followup,
        'assigned_to' => $assigned_to
    ], 'id = ?', [$lead_id]);

    set_flash_message('success', 'Lead status and follow-up date updated.');
    header("Location: " . BASE_URL . "/admin/inquiry_view.php?id=" . $lead_id);
    exit;
}

// Handle Adding a Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_note'])) {
    $note_text = trim($_POST['note_text'] ?? '');
    if (!empty($note_text)) {
        db_insert('lead_notes', [
            'lead_id'   => $lead_id,
            'user_id'   => $user['id'],
            'note_text' => $note_text
        ]);
        set_flash_message('success', 'Follow-up note logged.');
    }
    header("Location: " . BASE_URL . "/admin/inquiry_view.php?id=" . $lead_id);
    exit;
}

// Handle 1-Click Convert to Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_convert_customer'])) {
    // Check if customer already exists for this lead
    $existing = db_fetch_one("SELECT id FROM customers WHERE lead_id = ? LIMIT 1", [$lead_id]);
    if ($existing) {
        $customerId = $existing['id'];
    } else {
        $customerId = db_insert('customers', [
            'type'             => $lead['customer_type'],
            'name'             => $lead['contact_person'],
            'institution_name' => $lead['institution_name'],
            'contact_person'   => $lead['contact_person'],
            'email'            => $lead['email'],
            'phone'            => $lead['phone'],
            'whatsapp_number'  => $lead['whatsapp_number'] ?: $lead['phone'],
            'physical_address' => $lead['delivery_location'],
            'lead_id'          => $lead_id,
            'notes'            => 'Converted from Lead #' . $lead_id . ' (' . $lead['source'] . ')'
        ]);
    }

    // Update lead status to 'converted'
    db_update('leads', ['status' => 'converted'], 'id = ?', [$lead_id]);

    set_flash_message('success', 'Lead successfully converted to customer! Now drafting order.');
    header("Location: " . BASE_URL . "/admin/order_create.php?customer_id=" . $customerId . "&lead_id=" . $lead_id);
    exit;
}

// Fetch Lead Notes
$notes = db_fetch_all("
    SELECT n.*, u.full_name 
    FROM lead_notes n 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.lead_id = ? 
    ORDER BY n.created_at DESC
", [$lead_id]);

// Fetch Staff Users for Assignment
$staff_list = db_fetch_all("SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <a href="<?= BASE_URL ?>/admin/inquiries.php" class="text-decoration-none text-muted small mb-1 d-inline-block">
      <i class="bi bi-arrow-left me-1"></i> Back to All Leads
    </a>
    <h4 class="fw-bold mb-0">Inquiry #<?= $lead['id'] ?> &mdash; <?= e($lead['institution_name'] ?: $lead['contact_person']) ?></h4>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <a href="<?= e(whatsapp_url("Hello {$lead['contact_person']}! This is StitchCraft following up on your inquiry [Ref #{$lead['id']}].")) ?>" 
       target="_blank" class="btn btn-success btn-sm">
      <i class="bi bi-whatsapp me-1"></i> WhatsApp Lead
    </a>
    
    <!-- 1-Click Convert Button -->
    <form method="POST" action="" id="convertLeadForm">
      <input type="hidden" name="action_convert_customer" value="1">
      <button type="button" 
              class="btn btn-primary btn-sm btn-confirm-action"
              data-title="Convert Lead into Customer & Order"
              data-message="Convert this prospective inquiry into an active Customer profile and draft their new Order record?"
              data-confirm-text="Yes, Convert Lead"
              data-confirm-class="btn-primary"
              data-form-id="convertLeadForm">
        <i class="bi bi-arrow-repeat me-1"></i> Convert to Customer & Order
      </button>
    </form>
  </div>
</div>

<div class="row g-4">
  
  <!-- Left Column: Lead Specifications -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Lead Specifications & Scope</h6>
      
      <div class="row g-3 mb-4">
        <div class="col-sm-6">
          <div class="small text-muted">Customer Type</div>
          <div class="fw-bold text-capitalize"><?= e($lead['customer_type']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="small text-muted">Garment / Package</div>
          <div class="fw-bold"><?= e($lead['garment_type']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="small text-muted">Estimated Volume</div>
          <div class="fw-bold fs-5 text-primary"><?= (int)$lead['estimated_quantity'] ?> units</div>
        </div>
        <div class="col-sm-6">
          <div class="small text-muted">Target Deadline</div>
          <div class="fw-bold"><?= format_date($lead['target_deadline']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="small text-muted">Budget Range</div>
          <div><?= e($lead['budget_range'] ?: 'Not specified') ?></div>
        </div>
        <div class="col-sm-6">
          <div class="small text-muted">Delivery / Campus Location</div>
          <div><?= e($lead['delivery_location'] ?: 'Not specified') ?></div>
        </div>
      </div>

      <?php if (!empty($lead['notes'])): ?>
        <h6 class="fw-bold text-dark mb-2">Inquiry Specifications / Sizing Notes:</h6>
        <div class="p-3 bg-light rounded-3 small text-secondary mb-4 border">
          <?= nl2br(e($lead['notes'])) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($lead['sample_file'])): ?>
        <h6 class="fw-bold text-dark mb-2">Attached Sample Crest / Garment Photo:</h6>
        <div class="p-2 border rounded-3 d-inline-block bg-light">
          <a href="<?= BASE_URL ?>/<?= e($lead['sample_file']) ?>" target="_blank">
            <img src="<?= BASE_URL ?>/<?= e($lead['sample_file']) ?>" alt="Sample upload" style="max-height: 220px; object-fit: contain;" class="rounded">
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Follow-Up Notes History -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
      <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Internal Staff Follow-Up Log</h6>
      
      <!-- New Note Form -->
      <form method="POST" action="" class="mb-4">
        <input type="hidden" name="action_add_note" value="1">
        <div class="input-group">
          <textarea name="note_text" class="form-control form-control-sm" rows="2" placeholder="Record phone call summary, fabric swatch feedback, or next steps..." required></textarea>
          <button type="submit" class="btn btn-primary btn-sm px-3">Log Note</button>
        </div>
      </form>

      <!-- Notes Feed -->
      <?php if (!empty($notes)): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($notes as $note): ?>
            <div class="list-group-item px-0 py-3 border-bottom">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold small text-dark"><?= e($note['full_name'] ?? 'Staff') ?></span>
                <span class="small text-muted"><?= format_datetime($note['created_at']) ?></span>
              </div>
              <p class="small text-secondary mb-0"><?= nl2br(e($note['note_text'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted small mb-0">No follow-up notes logged yet. Enter the first note above.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Status Pipeline & Contact Details -->
  <div class="col-lg-4">
    
    <!-- Status & Pipeline Updater -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
      <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Pipeline Management</h6>
      <form method="POST" action="">
        <input type="hidden" name="action_update_lead" value="1">
        
        <div class="mb-3">
          <label class="form-label small fw-bold">Lead Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="new" <?= $lead['status'] == 'new' ? 'selected' : '' ?>>New (Uncontacted)</option>
            <option value="contacted" <?= $lead['status'] == 'contacted' ? 'selected' : '' ?>>Contacted (In Discussion)</option>
            <option value="quoted" <?= $lead['status'] == 'quoted' ? 'selected' : '' ?>>Quoted (Proforma Sent)</option>
            <option value="converted" <?= $lead['status'] == 'converted' ? 'selected' : '' ?>>Converted (Customer Order Created)</option>
            <option value="lost" <?= $lead['status'] == 'lost' ? 'selected' : '' ?>>Lost (Tender Cancelled)</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Next Follow-Up Date</label>
          <input type="date" name="follow_up_date" class="form-control form-control-sm" value="<?= e($lead['follow_up_date']) ?>">
          <div class="form-text small">Triggers morning reminders on the dashboard and cron alert.</div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Assigned Cutter / Tailor</label>
          <select name="assigned_to" class="form-select form-select-sm">
            <option value="">-- Unassigned --</option>
            <?php foreach ($staff_list as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $lead['assigned_to'] == $s['id'] ? 'selected' : '' ?>>
                <?= e($s['full_name']) ?> (<?= ucfirst($s['role']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
          Update Pipeline Status
        </button>
      </form>
    </div>

    <!-- Contact Profile -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
      <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Client Contact Info</h6>
      
      <div class="mb-3">
        <label class="small text-muted d-block">Contact Person</label>
        <span class="fw-bold"><?= e($lead['contact_person']) ?></span>
        <?php if (!empty($lead['role_title'])): ?>
          <small class="text-muted d-block"><?= e($lead['role_title']) ?></small>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="small text-muted d-block">Phone Number</label>
        <a href="tel:<?= e($lead['phone']) ?>" class="fw-bold text-decoration-none">
          <i class="bi bi-telephone me-1"></i> <?= e($lead['phone']) ?>
        </a>
      </div>

      <div class="mb-3">
        <label class="small text-muted d-block">Email Address</label>
        <a href="mailto:<?= e($lead['email']) ?>" class="text-decoration-none small">
          <i class="bi bi-envelope me-1"></i> <?= e($lead['email']) ?>
        </a>
      </div>

      <div class="mb-3">
        <label class="small text-muted d-block">Marketing Channel Attribution</label>
        <span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $lead['source'])) ?></span>
      </div>

      <div>
        <label class="small text-muted d-block">Logged Timestamp</label>
        <small class="text-secondary"><?= format_datetime($lead['created_at']) ?></small>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
