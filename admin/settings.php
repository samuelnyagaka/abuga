<?php
/**
 * System Settings & Integrations
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'System Settings';
require_once __DIR__ . '/admin_header.php';

// Strict Admin Role Check
require_admin_role();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_settings'])) {
    $settingsToUpdate = [
        'business_name'       => sanitize($_POST['business_name'] ?? ''),
        'business_phone'      => sanitize($_POST['business_phone'] ?? ''),
        'business_whatsapp'   => preg_replace('/[^0-9]/', '', $_POST['business_whatsapp'] ?? ''),
        'business_email'      => sanitize($_POST['business_email'] ?? ''),
        'business_address'    => sanitize($_POST['business_address'] ?? ''),
        'business_hours'      => sanitize($_POST['business_hours'] ?? ''),
        'currency_symbol'     => sanitize($_POST['currency_symbol'] ?? 'KSh '),
        'brevo_api_key'       => trim($_POST['brevo_api_key'] ?? ''),
        'brevo_sender_email'  => sanitize($_POST['brevo_sender_email'] ?? ''),
        'cron_token'          => sanitize($_POST['cron_token'] ?? 'tailor_cron_secure_token_2026')
    ];

    foreach ($settingsToUpdate as $key => $val) {
        $exists = db_fetch_one("SELECT key_name FROM settings WHERE key_name = ? LIMIT 1", [$key]);
        if ($exists) {
            db_update('settings', ['key_value' => $val], 'key_name = ?', [$key]);
        } else {
            db_insert('settings', ['key_name' => $key, 'key_value' => $val]);
        }
    }

    set_flash_message('success', 'System configurations saved successfully.');
    header("Location: " . BASE_URL . "/admin/settings.php");
    exit;
}

// Load Current Settings
$dbSettings = db_fetch_all("SELECT * FROM settings");
$settings = [];
foreach ($dbSettings as $s) {
    $settings[$s['key_name']] = $s['key_value'];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">System Settings & Integrations</h4>
    <p class="text-muted small mb-0">Configure your business contact details, Brevo transactional email, and automated cron triggers.</p>
  </div>
</div>

<form method="POST" action="">
  <input type="hidden" name="action_save_settings" value="1">

  <div class="row g-4">
    
    <!-- Business Profile -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Business Profile & Branding</h6>
        
        <div class="mb-3">
          <label class="form-label small fw-bold">Company Trading Name</label>
          <input type="text" name="business_name" class="form-control" value="<?= e($settings['business_name'] ?? BUSINESS_NAME) ?>" required>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Primary Phone</label>
            <input type="text" name="business_phone" class="form-control" value="<?= e($settings['business_phone'] ?? BUSINESS_PHONE) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">WhatsApp Direct Number</label>
            <input type="text" name="business_whatsapp" class="form-control" placeholder="254712345678" value="<?= e($settings['business_whatsapp'] ?? BUSINESS_WHATSAPP) ?>" required>
            <div class="form-text small">Country code without + or spaces (for wa.me links).</div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Official Public Email</label>
          <input type="email" name="business_email" class="form-control" value="<?= e($settings['business_email'] ?? BUSINESS_EMAIL) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Showroom & Workshop Address</label>
          <textarea name="business_address" class="form-control" rows="2" required><?= e($settings['business_address'] ?? BUSINESS_ADDRESS) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Operating Hours</label>
          <input type="text" name="business_hours" class="form-control" value="<?= e($settings['business_hours'] ?? BUSINESS_HOURS) ?>" required>
        </div>

        <div>
          <label class="form-label small fw-bold">Currency Symbol</label>
          <input type="text" name="currency_symbol" class="form-control" value="<?= e($settings['currency_symbol'] ?? CURRENCY_SYMBOL) ?>" required>
        </div>

      </div>
    </div>

    <!-- Integrations: Brevo & Cron -->
    <div class="col-lg-6">
      
      <!-- Brevo Email Settings -->
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
          <h6 class="fw-bold text-primary mb-0">2. Brevo (Sendinblue) HTTPS API</h6>
          <span class="badge bg-info-subtle text-info">InfinityFree Compliant</span>
        </div>
        
        <p class="small text-muted mb-3">
          InfinityFree blocks standard SMTP & PHP <code>mail()</code>. All quote receipts and tender notices are dispatched via the Brevo v3 HTTPS REST API.
        </p>

        <div class="mb-3">
          <label class="form-label small fw-bold">Brevo v3 API Key</label>
          <input type="password" name="brevo_api_key" class="form-control" placeholder="xkeysib-..." value="<?= e($settings['brevo_api_key'] ?? '') ?>">
          <div class="form-text small">Get your free API key at <a href="https://app.brevo.com/settings/keys/api" target="_blank">brevo.com</a>. If empty, emails will run in safe simulation mode.</div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Verified Brevo Sender Email</label>
          <input type="email" name="brevo_sender_email" class="form-control" placeholder="notifications@yourdomain.com" value="<?= e($settings['brevo_sender_email'] ?? BREVO_SENDER_EMAIL) ?>">
          <div class="form-text small">Must be a verified sender in your Brevo account.</div>
        </div>
      </div>

      <!-- Automated Cron Pinger -->
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">3. External Cron Scheduler</h6>
        <p class="small text-muted mb-3">
          Because InfinityFree has no native shell cron, point a free monitoring service like <a href="https://cron-job.org" target="_blank">cron-job.org</a> to ping this URL once daily at 08:00 AM:
        </p>

        <div class="mb-3">
          <label class="form-label small fw-bold">Cron Security Secret Token</label>
          <input type="text" name="cron_token" class="form-control" value="<?= e($settings['cron_token'] ?? CRON_SECURITY_TOKEN) ?>" required>
        </div>

        <div class="p-3 bg-light rounded-3 border small">
          <div class="small fw-bold text-dark mb-1">Your Live Cron Endpoint URL:</div>
          <code class="text-break d-block mb-2"><?= BASE_URL ?>/cron_dispatch.php?token=<?= e($settings['cron_token'] ?? CRON_SECURITY_TOKEN) ?></code>
          <a href="<?= BASE_URL ?>/cron_dispatch.php?token=<?= e($settings['cron_token'] ?? CRON_SECURITY_TOKEN) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-play-circle me-1"></i> Test Cron Pinger Now
          </a>
        </div>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg fw-bold py-3">
          <i class="bi bi-save me-2"></i> Save All Settings
        </button>
      </div>

    </div>

  </div>
</form>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
