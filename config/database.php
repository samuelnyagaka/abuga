<?php
/**
 * Database Configuration
 * Tailor Business Management & Marketing System
 * 
 * Optimized for InfinityFree Shared Hosting & Local Development.
 */

// Prevent direct script access
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Database Credentials for abuga.infinityfreeapp.com
define('DB_HOST', getenv('DB_HOST') ?: 'sql208.infinityfree.com');
define('DB_USER', getenv('DB_USER') ?: 'if0_42838997');
define('DB_PASS', getenv('DB_PASS') ?: 'exP7XJ5oaiNC');
// When you create the database in vPanel, name it "tailor" so the full name is if0_42838997_tailor
define('DB_NAME', getenv('DB_NAME') ?: 'if0_42838997_tailor');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

/**
 * Establish and return MySQLi connection
 * @return mysqli
 */
function get_db_connection() {
    static $conn = null;

    if ($conn === null) {
        // Disable default MySQLi error reporting to prevent credential exposure in stack traces
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn->connect_errno) {
            // Friendly error message that does not leak credentials
            error_log("Database connection failed: " . $conn->connect_error);
            die("
                <div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f87171; border-radius: 8px; background: #fef2f2;'>
                    <h2 style='color: #b91c1c; margin-top: 0;'>Database Connection Notice</h2>
                    <p style='color: #4b5563;'>The system could not connect to the MySQL database. Please verify your credentials in <code>config/database.php</code> or ensure the database is imported in phpMyAdmin.</p>
                </div>
            ");
        }

        // Set utf8mb4 encoding for international characters & emoji support
        $conn->set_charset("utf8mb4");

        // Align MySQL session timezone with East Africa Time (UTC+3)
        $conn->query("SET time_zone = '+03:00'");
    }

    return $conn;
}
