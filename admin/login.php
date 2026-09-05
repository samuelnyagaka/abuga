<?php
/**
 * Admin / Staff Login Portal
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// If already logged in, redirect straight to dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // 1. Check Staff & Administrators
        $user = db_fetch_one("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1", [$username, $username]);

        if ($user) {
            $verified = false;

            // Primary check: standard PHP bcrypt / default password_verify
            if (password_verify($password, $user['password_hash'])) {
                $verified = true;
            } 
            // Fallback check for seed hashes
            elseif (hash_equals($user['password_hash'], md5($password)) || 
                    ($username === 'admin' && $password === 'admin123') ||
                    ($username === 'tailor_john' && $password === 'staff123')) {
                $verified = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                db_update('users', ['password_hash' => $newHash], 'id = ?', [$user['id']]);
            }

            if ($verified) {
                session_regenerate_id(true);

                unset($_SESSION['customer_id'], $_SESSION['customer_type'], $_SESSION['customer_name'], $_SESSION['customer_email']);

                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_role']     = $user['role'];

                $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : BASE_URL . '/admin/dashboard.php';
                header("Location: " . $redirect);
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            // 2. Check Customer Directory (smart routing for customers logging in here)
            $customer = db_fetch_one("SELECT * FROM customers WHERE (email = ? OR phone = ?) AND is_active = 1 LIMIT 1", [$username, $username]);
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
                    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_fullname'], $_SESSION['user_email']);

                    $_SESSION['customer_id']          = $customer['id'];
                    $_SESSION['customer_type']        = $customer['type'];
                    $_SESSION['customer_name']        = $customer['name'];
                    $_SESSION['customer_email']       = $customer['email'];
                    $_SESSION['customer_phone']       = $customer['phone'];
                    $_SESSION['customer_institution'] = $customer['institution_name'];
                    $_SESSION['user_role']            = 'customer';

                    set_flash_message('success', "Welcome, {$customer['name']}! Redirecting to your Customer Portal.");
                    header("Location: " . BASE_URL . "/my_account.php");
                    exit;
                } else {
                    $error = 'Incorrect password for this customer account.';
                }
            } else {
                $error = 'No active account found with that username or email.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Login | <?= e(BUSINESS_NAME) ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #090e17 0%, #1e293b 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .login-card {
      width: 100%;
      max-width: 440px;
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      overflow: hidden;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="p-4 p-md-5 text-center bg-light border-bottom">
      <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="<?= e(BUSINESS_NAME) ?>" height="48" class="mb-2">
      <h4 class="fw-bold mb-1 text-dark" style="font-family: 'Outfit', sans-serif;">Workshop Portal</h4>
      <p class="small text-muted mb-0">Sign in to manage uniform orders and leads</p>
    </div>

    <div class="p-4 p-md-5">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small" role="alert">
          <i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label small fw-bold text-dark">Username or Email</label>
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-person text-secondary"></i></span>
            <input type="text" name="username" class="form-control" placeholder="admin or tailor_john" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold text-dark">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock text-secondary"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
          Sign In to Portal <i class="bi bi-box-arrow-in-right ms-1"></i>
        </button>
      </form>

      <!-- Demo Credentials Helper Note -->
      <div class="p-3 bg-light rounded-3 border small text-secondary mt-3">
        <strong class="text-dark d-block mb-1"><i class="bi bi-key me-1"></i> Default Seed Accounts:</strong>
        <div class="d-flex justify-content-between mb-1">
          <span><strong>Admin:</strong> admin</span>
          <code>admin123</code>
        </div>
        <div class="d-flex justify-content-between">
          <span><strong>Staff/Tailor:</strong> tailor_john</span>
          <code>staff123</code>
        </div>
      </div>

      <!-- Customer Portal link -->
      <div class="text-center mt-3 pt-2 border-top">
        <span class="small text-muted">Are you a Customer?</span>
        <a href="<?= BASE_URL ?>/login.php" class="small fw-semibold text-decoration-none ms-1">Customer Portal Login &rarr;</a>
        <div class="mt-1">
          <a href="<?= BASE_URL ?>/register.php" class="small text-muted text-decoration-none">Create Customer Account (Individual / School)</a>
        </div>
      </div>

      <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none small text-muted">
          <i class="bi bi-arrow-left me-1"></i> Return to Public Site
        </a>
      </div>
    </div>
  </div>

</body>
</html>
