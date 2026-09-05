<?php
/**
 * Bulk / Institutional Uniform Tender Inquiry Form
 * Tailor Business Management & Marketing System
 */

$page_title = 'School & Corporate Bulk Uniform Tender Inquiry';
$page_desc = 'Submit bulk uniform tender requests for primary & high schools, corporate companies, security firms, and institutions. Volume discounts and on-site sizing.';

require_once __DIR__ . '/includes/header.php';

$success = false;
$error = '';
$lead_ref_id = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $institution_name = sanitize($_POST['institution_name'] ?? '');
        $contact_person   = sanitize($_POST['contact_person'] ?? '');
        $role_title       = sanitize($_POST['role_title'] ?? 'Procurement Officer');
        $email            = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone            = sanitize($_POST['phone'] ?? '');
        $whatsapp_number  = sanitize($_POST['whatsapp_number'] ?? $phone);
        $garment_type     = sanitize($_POST['garment_type'] ?? '');
        $quantity         = max(10, (int)($_POST['quantity'] ?? 50));
        $budget_range     = sanitize($_POST['budget_range'] ?? 'Institutional Tender Rate');
        $target_deadline  = !empty($_POST['target_deadline']) ? sanitize($_POST['target_deadline']) : null;
        $delivery_location= sanitize($_POST['delivery_location'] ?? 'Nairobi');
        $notes            = sanitize($_POST['notes'] ?? '');
        $source           = sanitize($_POST['source'] ?? 'organic_search');

        if (empty($institution_name) || empty($contact_person) || empty($phone) || !$email || empty($garment_type)) {
            $error = 'Please fill out all mandatory fields (Institution Name, Contact Person, Phone, Email, and Uniform Type).';
        } else {
            // Process sample crest or uniform upload
            $sample_file_path = null;
            if (!empty($_FILES['sample_photo']['name'])) {
                $uploadResult = upload_and_resize_image($_FILES['sample_photo'], 'samples', 1200, 80);
                if ($uploadResult['success']) {
                    $sample_file_path = $uploadResult['path'];
                }
            }

            // Sizing support preference added to notes
            $sizing_pref = sanitize($_POST['sizing_support'] ?? 'on_site');
            $full_notes = "Sizing Assistance: " . ($sizing_pref === 'on_site' ? 'Requires Master Tailors on-site for student/staff measurement' : 'Institution has existing size matrix') . "\n" . $notes;

            // Insert into leads table
            $leadId = db_insert('leads', [
                'customer_type'      => 'institution',
                'institution_name'   => $institution_name,
                'contact_person'     => $contact_person,
                'role_title'         => $role_title,
                'email'              => $email,
                'phone'              => $phone,
                'whatsapp_number'    => $whatsapp_number,
                'garment_type'       => $garment_type,
                'estimated_quantity' => $quantity,
                'budget_range'       => $budget_range,
                'target_deadline'    => $target_deadline,
                'delivery_location'  => $delivery_location,
                'notes'              => $full_notes,
                'sample_file'        => $sample_file_path,
                'source'             => $source,
                'status'             => 'new',
                'follow_up_date'     => date('Y-m-d', strtotime('+1 day'))
            ]);

            if ($leadId) {
                $lead_ref_id = 'TNDR-' . str_pad($leadId, 5, '0', STR_PAD_LEFT);
                $success = true;

                // Send Brevo email to institutional representative
                $emailSubject = "Official Uniform Tender Receipt [{$lead_ref_id}] - {$institution_name}";
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 620px; margin: 0 auto; color: #1e293b;'>
                        <h2 style='color: #0f172a;'>Dear {$contact_person} ({$role_title}),</h2>
                        <p>Thank you for submitting your uniform tender inquiry on behalf of <strong>{$institution_name}</strong>.</p>
                        <div style='background: #f8fafc; padding: 18px; border-radius: 8px; margin: 20px 0; border: 1px solid #e2e8f0;'>
                            <p style='margin: 4px 0;'><strong>Tender Reference:</strong> {$lead_ref_id}</p>
                            <p style='margin: 4px 0;'><strong>Uniform Type:</strong> {$garment_type}</p>
                            <p style='margin: 4px 0;'><strong>Estimated Volume:</strong> {$quantity} units</p>
                            <p style='margin: 4px 0;'><strong>Target Delivery Deadline:</strong> " . ($target_deadline ?: 'Prior to Term Opening') . "</p>
                            <p style='margin: 4px 0;'><strong>Campus Location:</strong> {$delivery_location}</p>
                        </div>
                        <p>Our Institutional Accounts Manager is preparing an official company profile, fabric swatch catalog, and itemized proforma quote for your procurement committee's review.</p>
                        <p>We will contact you via <strong>{$phone}</strong> to confirm your physical swatch delivery or schedule an on-site sample presentation.</p>
                        <p style='margin-top: 30px; font-size: 13px; color: #64748b;'>
                            <strong>" . BUSINESS_NAME . " — Institutional Uniform Division</strong><br>
                            " . BUSINESS_ADDRESS . "<br>
                            Direct Procurement Desk: " . BUSINESS_PHONE . "
                        </p>
                    </div>
                ";
                send_brevo_email($email, $contact_person, $emailSubject, $emailBody);

                // Alert Admin via Brevo
                $adminSubject = "🏛️ NEW BULK UNIFORM TENDER: {$institution_name} ({$quantity} pcs)";
                $adminBody = "
                    <p>A new institutional bulk uniform request has been received:</p>
                    <p><strong>Institution:</strong> {$institution_name}<br>
                    <strong>Contact:</strong> {$contact_person} ({$role_title})<br>
                    <strong>Phone:</strong> {$phone} | <strong>Email:</strong> {$email}<br>
                    <strong>Uniforms:</strong> {$garment_type} (Quantity: {$quantity})<br>
                    <strong>Target Opening Date:</strong> {$target_deadline}</p>
                    <p><a href='" . BASE_URL . "/admin/inquiry_view.php?id={$leadId}'>Open Tender in Admin CRM</a></p>
                ";
                send_brevo_email(BUSINESS_EMAIL, 'Procurement Admin', $adminSubject, $adminBody);
            } else {
                $error = 'Failed to submit tender inquiry. Please contact our procurement desk directly.';
            }
        }
    }
}

