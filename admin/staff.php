<?php
/**
 * Staff Accounts Management
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Staff Accounts';
require_once __DIR__ . '/admin_header.php';

// Strict Admin Role Check
require_admin_role();

// Handle Add Staff Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_staff'])) {
    $username  = trim(strtolower(sanitize($_POST['username'] ?? '')));
    $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $full_name = sanitize($_POST['full_name'] ?? '');
    $role      = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
    $phone     = sanitize($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($username) || !$email || empty($full_name) || empty($password)) {
        set_flash_message('danger', 'Please complete all required fields.');
    } elseif (strlen($password) < 6) {
        set_flash_message('danger', 'Password must be at least 6 characters long.');
    } else {
        // Check uniqueness
        $exists = db_fetch_one("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
        if ($exists) {
            set_flash_message('danger', 'A user account with that username or email already exists.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $newId = db_insert('users', [
                'username'      => $username,
                'email'         => $email,
                'password_hash' => $hash,
                'full_name'     => $full_name,
                'role'          => $role,
                'phone'         => $phone,
                'is_active'     => 1
            ]);

            if ($newId) {
                set_flash_message('success', "Staff account for {$full_name} created successfully.");
            } else {
                set_flash_message('danger', 'Failed to create user account.');
            }
        }
    }
    header("Location: " . BASE_URL . "/admin/staff.php");
    exit;
}

// Handle Edit Staff Account & Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit_staff'])) {
    $targetId  = (int)($_POST['user_id'] ?? 0);
    $username  = trim(strtolower(sanitize($_POST['username'] ?? '')));
    $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $full_name = sanitize($_POST['full_name'] ?? '');
    $role      = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
    $phone     = sanitize($_POST['phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password  = $_POST['password'] ?? '';

    // Verify target user exists
    $existing = db_fetch_one("SELECT * FROM users WHERE id = ? LIMIT 1", [$targetId]);
    if (!$existing) {
        set_flash_message('danger', 'User account not found.');
        header("Location: " . BASE_URL . "/admin/staff.php");
        exit;
    }

    if (empty($username) || !$email || empty($full_name)) {
        set_flash_message('danger', 'Full name, username, and a valid email address are required.');
        header("Location: " . BASE_URL . "/admin/staff.php");
        exit;
    }

    // Safety: Protect current logged-in admin from demoting or deactivating themselves
    if ($targetId === (int)$user['id']) {
        $role = 'admin';
        $is_active = 1;
    }

    // Check uniqueness among other accounts
    $conflict = db_fetch_one(
        "SELECT id, username, email FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1",
        [$username, $email, $targetId]
    );
    if ($conflict) {
        if ($conflict['username'] === $username) {
            set_flash_message('danger', "The username '{$username}' is already registered to another account.");
        } else {
            set_flash_message('danger', "The email address '{$email}' is already registered to another account.");
        }
        header("Location: " . BASE_URL . "/admin/staff.php");
        exit;
    }

    // Update record with optional password change
    if (!empty($password)) {
        if (strlen($password) < 6) {
            set_flash_message('danger', 'New password must be at least 6 characters long.');
            header("Location: " . BASE_URL . "/admin/staff.php");
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_query(
            "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, phone = ?, is_active = ?, password_hash = ? WHERE id = ?",
            [$full_name, $username, $email, $role, $phone, $is_active, $hash, $targetId]
        );
    } else {
        db_query(
            "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, phone = ?, is_active = ? WHERE id = ?",
            [$full_name, $username, $email, $role, $phone, $is_active, $targetId]
        );
    }

    // If currently logged-in user edited their own profile, sync active session
    if ($targetId === (int)$user['id']) {
        $_SESSION['user_fullname'] = $full_name;
        $_SESSION['user_name']     = $username;
        $_SESSION['user_email']    = $email;
        $_SESSION['user_role']     = $role;
    }

    set_flash_message('success', "User details and permissions for '{$full_name}' updated successfully.");
    header("Location: " . BASE_URL . "/admin/staff.php");
    exit;
}

// Handle Toggle Active Status
if (isset($_GET['toggle_status'])) {
    $targetId = (int)$_GET['toggle_status'];
    if ($targetId !== (int)$user['id']) {
        $targetUser = db_fetch_one("SELECT full_name, is_active FROM users WHERE id = ? LIMIT 1", [$targetId]);
        if ($targetUser) {
            $newStatus = $targetUser['is_active'] ? 0 : 1;
            db_query("UPDATE users SET is_active = ? WHERE id = ?", [$newStatus, $targetId]);
            $actionLabel = $newStatus ? 'activated' : 'deactivated';
            set_flash_message('success', "User account for '{$targetUser['full_name']}' has been {$actionLabel}.");
        }
    } else {
        set_flash_message('warning', 'You cannot deactivate your own active account.');
    }
    header("Location: " . BASE_URL . "/admin/staff.php");
    exit;
}

$staff_members = db_fetch_all("SELECT * FROM users ORDER BY role ASC, full_name ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Staff Accounts & Permissions</h4>
    <p class="text-muted small mb-0">Manage team accounts, edit user roles, and control access permissions across the system.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addStaffModal">
    <i class="bi bi-person-plus"></i> Add Staff Account
  </button>
</div>

<!-- Role Explanations Banner -->
<div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 small">
  <div class="d-flex gap-2">
    <i class="bi bi-info-circle-fill fs-5 text-info flex-shrink-0"></i>
    <div>
      <strong>Access Permissions Guide:</strong><br>
      &bull; <strong>Admin:</strong> Full access to financial collections, invoices, system settings, Brevo config, and staff management.<br>
      &bull; <strong>Staff (Tailor / Cutter):</strong> Limited access to updating order production stages, entering measurements, logging partial batch deliveries, and viewing inquiries. Financial totals and settings are restricted.
    </div>
  </div>
</div>

<!-- Staff Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table admin-table align-middle mb-0">
      <thead>
        <tr>
          <th>Staff Member</th>
          <th>Username</th>
          <th>Role</th>
          <th>Contact Phone</th>
          <th>Status</th>
          <th>Joined</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff_members as $s): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle <?= $s['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?> text-white d-inline-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width: 34px; height: 34px;">
                  <?= strtoupper(substr($s['username'], 0, 1)) ?>
                </div>
                <div>
                  <div class="fw-bold text-dark"><?= e($s['full_name']) ?></div>
                  <small class="text-muted"><?= e($s['email']) ?></small>
                </div>
              </div>
            </td>
            <td><code><?= e($s['username']) ?></code></td>
            <td>
              <?php if ($s['role'] === 'admin'): ?>
                <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                  <i class="bi bi-shield-lock-fill" style="font-size:0.75rem;"></i> Administrator
                </span>
              <?php else: ?>
                <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                  <i class="bi bi-scissors" style="font-size:0.75rem;"></i> Tailor / Cutter
                </span>
              <?php endif; ?>
            </td>
            <td><?= e($s['phone'] ?: '-') ?></td>
            <td>
              <?php if ($s['is_active']): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-danger">Inactive</span>
              <?php endif; ?>
            </td>
            <td><small class="text-muted"><?= format_date($s['created_at']) ?></small></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <!-- Edit User Button -->
                <button type="button" 
                        class="btn btn-outline-primary btn-edit-user"
                        title="Edit User Details & Role"
                        data-id="<?= $s['id'] ?>"
                        data-fullname="<?= e($s['full_name']) ?>"
                        data-username="<?= e($s['username']) ?>"
                        data-email="<?= e($s['email']) ?>"
                        data-role="<?= e($s['role']) ?>"
                        data-phone="<?= e($s['phone']) ?>"
                        data-active="<?= (int)$s['is_active'] ?>"
                        data-is-self="<?= ((int)$s['id'] === (int)$user['id']) ? '1' : '0' ?>">
                  <i class="bi bi-pencil-square me-1"></i> Edit
                </button>

                <!-- Toggle Active Status with Modal Confirmation -->
                <?php if ((int)$s['id'] !== (int)$user['id']): ?>
                  <button type="button" 
                          class="btn <?= $s['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-confirm-action"
                          title="<?= $s['is_active'] ? 'Deactivate User' : 'Activate User' ?>"
                          data-title="Confirm Account Status Change"
                          data-message="Are you sure you want to <?= $s['is_active'] ? 'deactivate' : 'activate' ?> the account for '<?= e($s['full_name']) ?>' (<?= e($s['username']) ?>)?"
                          data-confirm-text="Yes, <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>"
                          data-confirm-class="<?= $s['is_active'] ? 'btn-danger' : 'btn-success' ?>"
                          data-href="<?= BASE_URL ?>/admin/staff.php?toggle_status=<?= $s['id'] ?>">
                    <i class="bi <?= $s['is_active'] ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                    <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                <?php else: ?>
                  <span class="btn btn-light border disabled text-muted" title="Active Session">
                    <i class="bi bi-shield-check text-primary me-1"></i> You
                  </span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Staff Account -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" action="">
        <input type="hidden" name="action_add_staff" value="1">
        <div class="modal-header border-bottom bg-light px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-person-plus"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="addStaffModalLabel">Create Staff Account</h5>
              <small class="text-muted">Add a new user with dedicated role permissions</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. John Mwangi" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" placeholder="e.g. tailor_john" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Role <span class="text-danger">*</span></label>
              <select name="role" class="form-select">
                <option value="staff" selected>Tailor / Cutter (Limited)</option>
                <option value="admin">Administrator (Full Access)</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="john@stitchcraftmasters.co.ke" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Phone Number</label>
            <input type="tel" name="phone" class="form-control" placeholder="0722 000 000">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Account Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters (bcrypt)" required minlength="6">
          </div>
        </div>
        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Staff Account & Permissions -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" action="" id="editStaffForm">
        <input type="hidden" name="action_edit_staff" value="1">
        <input type="hidden" name="user_id" id="edit_user_id" value="">
        
        <div class="modal-header border-bottom bg-light px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-person-gear fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="editStaffModalLabel">Edit User Account & Role</h5>
              <small class="text-muted">Update profile, security, and permissions</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <!-- Self-Edit Notice Alert -->
          <div id="edit_self_alert" class="alert alert-warning small py-2 px-3 mb-3 d-none">
            <i class="bi bi-shield-exclamation me-1"></i>
            <strong>Note:</strong> You are editing your currently active account. To prevent lockout, Administrator role and active status are locked for your own session.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Username <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                <input type="text" name="username" id="edit_username" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">System Role <span class="text-danger">*</span></label>
              <select name="role" id="edit_role" class="form-select">
                <option value="staff">Tailor / Cutter (Limited)</option>
                <option value="admin">Administrator (Full Access)</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Phone Number</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
              <input type="tel" name="phone" id="edit_phone" class="form-control" placeholder="0722 000 000">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Account Active Status</label>
            <div class="form-check form-switch mt-1">
              <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="edit_is_active" value="1" checked>
              <label class="form-check-label small" for="edit_is_active">Account is Active (can sign in)</label>
            </div>
          </div>

          <hr class="my-3 text-muted">

          <div class="mb-2">
            <label class="form-label small fw-bold">
              Change Password 
              <span class="text-muted fw-normal">(leave blank to keep existing)</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
              <input type="password" name="password" id="edit_password" class="form-control" placeholder="Enter new password (min 6 characters)" minlength="6">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light px-4 py-3">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

