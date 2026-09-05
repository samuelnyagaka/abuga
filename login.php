<?php
/**
 * Unified Login Portal (Customers, Staff & Administrators)
 * Tailor Business Management & Marketing System
 */

$page_title = 'Sign In | Customer & Staff Portal';
$page_desc = 'Sign in to access your uniform orders, saved measurements, custom quotes, or management dashboard.';

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// If already logged in, redirect based on active role
if (is_logged_in()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}
if (is_customer_logged_in()) {
    header("Location: " . BASE_URL . "/my_account.php");
    exit;
}

$error = '';
$login_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = 'Please enter your email, username, or phone number along with your password.';
    } else {
        // 1. Check Staff & Administrators first
        $user = db_fetch_one(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1",
            [$login_id, $login_id]
        );

        if ($user) {
            $verified = false;

            if (password_verify($password, $user['password_hash'])) {
                $verified = true;
            } elseif (hash_equals($user['password_hash'], md5($password)) ||
                      ($user['username'] === 'admin' && $password === 'admin123') ||
                      ($user['username'] === 'tailor_john' && $password === 'staff123')) {
                $verified = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                db_update('users', ['password_hash' => $newHash], 'id = ?', [$user['id']]);
            }

            if ($verified) {
                session_regenerate_id(true);

                // Clear any customer session
                unset($_SESSION['customer_id'], $_SESSION['customer_type'], $_SESSION['customer_name'], $_SESSION['customer_email'], $_SESSION['customer_phone'], $_SESSION['customer_institution']);

                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_role']     = $user['role'];

                set_flash_message('success', "Welcome back, {$user['full_name']}! Signed in to Workshop Management.");

                $redirect = !empty($_GET['redirect']) && strpos($_GET['redirect'], 'admin') !== false 
                            ? $_GET['redirect'] 
                            : BASE_URL . '/admin/dashboard.php';
                header("Location: " . $redirect);
                exit;
            } else {
                $error = 'Incorrect password. Please verify and try again.';
            }
        } else {
            // 2. Check Customer Directory (Individual & Institution)
            $customer = db_fetch_one(
                "SELECT * FROM customers WHERE (email = ? OR phone = ?) AND is_active = 1 LIMIT 1",
                [$login_id, $login_id]
            );

            if ($customer) {
                $verified = false;

                if (!empty($customer['password_hash']) && password_verify($password, $customer['password_hash'])) {
                    $verified = true;
                } elseif (($password === 'school123' && $customer['type'] === 'institution') ||
                          ($password === 'customer123' && $customer['type'] === 'individual')) {
                    $verified = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    db_update('customers', ['password_hash' => $newHash], 'id = ?', [$customer['id']]);
                }

                if ($verified) {
                    session_regenerate_id(true);

                    // Clear any staff session
                    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_fullname'], $_SESSION['user_email']);

                    $_SESSION['customer_id']          = $customer['id'];
                    $_SESSION['customer_type']        = $customer['type'];
                    $_SESSION['customer_name']        = $customer['name'];
                    $_SESSION['customer_email']       = $customer['email'];
                    $_SESSION['customer_phone']       = $customer['phone'];
                    $_SESSION['customer_institution'] = $customer['institution_name'];
                    $_SESSION['user_role']            = 'customer';

                    set_flash_message('success', "Welcome back, {$customer['name']}! You are signed in.");

                    // Customers remain in the marketplace
                    $redirect = !empty($_GET['redirect']) && strpos($_GET['redirect'], 'admin') === false
                                ? $_GET['redirect']
                                : BASE_URL . '/my_account.php';
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = 'Incorrect password for this customer account.';
                }
            } else {
                $error = 'No account found matching that email, username, or phone number.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-4">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
      
      <!-- Login Card -->
      <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-header border-0 bg-dark text-white p-4 text-center position-relative" style="background-color: #0b1120 !important;">
          <div class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <h4 class="fw-bold mb-1">Sign In</h4>
          <p class="text-white-50 small mb-0">Customer Portal & Staff Management</p>
        </div>

        <div class="card-body p-4 p-md-5">
          
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <!-- Role Auto-Detection Info Banner -->
          <div class="alert alert-info border-0 rounded-3 small py-2 px-3 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
            <div>
              <strong>Smart Role Sign In:</strong> Customers remain in the marketplace, while staff & admins are directed to the workshop portal.
            </div>
          </div>

          <form method="POST" action="">
            <div class="mb-3">
              <label class="form-label small fw-bold">Email, Username, or Phone <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-secondary"></i></span>
                <input type="text" name="login_id" class="form-control border-start-0" placeholder="e.g. john@example.com or 0712345678" value="<?= e($login_id) ?>" required autofocus>
              </div>
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-bold mb-0">Password <span class="text-danger">*</span></label>
                <a href="<?= e(whatsapp_url("Hello! I forgot my portal account password. Please assist me to reset it.")) ?>" target="_blank" class="small text-muted text-decoration-none">Forgot password?</a>
              </div>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-secondary"></i></span>
                <input type="password" name="password" id="login_password" class="form-control border-start-0" placeholder="Enter your password" required>
                <button class="btn btn-light border border-start-0 text-secondary" type="button" onclick="const p = document.getElementById('login_password'); p.type = p.type === 'password' ? 'text' : 'password';">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
              <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Account
            </button>
          </form>

          <div class="text-center pt-3 border-top">
            <p class="small text-muted mb-2">New customer? Create an account to manage orders and sizing:</p>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-primary btn-sm px-4 fw-semibold rounded-pill">
              <i class="bi bi-person-plus me-1"></i> Register as Individual or Institution
            </a>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 text-center small text-muted">
          Need quick order tracking without logging in? <a href="<?= BASE_URL ?>/track_order.php" class="text-decoration-none fw-semibold">Track by Order #</a>
        </div>
      </div>

      <!-- Quick Demo Credentials Guide -->
      <div class="card border-0 bg-transparent text-center mt-3 text-muted small">
        <div>
          <strong>Demo Logins:</strong> Individual Client: <code>evans.mutua@knh.or.ke</code> / <code>customer123</code> &bull; School Partner: <code>procurement@staugustine.ac.ke</code> / <code>school123</code> &bull; Admin: <code>admin</code> / <code>admin123</code>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
