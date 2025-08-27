<?php
// Unified Puzzle Path Configuration
// This file contains all database and application settings

// Database Configuration - Local Development
// Using local XAMPP database (same as WordPress)
define('DB_HOST', 'localhost');
define('DB_NAME', 'puzzlepath_wp');          // Local WordPress database
define('DB_USER', 'wpuser');
define('DB_PASS', 'wp123456');

// Application Settings
define('APP_NAME', 'Puzzle Path');
define('APP_VERSION', '2.0.0');
define('DEFAULT_TIMEZONE', 'Australia/Brisbane');

// WordPress Database Access (direct connection - no API needed)
// The app connects directly to the WordPress database to check bookings

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

// Only using single WordPress database - getUserDb() removed
// All functions now use getMainDb() which connects to the WordPress database

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
        'BB' => 'BBR1',  // Broadbeach booking code maps to BBR1 in database
        'EP' => 'EP',    // Emerald Park (adjust if different in your database)
    ];
    
    return $mapping[$booking_code] ?? $booking_code;
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

// Function to make API requests with better error handling
function makeApiRequest($url, $method = 'GET', $data = null, $timeout = 30) {
    // Try cURL first (preferred method)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        
        // Basic cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Puzzle Path App v' . APP_VERSION);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Log detailed error information
        if ($response === false || !empty($error)) {
            logError("cURL API request failed", [
                'url' => $url,
                'method' => $method,
                'http_code' => $http_code,
                'curl_error' => $error,
                'response' => $response
            ]);
            return false;
        }
        
        // Check for HTTP errors
        if ($http_code >= 400) {
            logError("API returned HTTP error", [
                'url' => $url,
                'http_code' => $http_code,
                'response' => $response
            ]);
            // Still return the response for error handling by caller
        }
        
        return $response;
        
    } else if (ini_get('allow_url_fopen')) {
        // Fallback to file_get_contents if cURL not available
        $context_options = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'header' => "User-Agent: Puzzle Path App v" . APP_VERSION . "\r\n" .
                           "Content-Type: application/json\r\n" .
                           "Accept: application/json\r\n"
            ]
        ];
        
        if ($method === 'POST' && $data) {
            $context_options['http']['content'] = is_string($data) ? $data : json_encode($data);
        }
        
        $context = stream_context_create($context_options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            logError("file_get_contents API request failed", [
                'url' => $url,
                'method' => $method,
                'error' => $error['message'] ?? 'Unknown error'
            ]);
            return false;
        }
        
        return $response;
        
    } else {
        // Both methods unavailable
        logError("No HTTP client available", [
            'curl_available' => function_exists('curl_init'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'url' => $url
        ]);
        return false;
    }
}

?>
