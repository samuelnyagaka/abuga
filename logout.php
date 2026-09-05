<?php
/**
 * Logout Handler
 * Tailor Business Management & Marketing System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';

// Clear all session data
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
session_start();

set_flash_message('success', 'You have been signed out successfully. Have a wonderful day!');
header("Location: " . BASE_URL . "/index.php");
exit;
