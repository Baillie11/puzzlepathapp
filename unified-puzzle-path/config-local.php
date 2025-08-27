<?php
/**
 * Puzzle Path Unified App - Local Development Configuration
 * 
 * This file contains configuration for local XAMPP environment
 */

// Database Configuration - Local XAMPP
// Using the same database as WordPress for integration
define('DB_HOST', 'localhost');
define('DB_NAME', 'puzzlepath_wp');           // Same database as WordPress for integration
define('DB_USER', 'wpuser');
define('DB_PASS', 'wp123456');

// Application Settings
define('APP_NAME', 'Puzzle Path');
define('APP_VERSION', '2.0.0');
define('DEFAULT_TIMEZONE', 'Australia/Brisbane');

// Quest Settings
define('MIN_QUEST_TIME', 60);     // 1 minute minimum (for testing)
define('MAX_QUEST_TIME', 14400);  // 4 hours maximum

// File paths (relative to application)
define('UPLOAD_PATH', 'uploads/');
define('MEDAL_IMAGES_PATH', 'medals/');

// Local development settings
define('DEBUG_MODE', true);       // Enable for local development
define('LOG_ERRORS', true);
define('DISPLAY_ERRORS', true);   // Show errors during development

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Database connection functions
function getMainDb() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $conn->connect_error);
            } else {
                die("Database connection failed. Please try again later.");
            }
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

// Helper function to map booking codes to database hunt codes
function mapBookingCodeToHuntCode($booking_code) {
    $mapping = [
        'BB' => 'BB',   // Broadbeach
        'EP' => 'EP',   // Emerald Park
    ];
    
    return $mapping[$booking_code] ?? $booking_code;
}

// Function to log errors (local development)
function logError($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log_entry .= " - Context: " . json_encode($context);
    }
    
    // Log to file and display if in debug mode
    error_log($log_entry . PHP_EOL, 3, 'error.log');
    
    if (DEBUG_MODE) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
        echo "<strong>Debug Log:</strong> " . htmlspecialchars($log_entry);
        echo "</div>";
    }
}

// Function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to generate CSRF tokens
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set error reporting for local development
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

?>
