<?php
/**
 * Admin Panel Header Template
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';

// Ensure user is logged in
require_login();

$user = get_current_user_data();
$current_page = basename($_SERVER['PHP_SELF']);

// Quick operational badges
$new_leads_count = (int)db_scalar("SELECT COUNT(*) FROM leads WHERE status = 'new'");
$active_orders_count = (int)db_scalar("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'in_production')");
$low_stock_count = (int)db_scalar("SELECT COUNT(*) FROM inventory WHERE quantity_on_hand <= reorder_threshold");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($admin_page_title) ? e($admin_page_title) . ' | ' : '' ?>Tailor Operations Portal</title>
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Admin Styles with Cache-Busting -->
  <link rel="stylesheet" href="<?= asset_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <i class="bi bi-scissors text-warning"></i>
      <span>StitchCraft Ops</span>
    </div>

    <ul class="sidebar-menu">
      <li class="menu-header">Overview</li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>

      <li class="menu-header">CRM & Pipeline</li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/inquiries.php" class="<?= in_array($current_page, ['inquiries.php', 'inquiry_view.php']) ? 'active' : '' ?>">
          <i class="bi bi-inbox"></i> Leads & Inquiries
          <?php if ($new_leads_count > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $new_leads_count ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/customers.php" class="<?= in_array($current_page, ['customers.php', 'customer_view.php']) ? 'active' : '' ?>">
          <i class="bi bi-people"></i> Customers Directory
        </a>
      </li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/measurements.php" class="<?= $current_page == 'measurements.php' ? 'active' : '' ?>">
          <i class="bi bi-rulers"></i> Measurement Profiles
        </a>
      </li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/orders.php" class="<?= in_array($current_page, ['orders.php', 'order_create.php', 'order_view.php']) ? 'active' : '' ?>">
          <i class="bi bi-kanban"></i> Orders & Pipeline
          <?php if ($active_orders_count > 0): ?>
            <span class="badge bg-primary ms-auto"><?= $active_orders_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="menu-header">Workshop & Stock</li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/inventory.php" class="<?= $current_page == 'inventory.php' ? 'active' : '' ?>">
          <i class="bi bi-box-seam"></i> Fabrics & Trims
          <?php if ($low_stock_count > 0): ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $low_stock_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <?php if (has_role('admin')): ?>
        <li class="menu-header">Marketing & CMS</li>
        <li class="menu-item">
          <a href="<?= BASE_URL ?>/admin/content_manager.php" class="<?= $current_page == 'content_manager.php' ? 'active' : '' ?>">
            <i class="bi bi-layout-text-window-reverse"></i> Website Content
          </a>
        </li>

        <li class="menu-header">Administration</li>
        <li class="menu-item">
          <a href="<?= BASE_URL ?>/admin/staff.php" class="<?= $current_page == 'staff.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> Staff Management
          </a>
        </li>
        <li class="menu-item">
          <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> System Settings
          </a>
        </li>
      <?php endif; ?>

      <li class="menu-header">External</li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/index.php" target="_blank">
          <i class="bi bi-box-arrow-up-right"></i> View Public Site
        </a>
      </li>
      <li class="menu-item">
        <a href="<?= BASE_URL ?>/admin/logout.php" class="text-danger">
          <i class="bi bi-box-arrow-left"></i> Sign Out
        </a>
      </li>
    </ul>
  </aside>

  <!-- Main Wrapper -->
  <div class="admin-content-wrapper">
    
    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-lg-none" type="button" aria-label="Toggle Sidebar">
          <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fw-semibold text-dark d-none d-sm-inline"><?= isset($admin_page_title) ? e($admin_page_title) : 'Dashboard' ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/admin/order_create.php" class="btn btn-sm btn-primary d-none d-sm-inline-flex align-items-center gap-1">
          <i class="bi bi-plus-circle"></i> New Order
        </a>
        
        <div class="dropdown">
          <button class="btn btn-light btn-sm dropdown-toggle border d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
              <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </span>
            <span class="small fw-semibold"><?= e($user['full_name']) ?></span>
            <span class="badge bg-secondary" style="font-size: 0.65rem;"><?= ucfirst($user['role']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><h6 class="dropdown-header small">Signed in as <strong><?= e($user['username']) ?></strong></h6></li>
            <li><a class="dropdown-item small" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Sign Out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Page Content Container -->
    <main class="admin-main-container">
      <?= display_flash_messages() ?>