$initial_source = sanitize($_GET['source'] ?? 'organic_search');
$preselected_sector = sanitize($_GET['sector'] ?? '');
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #091322 0%, #1e3a8a 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Institutional Procurement</span>
    <h1 class="display-5 fw-bold">Bulk Uniform Tender & Quotation Form</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      For School Boards, Bursars, Procurement Officers, and Corporate Operations Directors. Guaranteed anti-fade fabrics and staggered batch delivery.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">

        <?php if ($success): ?>
          <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white">
            <div class="display-3 text-success mb-3"><i class="bi bi-building-check"></i></div>
            <h2 class="fw-bold mb-2">Tender Request Logged!</h2>
            <p class="text-muted fs-5 mb-4">
              Tender Reference Number: <strong class="text-primary"><?= e($lead_ref_id) ?></strong>
            </p>
            <div class="p-4 bg-light rounded-3 text-start max-w-600 mx-auto mb-4 border">
              <h6 class="fw-bold text-dark mb-2">What Happens Next:</h6>
              <ul class="small text-secondary mb-0 ps-3">
                <li class="mb-1">Our Institutional Director will contact you within 2 business hours.</li>
                <li class="mb-1">We prepare physical fabric swatches and embroidered crest prototypes.</li>
                <li class="mb-1">We provide our standardized student/staff size breakdown matrix.</li>
                <li>Official proforma quotation dispatched to <strong><?= e($_POST['email'] ?? '') ?></strong>.</li>
              </ul>
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3">
              <a href="<?= e(whatsapp_url("Hello StitchCraft Procurement! We just submitted bulk tender [{$lead_ref_id}] for {$institution_name}. Please send your catalog.")) ?>" 
                 target="_blank" class="btn btn-success btn-lg">
                <i class="bi bi-whatsapp me-2"></i> Fast-Track on WhatsApp Desk
              </a>
              <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary btn-lg">
                Return to Home
              </a>
            </div>
          </div>
        <?php else: ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <form action="<?= BASE_URL ?>/bulk_inquiry.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
              <?= csrf_input() ?>
              <input type="hidden" name="source" value="<?= e($initial_source) ?>">

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">1. Institution & Representative</h5>
              <?php 
                $cust = is_customer_logged_in() ? get_logged_in_customer() : null;
                $default_inst = $_POST['institution_name'] ?? ($cust['institution_name'] ?: ($cust['name'] ?? ''));
                $default_person = $_POST['contact_person'] ?? ($cust['name'] ?? '');
                $default_email = $_POST['email'] ?? ($cust['email'] ?? '');
                $default_phone = $_POST['phone'] ?? ($cust['phone'] ?? '');
                $default_wa = $_POST['whatsapp_number'] ?? ($cust['phone'] ?? '');
              ?>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Institution / Company Name <span class="text-danger">*</span></label>
                  <input type="text" name="institution_name" class="form-control" placeholder="e.g. St. Jude Academy / KenPower Logistics" required value="<?= e($default_inst) ?>">
                  <div class="invalid-feedback">Institution name is required.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Contact Person Name <span class="text-danger">*</span></label>
                  <input type="text" name="contact_person" class="form-control" placeholder="e.g. Sister Rose Wanjiru / Mr. David Kiptoo" required value="<?= e($default_person) ?>">
                  <div class="invalid-feedback">Representative name is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Official Designation / Role</label>
                  <input type="text" name="role_title" class="form-control" placeholder="e.g. Principal / Head of Procurement / HR Director" value="<?= e($_POST['role_title'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Official Email Address <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" placeholder="procurement@academy.sc.ke" required value="<?= e($default_email) ?>">
                  <div class="invalid-feedback">Valid email required for proforma quote.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                  <input type="tel" name="phone" class="form-control" placeholder="0722 000 000" required value="<?= e($default_phone) ?>">
                  <div class="invalid-feedback">Phone number required.</div>
                </div>
              </div>

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">2. Uniform Scope & Estimated Volume</h5>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Uniform Package Category <span class="text-danger">*</span></label>
                  <select name="garment_type" class="form-select" required>
                    <option value="">-- Select Uniform Category --</option>
                    <option value="Primary & Junior Secondary School Full Sets" <?= $preselected_sector == 'school' ? 'selected' : '' ?>>Primary & Junior Secondary School Full Uniform Sets</option>
                    <option value="Senior High School Blazers & Uniforms">Senior High School Blazers & Uniforms</option>
                    <option value="Corporate Staff Blazers & Office Wear" <?= $preselected_sector == 'corporate' ? 'selected' : '' ?>>Corporate Staff Blazers & Branded Office Wear</option>
                    <option value="Security Guard Heavy Drill Uniforms" <?= $preselected_sector == 'workwear' ? 'selected' : '' ?>>Security Guard Heavy Drill Uniforms</option>
                    <option value="Hospital Scrubs & Medical Laboratory Coats">Hospital Scrubs & Medical Laboratory Coats</option>
                    <option value="Chef Uniforms & Hospitality Apron Sets">Chef Uniforms & Hospitality Apron Sets</option>
                    <option value="School Sports Tracksuits & Games Kits">School Sports Tracksuits & Games Kits</option>
                  </select>
                  <div class="invalid-feedback">Please select a uniform category.</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold">Estimated Quantity (Units) <span class="text-danger">*</span></label>
                  <input type="number" name="quantity" class="form-control" value="100" min="10" step="10" required>
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold">Target Opening / Deadline</label>
                  <input type="date" name="target_deadline" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Campus / Delivery Town</label>
                  <input type="text" name="delivery_location" class="form-control" placeholder="e.g. Westlands Nairobi, Nakuru, Eldoret, Mombasa" value="<?= e($_POST['delivery_location'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Sizing Support Required?</label>
                  <select name="sizing_support" class="form-select">
                    <option value="on_site">Yes — Send master tailors to campus to measure students/staff</option>
                    <option value="matrix_form">No — Institution will compile sizing numbers using your size guide</option>
                  </select>
                </div>
              </div>

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">3. Prototype, Crest & Tender Notes</h5>
              <div class="mb-3">
                <label class="form-label small fw-bold">Tender Specifications or Special Requirements</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Mention specific colors, fabric blends (e.g. 65/35 poly-viscose), crest embroidery placement, or staggered delivery requirements..."><?= e($_POST['notes'] ?? '') ?></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label small fw-bold">Upload School Crest, Company Logo, or Sample Uniform Photo (Optional)</label>
                <input type="file" name="sample_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text small">Accepted formats: JPG, PNG, WebP (Max 4MB).</div>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold">
                  <i class="bi bi-file-earmark-arrow-up-fill me-2"></i> Submit Institutional Tender Specifications
                </button>
              </div>

            </form>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
