<?php
/**
 * Individual Custom Clothing Quote Request Form
 * Tailor Business Management & Marketing System
 */

$page_title = 'Request a Custom Tailoring Quote';
$page_desc = 'Get an itemized quote for bespoke suits, wedding tuxedos, executive blazers, or custom garments handcrafted to your measurements.';

require_once __DIR__ . '/includes/header.php';

$success = false;
$error = '';
$lead_ref_id = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $contact_person   = sanitize($_POST['contact_person'] ?? '');
        $email            = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone            = sanitize($_POST['phone'] ?? '');
        $whatsapp_number  = sanitize($_POST['whatsapp_number'] ?? $phone);
        $garment_type     = sanitize($_POST['garment_type'] ?? '');
        $quantity         = max(1, (int)($_POST['quantity'] ?? 1));
        $budget_range     = sanitize($_POST['budget_range'] ?? '');
        $target_deadline  = !empty($_POST['target_deadline']) ? sanitize($_POST['target_deadline']) : null;
        $delivery_location= sanitize($_POST['delivery_location'] ?? 'Nairobi');
        $notes            = sanitize($_POST['notes'] ?? '');
        $source           = sanitize($_POST['source'] ?? 'organic_search');

        // Normalize source
        $valid_sources = ['organic_search', 'social_facebook', 'social_instagram', 'social_tiktok', 'whatsapp', 'referral', 'walk_in', 'other'];
        if (!in_array($source, $valid_sources)) {
            $source = 'organic_search';
        }

        if (empty($contact_person) || empty($phone) || !$email || empty($garment_type)) {
            $error = 'Please complete all required fields (Name, Phone, Email, and Garment Type).';
        } else {
            // Process sample photo upload if provided
            $sample_file_path = null;
            if (!empty($_FILES['sample_photo']['name'])) {
                $uploadResult = upload_and_resize_image($_FILES['sample_photo'], 'samples', 1200, 80);
                if ($uploadResult['success']) {
                    $sample_file_path = $uploadResult['path'];
                }
            }

            // Insert into leads table
            $leadId = db_insert('leads', [
                'customer_type'      => 'individual',
                'institution_name'   => null,
                'contact_person'     => $contact_person,
                'role_title'         => 'Individual Client',
                'email'              => $email,
                'phone'              => $phone,
                'whatsapp_number'    => $whatsapp_number,
                'garment_type'       => $garment_type,
                'estimated_quantity' => $quantity,
                'budget_range'       => $budget_range,
                'target_deadline'    => $target_deadline,
                'delivery_location'  => $delivery_location,
                'notes'              => $notes,
                'sample_file'        => $sample_file_path,
                'source'             => $source,
                'status'             => 'new',
                'follow_up_date'     => date('Y-m-d', strtotime('+1 day'))
            ]);

            if ($leadId) {
                $lead_ref_id = 'LEAD-' . str_pad($leadId, 5, '0', STR_PAD_LEFT);
                $success = true;

                // Send Brevo confirmation email to client
                $emailSubject = "We have received your custom quote request [{$lead_ref_id}] - " . BUSINESS_NAME;
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;'>
                        <h2 style='color: #0f172a;'>Hello {$contact_person},</h2>
                        <p>Thank you for reaching out to <strong>" . BUSINESS_NAME . "</strong>. We have logged your request for <strong>{$garment_type}</strong> (Reference: <strong>{$lead_ref_id}</strong>).</p>
                        <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #e2e8f0;'>
                            <p style='margin: 4px 0;'><strong>Garment:</strong> {$garment_type}</p>
                            <p style='margin: 4px 0;'><strong>Quantity:</strong> {$quantity}</p>
                            <p style='margin: 4px 0;'><strong>Estimated Budget:</strong> {$budget_range}</p>
                            <p style='margin: 4px 0;'><strong>Target Deadline:</strong> " . ($target_deadline ?: 'Flexible') . "</p>
                        </div>
                        <p>Our Master Cutter is reviewing your specifications and will get in touch shortly via WhatsApp / Phone (<strong>{$phone}</strong>) with fabric recommendations and fitting appointment options.</p>
                        <p style='margin-top: 30px; font-size: 13px; color: #64748b;'>
                            " . BUSINESS_NAME . "<br>
                            " . BUSINESS_ADDRESS . "<br>
                            Phone / WhatsApp: " . BUSINESS_PHONE . "
                        </p>
                    </div>
                ";
                send_brevo_email($email, $contact_person, $emailSubject, $emailBody);

                // Send Brevo notification to Admin
                $adminSubject = "🚨 New Individual Quote Request: {$contact_person} ({$garment_type})";
                $adminBody = "
                    <p>A new custom quote inquiry has been submitted online:</p>
                    <p><strong>Name:</strong> {$contact_person}<br>
                    <strong>Phone:</strong> {$phone}<br>
                    <strong>Email:</strong> {$email}<br>
                    <strong>Garment:</strong> {$garment_type}<br>
                    <strong>Source:</strong> {$source}</p>
                    <p><a href='" . BASE_URL . "/admin/inquiry_view.php?id={$leadId}'>View Lead in Admin Dashboard</a></p>
                ";
                send_brevo_email(BUSINESS_EMAIL, 'Admin Team', $adminSubject, $adminBody);
            } else {
                $error = 'Failed to submit inquiry. Please try again or chat with us directly on WhatsApp.';
            }
        }
    }
}

