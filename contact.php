<?php
/**
 * Contact Us Page
 * Tailor Business Management & Marketing System
 */

$page_title = 'Contact Workshop & Showroom';
$page_desc = 'Visit our Nairobi workshop at Biashara Plaza, Moi Avenue or speak with our master tailors for school uniforms and custom garments.';

require_once __DIR__ . '/includes/header.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please try again.';
    } else {
        $name    = sanitize($_POST['name'] ?? '');
        $email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone   = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? 'General Inquiry');
        $message = sanitize($_POST['message'] ?? '');

        if (empty($name) || empty($phone) || !$email || empty($message)) {
            $error = 'Please fill out all required fields.';
        } else {
            // Save as lead
            $leadId = db_insert('leads', [
                'customer_type'      => 'individual',
                'contact_person'     => $name,
                'email'              => $email,
                'phone'              => $phone,
                'garment_type'       => $subject,
                'estimated_quantity' => 1,
                'notes'              => $message,
                'source'             => 'organic_search',
                'status'             => 'new'
            ]);

            // Notify Admin via Brevo
            send_brevo_email(
                BUSINESS_EMAIL,
                'Showroom Admin',
                "New Contact Message from {$name}: {$subject}",
                "<p><strong>From:</strong> {$name} ({$phone}, {$email})</p><p><strong>Subject:</strong> {$subject}</p><p><strong>Message:</strong><br>{$message}</p>"
            );

            // Auto-reply to visitor
            send_brevo_email(
                $email,
                $name,
                "Thank you for contacting " . BUSINESS_NAME,
                "<p>Hello {$name},</p><p>We have received your message regarding <strong>{$subject}</strong>. Our customer care desk will get back to you shortly.</p>"
            );

            $sent = true;
        }
    }
}
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-gold text-white px-3 py-2 rounded-pill fw-semibold mb-2" style="background:#d97706;">Visit or Call</span>
    <h1 class="display-5 fw-bold">Contact Our Tailoring Workshop</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      Visit our central Nairobi showroom for fittings and fabric swatches, or reach out to our institutional procurement desk.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-5">
      
      <!-- Contact Details & Map -->
      <div class="col-lg-5">
        <h3 class="fw-bold mb-4">Workshop Showroom</h3>

        <div class="d-flex gap-3 mb-4">
          <div class="feature-icon-wrapper" style="width: 48px; height: 48px; font-size: 1.2rem;">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Showroom & Fitting Studio</h6>
            <p class="text-muted small mb-0"><?= e(BUSINESS_ADDRESS) ?></p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <div class="feature-icon-wrapper" style="width: 48px; height: 48px; font-size: 1.2rem;">
            <i class="bi bi-telephone-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Direct Phone Lines</h6>
            <p class="text-muted small mb-0">
              <a href="tel:<?= e(BUSINESS_PHONE) ?>" class="text-decoration-none text-dark fw-semibold"><?= e(BUSINESS_PHONE) ?></a>
            </p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <div class="feature-icon-wrapper feature-icon-gold" style="width: 48px; height: 48px; font-size: 1.2rem;">
            <i class="bi bi-whatsapp"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">WhatsApp Fast Desk</h6>
            <p class="text-muted small mb-0">
              <a href="<?= e(whatsapp_url("Hello StitchCraft! I would like to make an inquiry.")) ?>" target="_blank" class="text-success text-decoration-none fw-semibold">
                Chat on WhatsApp (+<?= e(BUSINESS_WHATSAPP) ?>)
              </a>
            </p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <div class="feature-icon-wrapper" style="width: 48px; height: 48px; font-size: 1.2rem;">
            <i class="bi bi-clock-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Operating Hours</h6>
            <p class="text-muted small mb-0"><?= e(BUSINESS_HOURS) ?></p>
          </div>
        </div>

        <!-- Google Map Embed Container -->
        <div class="rounded-4 overflow-hidden border shadow-sm mt-4">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.819921444155!2d36.82194631475396!3d-1.283333299063991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d65b706c9b%3A0x7d28c3132e0e5672!2sMoi%20Ave%2C%20Nairobi!5e0!3m2!1sen!2ske!4v1629890123456!5m2!1sen!2ske" 
            width="100%" height="240" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>

      </div>

      <!-- Quick Message Form -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
          <h3 class="fw-bold mb-2">Send Us a Quick Message</h3>
          <p class="text-muted small mb-4">Have a question regarding custom tailoring, bulk prices, or fabric samples? Drop us a line below.</p>

          <?php if ($sent): ?>
            <div class="alert alert-success p-4 rounded-3 text-center">
              <i class="bi bi-check-circle-fill fs-2 d-block mb-2 text-success"></i>
              <h5 class="fw-bold">Message Received!</h5>
              <p class="small text-muted mb-3">Thank you for reaching out. A confirmation email was sent and our workshop coordinator will get in touch shortly.</p>
              <a href="<?= e(whatsapp_url("Hello! I just submitted a contact form on your site.")) ?>" target="_blank" class="btn btn-success btn-sm">
                <i class="bi bi-whatsapp me-1"></i> Continue on WhatsApp
              </a>
            </div>
          <?php else: ?>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/contact.php" class="needs-validation" novalidate>
              <?= csrf_input() ?>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Your Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Grace Wambui" required value="<?= e($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                  <input type="tel" name="phone" class="form-control" placeholder="0712 345 678" required value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Inquiry Subject</label>
                  <select name="subject" class="form-select">
                    <option value="School Uniform Tender">School Uniform Tender</option>
                    <option value="Corporate Uniform Inquiries">Corporate Uniform Inquiries</option>
                    <option value="Bespoke Suit Fitting Appointment">Bespoke Suit Fitting Appointment</option>
                    <option value="General Tailoring Question">General Tailoring Question</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Message Details <span class="text-danger">*</span></label>
                  <textarea name="message" class="form-control" rows="4" placeholder="How can we assist you today?" required><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <div class="col-12 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    <i class="bi bi-send me-1"></i> Send Message
                  </button>
                </div>
              </div>
            </form>

          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
