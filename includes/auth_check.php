<?php
/**
 * Authentication and Access Control Guard
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently logged into an admin/staff session
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['admin', 'staff']);
}

/**
 * Check if a customer (individual or institution) is currently logged in
 * @return bool
 */
function is_customer_logged_in() {
    return !empty($_SESSION['customer_id']);
}

/**
 * Return current logged-in customer profile data
 * @return array|null
 */
function get_logged_in_customer() {
    if (!is_customer_logged_in()) {
        return null;
    }
    return [
        'id'               => (int)$_SESSION['customer_id'],
        'type'             => $_SESSION['customer_type'] ?? 'individual',
        'name'             => $_SESSION['customer_name'] ?? 'Valued Customer',
        'email'            => $_SESSION['customer_email'] ?? '',
        'phone'            => $_SESSION['customer_phone'] ?? '',
        'institution_name' => $_SESSION['customer_institution'] ?? null
    ];
}

/**
 * Restrict access strictly to authenticated customers
 * @param string|null $redirectAfter
 */
function require_customer_login($redirectAfter = null) {
    if (!is_customer_logged_in()) {
        $target = $redirectAfter ?: $_SERVER['REQUEST_URI'];
        set_flash_message('info', 'Please sign in to your customer account to access this page.');
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($target));
        exit;
    }
}

/**
 * Return current logged-in user data (staff / admin)
 * @return array|null
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['user_name'] ?? 'User',
        'full_name' => $_SESSION['user_fullname'] ?? 'Staff Member',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'staff'
    ];
}

/**
 * Check if the current user has a specific role (e.g. 'admin')
 * @param string $role
 * @return bool
 */
function has_role($role) {
    return is_logged_in() && ($_SESSION['user_role'] === $role);
}

/**
 * Restrict access to authenticated staff or admin users
 */
function require_login() {
    if (!is_logged_in()) {
        if (is_customer_logged_in()) {
            set_flash_message('warning', 'You are signed in as a Customer. Workshop management controls are restricted to staff.');
            header("Location: " . BASE_URL . "/my_account.php");
            exit;
        }
        $loginUrl = BASE_URL . '/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        header("Location: " . $loginUrl);
        exit;
    }
}

/**
 * Restrict access strictly to users with 'admin' role
 */
function require_admin_role() {
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        set_flash_message('danger', 'Access restricted. You do not have permission to access financial records or administrative controls.');
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit;
    }
}

