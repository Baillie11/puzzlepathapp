<?php
// Unified Puzzle Path Configuration - FINAL VERSION (Supports All Booking Code Formats)
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

// UPDATED: Enhanced function to extract hunt code from ALL booking number formats
function extractHuntCodeFromBooking($booking_number) {
    // Sanitize input
    $booking_number = preg_replace('/[^A-Za-z0-9\\-_]/', '', $booking_number);
    
    if (empty($booking_number)) {
        return null;
    }
    
    // Convert to uppercase for consistent processing
    $booking_upper = strtoupper($booking_number);
    
    // Pattern 1: Check for full hunt codes that match database hunt_codes exactly
    $full_hunt_codes = ['BBQ123', 'ELP729', 'CLG562', 'SFP921', 'KTL830', 'SPB441', 'RKP123', 'SRP456', 'HHQ642', 'RSQ777', 'TSTQ01'];
    
    foreach ($full_hunt_codes as $hunt_code) {
        if (strpos($booking_upper, $hunt_code) === 0) {
            return $hunt_code; // Return the full database hunt code directly
        }
    }
    
    // Pattern 2: Standard format (PREFIX-YYYYMMDD-XXXX or PREFIX-XXXXXX) for shorter codes
    $parts = explode('-', $booking_upper);
    if (count($parts) >= 1) {
        $prefix = $parts[0];
        
        // Check if prefix is a known hunt code
        $known_codes = ['BB', 'EP', 'BBR1', 'PP', 'CLG', 'SFP', 'KTL', 'SPB', 'RKP', 'SRP', 'HHQ', 'RSQ'];
        if (in_array($prefix, $known_codes)) {
            return $prefix;
        }
    }
    
    // Pattern 3: Check for embedded hunt codes in booking number
    $hunt_patterns = [
        '/^BBR1/i' => 'BBR1',
        '/^BB/i' => 'BB', 
        '/^EP/i' => 'EP',
        '/^PP/i' => 'PP',
        '/^CLG/i' => 'CLG',
        '/^SFP/i' => 'SFP',
        '/^KTL/i' => 'KTL',
        '/^SPB/i' => 'SPB',
        '/^RKP/i' => 'RKP',
        '/^SRP/i' => 'SRP',
        '/^HHQ/i' => 'HHQ',
        '/^RSQ/i' => 'RSQ',
        // Add pattern for any booking that contains these codes
        '/BBR1/i' => 'BBR1',
        '/BROADBEACH/i' => 'BB',
        '/EMERALD/i' => 'EP'
    ];
    
    foreach ($hunt_patterns as $pattern => $hunt_code) {
        if (preg_match($pattern, $booking_upper)) {
            return $hunt_code;
        }
    }
    
    // Pattern 4: For any unrecognized format, default to BBR1 (most common hunt)
    // This ensures all bookings work even if format changes again
    return 'BBR1';
}

// UPDATED: Secure mapping function with validation (handles all hunt codes)
function mapBookingCodeToHuntCode($booking_code) {
    // Validate input
    if (!is_string($booking_code) || empty($booking_code)) {
        return 'BBR1'; // Default fallback
    }
    
    $booking_code = strtoupper(trim($booking_code));
    
    // Whitelist of valid mappings - map to appropriate database hunt codes
    $mapping = [
        // Full hunt codes (direct mapping)
        'BBQ123' => 'BBQ123', // Broadbeach Adventurer Quest (direct)
        'ELP729' => 'ELP729', // Emerald Lakes Explorer Quest (direct)
        'CLG562' => 'CLG562', // Coolangatta Walking Quest (direct)
        'SFP921' => 'SFP921', // Surfers Paradise Explorer Quest (direct)
        'KTL830' => 'KTL830', // Koala Trail Driving Quest (direct)
        'SPB441' => 'SPB441', // Springbrook Alpaca Adventure (direct)
        'RKP123' => 'RKP123', // Currumbin Rockpools Quest (direct)
        'SRP456' => 'SRP456', // Southport Rockpools Walking Quest (direct)
        'HHQ642' => 'HHQ642', // The Hinterland Hideaway Quest (direct)
        'RSQ777' => 'RSQ777', // Risqué Rendezvous (direct)
        'TSTQ01' => 'TSTQ01', // Sandbox Test Quest (direct)
        
        // Short codes (mapped to full hunt codes)
        'BB' => 'BBQ123',     // Broadbeach booking codes → BBQ123 hunt
        'BBR1' => 'BBQ123',   // BBR1 codes → BBQ123 hunt
        'EP' => 'ELP729',     // Emerald Park booking codes → ELP729 hunt
        'ELP' => 'ELP729',    // ELP codes → ELP729 hunt
        'CLG' => 'CLG562',    // Coolangatta codes → CLG562 hunt
        'SFP' => 'SFP921',    // Surfers Paradise codes → SFP921 hunt
        'KTL' => 'KTL830',    // Koala Trail codes → KTL830 hunt
        'SPB' => 'SPB441',    // Springbrook codes → SPB441 hunt
        'RKP' => 'RKP123',    // Currumbin Rockpools codes → RKP123 hunt
        'SRP' => 'SRP456',    // Southport codes → SRP456 hunt
        'HHQ' => 'HHQ642',    // Hinterland codes → HHQ642 hunt
        'RSQ' => 'RSQ777',    // Risque codes → RSQ777 hunt
        'TSTQ' => 'TSTQ01',   // Test codes → TSTQ01 hunt
        'PP' => 'BBQ123'      // PP codes → BBQ123 hunt (backwards compatibility)
    ];
    
    return isset($mapping[$booking_code]) ? $mapping[$booking_code] : 'BBR1';
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

// Format quest/hunt names for display (convert underscores/hyphens to spaces, capitalize words)
function formatQuestName($name) {
    if (empty($name)) {
        return '';
    }
    
    // Replace underscores, hyphens, and other separators with spaces
    $formatted = preg_replace('/[_\-]+/', ' ', $name);
    
    // Convert to title case (capitalize first letter of each word)
    $formatted = ucwords(strtolower(trim($formatted)));
    
    return $formatted;
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
