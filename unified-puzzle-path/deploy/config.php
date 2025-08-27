<?php
// Unified Puzzle Path Configuration
// This file contains all database and application settings

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ozbizfin_wp793');          // WordPress database for bookings
define('DB_USER', 'ozbizfin_wp793');
define('DB_PASS', 'bS[O61@p7l');

// User Database Configuration (for registration/login)
define('USER_DB_HOST', 'localhost');
define('USER_DB_NAME', 'ozbizfin_puzzlepath');
define('USER_DB_USER', 'ozbizfin_questuser');
define('USER_DB_PASS', 'yGwPwjOMziXO6L');

// Application Settings
define('APP_NAME', 'Puzzle Path');
define('APP_VERSION', '2.0.0');
define('DEFAULT_TIMEZONE', 'Australia/Brisbane');

// Quest Settings
define('MIN_QUEST_TIME', 300);    // 5 minutes minimum (for testing)
define('MAX_QUEST_TIME', 14400);  // 4 hours maximum

// File paths
define('UPLOAD_PATH', 'uploads/');
define('MEDAL_IMAGES_PATH', 'medals/');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Database connection functions
function getMainDb() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

function getUserDb() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(USER_DB_HOST, USER_DB_USER, USER_DB_PASS, USER_DB_NAME);
        if ($conn->connect_error) {
            die("User database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Helper function to extract hunt code from booking number
function extractHuntCodeFromBooking($booking_number) {
    // Expected format: BB-YYYYMMDD-XXXX or EP-YYYYMMDD-XXXX
    $parts = explode('-', $booking_number);
    if (count($parts) >= 1) {
        return strtoupper($parts[0]);
    }
    
    // Fallback: check if booking number contains known hunt codes
    $booking_upper = strtoupper($booking_number);
    if (strpos($booking_upper, 'BB') !== false) return 'BB';
    if (strpos($booking_upper, 'EP') !== false) return 'EP';
    if (strpos($booking_upper, 'BROADBEACH') !== false) return 'BB';
    if (strpos($booking_upper, 'EMERALD') !== false) return 'EP';
    
    return null;
}

// Function to log errors
function logError($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log_entry .= " - Context: " . json_encode($context);
    }
    error_log($log_entry . PHP_EOL, 3, 'error.log');
}

// Function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to generate CSRF tokens
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