// Pre-fill source from URL query parameter if present
$initial_source = sanitize($_GET['source'] ?? 'organic_search');
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-gold text-white px-3 py-2 rounded-pill fw-semibold mb-2" style="background:#d97706;">Bespoke Inquiries</span>
    <h1 class="display-5 fw-bold">Request an Individual Custom Quote</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      Tell us about the garment you have in mind. We will calculate fabric yardage, construction costs, and schedule your fitting.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <?php if ($success): ?>
          <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white">
            <div class="display-3 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
            <h2 class="fw-bold mb-2">Quote Request Received!</h2>
            <p class="text-muted fs-5 mb-4">
              Your inquiry has been assigned Reference Number <strong class="text-primary"><?= e($lead_ref_id) ?></strong>.
            </p>
            <p class="text-secondary max-w-600 mx-auto mb-4">
              A confirmation email has been dispatched via Brevo. Our master cutter will contact you within 2 business hours to confirm fabric selection and schedule your first fitting session.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
              <a href="<?= e(whatsapp_url("Hello StitchCraft! I just submitted custom quote request [{$lead_ref_id}]. Can we discuss fabric options?")) ?>" 
                 target="_blank" class="btn btn-success btn-lg">
                <i class="bi bi-whatsapp me-2"></i> Fast-Track on WhatsApp
              </a>
              <a href="<?= BASE_URL ?>/portfolio.php" class="btn btn-outline-secondary btn-lg">
                Browse Gallery
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
            <form action="<?= BASE_URL ?>/quote_request.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
              <?= csrf_input() ?>
              <input type="hidden" name="source" value="<?= e($initial_source) ?>">

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">1. Your Contact Information</h5>
              <?php 
                $cust = is_customer_logged_in() ? get_logged_in_customer() : null;
                $default_name = $_POST['contact_person'] ?? ($cust['name'] ?? '');
                $default_email = $_POST['email'] ?? ($cust['email'] ?? '');
                $default_phone = $_POST['phone'] ?? ($cust['phone'] ?? '');
                $default_wa = $_POST['whatsapp_number'] ?? ($cust['phone'] ?? '');
              ?>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                  <input type="text" name="contact_person" class="form-control" placeholder="e.g. Dr. Evans Mutua" required value="<?= e($default_name) ?>">
                  <div class="invalid-feedback">Please enter your name.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" placeholder="name@domain.com" required value="<?= e($default_email) ?>">
                  <div class="invalid-feedback">Please enter a valid email for the quote copy.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Primary Phone <span class="text-danger">*</span></label>
                  <input type="tel" name="phone" class="form-control" placeholder="0712 345 678" required value="<?= e($default_phone) ?>">
                  <div class="invalid-feedback">Please provide your phone number.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">WhatsApp Number (If different)</label>
                  <input type="tel" name="whatsapp_number" class="form-control" placeholder="0712 345 678" value="<?= e($default_wa) ?>">
                </div>
              </div>

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">2. Garment Details & Preferences</h5>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Garment Category <span class="text-danger">*</span></label>
                  <select name="garment_type" class="form-select" required>
                    <option value="">-- Choose Garment Type --</option>
                    <option value="Bespoke 3-Piece Suit (Jacket, Vest, Trouser)" <?= ($_POST['garment_type'] ?? '') == 'Bespoke 3-Piece Suit (Jacket, Vest, Trouser)' ? 'selected' : '' ?>>Bespoke 3-Piece Suit (Jacket, Vest, Trouser)</option>
                    <option value="Bespoke 2-Piece Suit" <?= ($_POST['garment_type'] ?? '') == 'Bespoke 2-Piece Suit' ? 'selected' : '' ?>>Bespoke 2-Piece Suit (Jacket & Trouser)</option>
                    <option value="Groom / Wedding Tuxedo" <?= ($_POST['garment_type'] ?? '') == 'Groom / Wedding Tuxedo' ? 'selected' : '' ?>>Groom / Wedding Tuxedo</option>
                    <option value="Executive Blazer / Sport Coat" <?= ($_POST['garment_type'] ?? '') == 'Executive Blazer / Sport Coat' ? 'selected' : '' ?>>Executive Blazer / Sport Coat</option>
                    <option value="Custom Formal Trousers (Pair)" <?= ($_POST['garment_type'] ?? '') == 'Custom Formal Trousers (Pair)' ? 'selected' : '' ?>>Custom Formal Trousers</option>
                    <option value="Contemporary African Attire / Agbada" <?= ($_POST['garment_type'] ?? '') == 'Contemporary African Attire / Agbada' ? 'selected' : '' ?>>Contemporary African Attire / Agbada</option>
                    <option value="Custom Evening Gown / Ceremonial Dress" <?= ($_POST['garment_type'] ?? '') == 'Custom Evening Gown / Ceremonial Dress' ? 'selected' : '' ?>>Custom Evening Gown / Ceremonial Dress</option>
                    <option value="Alteration / Garment Restyling" <?= ($_POST['garment_type'] ?? '') == 'Alteration / Garment Restyling' ? 'selected' : '' ?>>Alteration / Garment Restyling</option>
                  </select>
                  <div class="invalid-feedback">Please select a garment type.</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Quantity</label>
                  <input type="number" name="quantity" class="form-control" value="1" min="1" max="50">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Budget Estimate</label>
                  <select name="budget_range" class="form-select">
                    <option value="KSh 15,000 - 25,000">KSh 15,000 - 25,000</option>
                    <option value="KSh 25,000 - 45,000" selected>KSh 25,000 - 45,000</option>
                    <option value="KSh 45,000 - 80,000">KSh 45,000 - 80,000</option>
                    <option value="Above KSh 80,000 (Luxury Super 150s)">Above KSh 80,000</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Target Milestone / Event Date</label>
                  <input type="date" name="target_deadline" class="form-control" min="<?= date('Y-m-d') ?>">
                  <div class="form-text small">Standard turnaround is 10 to 14 days. Express rush available.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Preferred Delivery / Fitting Location</label>
                  <input type="text" name="delivery_location" class="form-control" placeholder="e.g. Workshop Showroom, Moi Avenue or Kilimani home" value="<?= e($_POST['delivery_location'] ?? '') ?>">
                </div>
              </div>

              <h5 class="fw-bold mb-3 text-primary border-bottom pb-2">3. Style Notes & Photo Sample</h5>
              <div class="mb-3">
                <label class="form-label small fw-bold">Specific Requirements or Known Measurements</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Tell us about color preference (e.g. Midnight navy, emerald green), lapel style (peak, notch, shawl), or any specific fit details..."><?= e($_POST['notes'] ?? '') ?></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label small fw-bold">Upload Style Photo or Reference Design (Optional)</label>
                <input type="file" name="sample_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text small">Accepted formats: JPG, PNG, WebP (Max 4MB). We will automatically optimize it.</div>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-gold btn-lg py-3 fw-bold">
                  <i class="bi bi-send-fill me-2"></i> Submit Custom Quote Request
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
