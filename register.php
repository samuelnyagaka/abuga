<?php
/**
 * Customer Registration Portal (Individual & Institution Accounts)
 * Tailor Business Management & Marketing System
 */

$page_title = 'Create Customer Account | Individual & Institutional Clients';
$page_desc = 'Register your account for personal bespoke tailoring or institutional school and corporate uniform orders.';

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// If already logged in, redirect
if (is_customer_logged_in()) {
    header("Location: " . BASE_URL . "/my_account.php");
    exit;
}
if (is_logged_in()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$error = '';
$account_type = $_POST['account_type'] ?? ($_GET['type'] ?? 'individual');
if (!in_array($account_type, ['individual', 'institution'])) {
    $account_type = 'individual';
}

$form_data = [
    'name'             => '',
    'institution_name' => '',
    'contact_person'   => '',
    'role_title'       => '',
    'email'            => '',
    'phone'            => '',
    'whatsapp_number'  => '',
    'physical_address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type     = in_array($_POST['account_type'] ?? '', ['individual', 'institution']) ? $_POST['account_type'] : 'individual';
    $email            = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone            = sanitize($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $physical_address = sanitize($_POST['physical_address'] ?? '');

    if ($account_type === 'institution') {
        $institution_name = sanitize($_POST['institution_name'] ?? '');
        $contact_person   = sanitize($_POST['contact_person'] ?? '');
        $role_title       = sanitize($_POST['role_title'] ?? '');
        $whatsapp_number  = sanitize($_POST['whatsapp_number'] ?? $phone);
        $name             = $institution_name;

        $form_data = [
            'institution_name' => $institution_name,
            'contact_person'   => $contact_person,
            'role_title'       => $role_title,
            'email'            => $_POST['email'] ?? '',
            'phone'            => $phone,
            'whatsapp_number'  => $whatsapp_number,
            'physical_address' => $physical_address
        ];

        if (empty($institution_name) || empty($contact_person) || empty($phone) || !$email) {
            $error = 'Please fill in the Institution Name, Contact Person, Phone, and a valid Email.';
        }
    } else {
        $name             = sanitize($_POST['name'] ?? '');
        $whatsapp_number  = sanitize($_POST['whatsapp_number'] ?? $phone);

        $form_data = [
            'name'             => $name,
            'email'            => $_POST['email'] ?? '',
            'phone'            => $phone,
            'whatsapp_number'  => $whatsapp_number,
            'physical_address' => $physical_address
        ];

        if (empty($name) || empty($phone) || !$email) {
            $error = 'Please enter your Full Name, Phone number, and a valid Email address.';
        }
    }

    if (empty($error)) {
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $password_confirm) {
            $error = 'The passwords you entered do not match.';
        } else {
            // Check if customer already exists
            $existing = db_fetch_one(
                "SELECT id, name, password_hash FROM customers WHERE email = ? OR phone = ? LIMIT 1",
                [$email, $phone]
            );

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($existing) {
                if (!empty($existing['password_hash'])) {
                    $error = 'An account with that email address or phone number already exists. Please sign in instead.';
                } else {
                    // Activate online portal for an existing offline client record
                    db_query(
                        "UPDATE customers SET password_hash = ?, type = ?, name = ?, institution_name = ?, contact_person = ?, physical_address = ?, is_active = 1, updated_at = NOW() WHERE id = ?",
                        [
                            $password_hash,
                            $account_type,
                            $name,
                            $account_type === 'institution' ? $institution_name : null,
                            $account_type === 'institution' ? $contact_person : $name,
                            $physical_address,
                            $existing['id']
                        ]
                    );
                    $customerId = $existing['id'];
                }
            } else {
                // Insert fresh customer record
                $customerId = db_insert('customers', [
                    'type'             => $account_type,
                    'name'             => $name,
                    'institution_name' => $account_type === 'institution' ? $institution_name : null,
                    'contact_person'   => $account_type === 'institution' ? $contact_person : $name,
                    'email'            => $email,
                    'password_hash'    => $password_hash,
                    'phone'            => $phone,
                    'whatsapp_number'  => $whatsapp_number,
                    'physical_address' => $physical_address,
                    'is_active'        => 1
                ]);
            }

            if (empty($error) && !empty($customerId)) {
                // Auto-login newly registered customer
                session_regenerate_id(true);

                unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_fullname'], $_SESSION['user_email']);

                $_SESSION['customer_id']          = $customerId;
                $_SESSION['customer_type']        = $account_type;
                $_SESSION['customer_name']        = $name;
                $_SESSION['customer_email']       = $email;
                $_SESSION['customer_phone']       = $phone;
                $_SESSION['customer_institution'] = $account_type === 'institution' ? $institution_name : null;
                $_SESSION['user_role']            = 'customer';

                // Send welcome notification via Brevo
                $subject = "Welcome to " . BUSINESS_NAME . " Client Portal";
                $typeLabel = $account_type === 'institution' ? "Institutional Partner" : "Individual Client";
                $html = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #1e3a8a;'>Welcome to " . BUSINESS_NAME . "!</h2>
                        <p>Hello <strong>{$name}</strong>,</p>
                        <p>Your customer account has been created successfully as an <strong>{$typeLabel}</strong>.</p>
                        <p>You can now sign in at any time to:</p>
                        <ul>
                            <li>Track live manufacturing stages for your school uniforms and bespoke attire</li>
                            <li>View and manage your saved measurement profiles</li>
                            <li>Download official proforma invoices and receipts</li>
                            <li>Submit expedited quote requests and re-orders</li>
                        </ul>
                        <p><a href='" . BASE_URL . "/my_account.php' style='display:inline-block; padding: 10px 20px; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 4px;'>Go to My Customer Portal</a></p>
                        <p style='font-size: 12px; color: #777;'>Need urgent assistance? Reach us on WhatsApp at " . BUSINESS_PHONE . ".</p>
                    </div>
                ";
                @send_brevo_email($email, $name, $subject, $html);

                set_flash_message('success', "Welcome to " . BUSINESS_NAME . ", {$name}! Your customer account is ready.");
                header("Location: " . BASE_URL . "/my_account.php");
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-4">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
      
      <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        
        <!-- Card Header with Account Type Tabs -->
        <div class="card-header border-0 bg-dark text-white p-4 text-center" style="background-color: #0b1120 !important;">
          <h4 class="fw-bold mb-1">Create Customer Account</h4>
          <p class="text-white-50 small mb-3">Access order tracking, measurement profiles, and expedited quotes</p>

          <!-- Type Selector Pills -->
          <div class="btn-group w-100 p-1 bg-white bg-opacity-10 rounded-3" role="group">
            <a href="<?= BASE_URL ?>/register.php?type=individual" class="btn btn-sm <?= $account_type === 'individual' ? 'btn-gold' : 'btn-outline-light' ?> py-2 fw-semibold">
              <i class="bi bi-person me-1"></i> Individual Client
            </a>
            <a href="<?= BASE_URL ?>/register.php?type=institution" class="btn btn-sm <?= $account_type === 'institution' ? 'btn-gold' : 'btn-outline-light' ?> py-2 fw-semibold">
              <i class="bi bi-buildings me-1"></i> School / Corporate / Institution
            </a>
          </div>
        </div>

        <div class="card-body p-4 p-md-5">

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
              <i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <form method="POST" action="">
            <input type="hidden" name="account_type" value="<?= e($account_type) ?>">

            <?php if ($account_type === 'institution'): ?>
              <!-- Institutional Account Fields -->
              <div class="badge bg-primary-subtle text-primary mb-3 px-3 py-2 rounded-pill small">
                <i class="bi bi-mortarboard me-1"></i> Schools, Colleges, Security Firms, Corporate Tenders
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Institution / School / Company Name <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                  <input type="text" name="institution_name" class="form-control" placeholder="e.g. St. Nicholas Senior School or Apex Logistics Ltd" value="<?= e($form_data['institution_name'] ?? '') ?>" required>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-md-7">
                  <label class="form-label small fw-bold">Contact Person Name <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="contact_person" class="form-control" placeholder="e.g. Dr. Jane Omondi" value="<?= e($form_data['contact_person'] ?? '') ?>" required>
                  </div>
                </div>
                <div class="col-md-5">
                  <label class="form-label small fw-bold">Designation / Role</label>
                  <input type="text" name="role_title" class="form-control" placeholder="e.g. Principal / Procurement Head" value="<?= e($form_data['role_title'] ?? '') ?>">
                </div>
              </div>

            <?php else: ?>
              <!-- Individual Account Fields -->
              <div class="badge bg-gold text-dark mb-3 px-3 py-2 rounded-pill small fw-semibold">
                <i class="bi bi-scissors me-1"></i> Personal Tailoring, Bespoke Suits, Wedding Wear & Sizing
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                  <input type="text" name="name" class="form-control" placeholder="e.g. Peter Kamau" value="<?= e($form_data['name'] ?? '') ?>" required>
                </div>
              </div>
            <?php endif; ?>

            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Official Email Address <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                  <input type="email" name="email" class="form-control" placeholder="name@domain.com" value="<?= e($form_data['email'] ?? '') ?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                  <input type="tel" name="phone" class="form-control" placeholder="0722 000 000" value="<?= e($form_data['phone'] ?? '') ?>" required>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold"><?= $account_type === 'institution' ? 'Campus / Office Physical Location' : 'Delivery Address or Town' ?></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                <input type="text" name="physical_address" class="form-control" placeholder="e.g. Westlands, Nairobi / Along Waiyaki Way" value="<?= e($form_data['physical_address'] ?? '') ?>">
              </div>
            </div>

            <hr class="my-4 text-muted">

            <div class="row g-2 mb-4">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Create Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                  <input type="password" name="password" id="reg_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
                  <input type="password" name="password_confirm" class="form-control" placeholder="Re-type password" required minlength="6">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-check-circle-fill me-1"></i> Complete Registration & Access Portal
            </button>
          </form>

          <div class="text-center pt-3 mt-4 border-top">
            <span class="small text-muted">Already registered?</span>
            <a href="<?= BASE_URL ?>/login.php" class="small fw-bold text-decoration-none ms-1">Sign in here &rarr;</a>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
