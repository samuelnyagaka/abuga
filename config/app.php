<?php
/**
 * Application Global Configuration
 * Tailor Business Management & Marketing System
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Ensure database config is available
require_once __DIR__ . '/database.php';

// Set default timezone for entire PHP application
date_default_timezone_set('Africa/Nairobi');

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session security configurations
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Business Information
define('BUSINESS_NAME', 'StitchCraft & Uniform Masters');
define('BUSINESS_TAGLINE', 'Precision Tailoring for Schools, Corporate & Bespoke Fashion');
define('BUSINESS_PHONE', '+254 712 345 678');
define('BUSINESS_WHATSAPP', '254712345678'); // International format without + or spaces for wa.me
define('BUSINESS_EMAIL', 'info@stitchcraftmasters.co.ke');
define('BUSINESS_ADDRESS', 'Biashara Plaza, 2nd Floor, Suite 14, Moi Avenue, Nairobi, Kenya');
define('BUSINESS_HOURS', 'Mon - Sat: 8:00 AM - 6:00 PM | Sun: Closed');

// Currency Configuration
define('CURRENCY_CODE', 'KES');
define('CURRENCY_SYMBOL', 'KSh ');

// Email Delivery via Brevo (Sendinblue) HTTPS API
// On InfinityFree: PHP mail() & SMTP ports are blocked. All outbound emails use the Brevo HTTPS REST API.
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: ''); // Insert Brevo v3 API Key here or in admin settings
define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'notifications@stitchcraftmasters.co.ke');
define('BREVO_SENDER_NAME', BUSINESS_NAME);

// Cron Runner Security Token (used when pinged via external services like cron-job.org)
define('CRON_SECURITY_TOKEN', getenv('CRON_TOKEN') ?: 'tailor_cron_secure_token_2026');

// Image Upload Constraints (Free-tier hosting limits)
define('MAX_UPLOAD_SIZE_BYTES', 4 * 1024 * 1024); // 4MB maximum upload
define('MAX_IMAGE_WIDTH', 1200);                  // Max resolution after GD resize
define('MAX_IMAGE_HEIGHT', 1200);
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');

// Detect Base URL dynamically
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Determine path relative to document root
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Normalize admin subdirectory to root base
    if (substr($scriptDir, -6) === '/admin') {
        $scriptDir = substr($scriptDir, 0, -6);
    }
    $basePath = rtrim($scriptDir, '/');
    
    return $protocol . $host . $basePath;
}

define('BASE_URL', get_base_url());
