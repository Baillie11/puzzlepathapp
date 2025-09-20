<?php
// Unified Puzzle Path Configuration - LIVE SITE VERSION
// This file contains all database and application settings with enhanced security

// Security: Check if accessed directly
if (!defined('PUZZLE_PATH_ACCESS') && !defined('ABSPATH')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// Define access constant for this session
define('PUZZLE_PATH_ACCESS', true);

// LIVE SITE DATABASE CONFIGURATION - Based on your deploy settings
define('DB_HOST', 'localhost'); // Your live database host
define('DB_NAME', 'ozbizfin_wp793'); // Your live WordPress database name
define('DB_USER', 'ozbizfin_wp793'); // Your live database username
define('DB_PASS', 'bS[O61@p7l'); // Your live database password

// Validate required database credentials
if (empty(DB_NAME) || empty(DB_USER) || empty(DB_PASS)) {
    error_log('PuzzlePath: Missing required database credentials');
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        die('Database configuration error. Please check your settings.');
    }
}

// Application Settings
define('APP_NAME', 'Puzzle Path');
define('APP_VERSION', '2.1.0');
define('DEFAULT_TIMEZONE', 'Australia/Brisbane');

// Security Settings
define('PP_HASH_ALGO', 'sha256');
define('PP_SESSION_TIMEOUT', 3600); // 1 hour
define('PP_MAX_LOGIN_ATTEMPTS', 5);
define('PP_RATE_LIMIT_WINDOW', 300); // 5 minutes

// Quest Settings with validation
define('MIN_QUEST_TIME', 300);    // 5 minutes minimum
define('MAX_QUEST_TIME', 14400);  // 4 hours maximum

// File paths with proper validation
define('UPLOAD_PATH', 'uploads/');
define('MEDAL_IMAGES_PATH', 'medals/');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Database connection with enhanced error handling and security
function getMainDb() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Set charset to prevent character set confusion attacks
            if (!$conn->set_charset("utf8mb4")) {
                throw new Exception("Error loading character set utf8mb4: " . $conn->error);
            }
            
            // Set SQL mode for strict data handling
            $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
        } catch (Exception $e) {
            // Log error securely (don't expose credentials)
            error_log('PuzzlePath DB Connection Error: ' . $e->getMessage());
            
            // Don't expose connection details in production
            throw new Exception("Database connection unavailable");
        }
    }
    
    return $conn;
}

// Secure helper function to extract hunt code from booking number
function extractHuntCodeFromBooking($booking_number) {
    // Sanitize input
    $booking_number = preg_replace('/[^A-Za-z0-9\\-_]/', '', $booking_number);
    
    if (empty($booking_number)) {
        return null;
    }
    
    // Expected format: BB-YYYYMMDD-XXXX or EP-YYYYMMDD-XXXX
    $parts = explode('-', $booking_number);
    if (count($parts) >= 1) {
        $code = strtoupper($parts[0]);
        // Validate code format
        if (preg_match('/^[A-Z]{1,4}$/', $code)) {
            return $code;
        }
    }
    
    // Fallback: check if booking number contains known hunt codes
    $booking_upper = strtoupper($booking_number);
    $valid_codes = ['BB', 'EP', 'BBR1', 'PP']; // Define valid codes including PP
    
    foreach ($valid_codes as $code) {
        if (strpos($booking_upper, $code) !== false) {
            return $code;
        }
    }
    
    return null;
}

// Secure mapping function with validation
function mapBookingCodeToHuntCode($booking_code) {
    // Validate input
    if (!is_string($booking_code) || empty($booking_code)) {
        return null;
    }
    
    $booking_code = strtoupper(trim($booking_code));
    
    // Whitelist of valid mappings
    $mapping = [
        'BB' => 'BBR1',  // Broadbeach booking code maps to BBR1 in database
        'EP' => 'EP',    // Emerald Park
        'BBR1' => 'BBR1', // Direct mapping
    ];
    
    return isset($mapping[$booking_code]) ? $mapping[$booking_code] : null;
}

// Enhanced error logging with security considerations
function logError($message, $context = [], $level = 'ERROR') {
    // Sanitize message to prevent log injection
    $message = filter_var($message, FILTER_SANITIZE_STRING);
    
    // Create structured log entry
    $log_entry = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200)
    ];
    
    // Add context if provided (be careful with sensitive data)
    if (!empty($context)) {
        // Remove sensitive keys
        $sensitive_keys = ['password', 'token', 'key', 'secret', 'auth'];
        foreach ($context as $key => $value) {
            $key_lower = strtolower($key);
            foreach ($sensitive_keys as $sensitive) {
                if (strpos($key_lower, $sensitive) !== false) {
                    $context[$key] = '[REDACTED]';
                    break;
                }
            }
        }
        $log_entry['context'] = $context;
    }
    
    // Write to error log
    error_log('PuzzlePath: ' . json_encode($log_entry));
}

// Secure HTML output function
function h($string, $encoding = 'UTF-8') {
    if ($string === null || $string === '') {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, $encoding);
}

// Rate limiting function
function checkRateLimit($action) {
    // Simple file-based rate limiting for live site
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_file = sys_get_temp_dir() . '/puzzlepath_rate_' . md5($ip . $action) . '.tmp';
    
    $current_time = time();
    $rate_data = [];
    
    if (file_exists($rate_file)) {
        $rate_data = json_decode(file_get_contents($rate_file), true) ?: [];
    }
    
    // Clean old entries
    $rate_data = array_filter($rate_data, function($timestamp) use ($current_time) {
        return ($current_time - $timestamp) < PP_RATE_LIMIT_WINDOW;
    });
    
    // Check if limit exceeded
    if (count($rate_data) >= PP_MAX_LOGIN_ATTEMPTS) {
        return false;
    }
    
    // Add current request
    $rate_data[] = $current_time;
    file_put_contents($rate_file, json_encode($rate_data));
    
    return true;
}

// Input validation function
function validateInput($input, $type) {
    switch ($type) {
        case 'booking_number':
            // Allow alphanumeric, hyphens, and underscores
            $cleaned = preg_replace('/[^A-Za-z0-9\-_]/', '', $input);
            return !empty($cleaned) && strlen($cleaned) <= 50 ? $cleaned : false;
        
        default:
            return filter_var($input, FILTER_SANITIZE_STRING);
    }
}

// Production settings
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
?>

<!-- 
DEPLOYMENT INSTRUCTIONS:

1. Save this file as "config-secure.php" (remove the "-live" from the filename)

2. Update the database credentials at the top of this file:
   - DB_HOST: Your live database host (usually 'localhost')
   - DB_NAME: Your live WordPress database name  
   - DB_USER: Your live database username
   - DB_PASS: Your live database password

3. Upload to your live site in the same directory as your Puzzle Path app

4. The file should work with your existing verify_booking.php

NOTE: I've included your cPanel database credentials from the deploy folder, but please verify these are correct for your live site.
-->
