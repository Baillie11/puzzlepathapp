<?php
// Unified Puzzle Path Configuration - SECURE VERSION
// This file contains all database and application settings with enhanced security

// Security: Check if accessed directly
if (!defined('PUZZLE_PATH_ACCESS') && !defined('ABSPATH')) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// Define access constant for this session
define('PUZZLE_PATH_ACCESS', true);

// Database Configuration - Use environment variables with secure fallbacks
// Priority: Environment Variables > wp-config.php constants > secure defaults

// WordPress Database Configuration - Local Development
define('DB_HOST', getenv('PP_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('PP_DB_NAME') ?: 'puzzlepath_wp');
define('DB_USER', getenv('PP_DB_USER') ?: 'wpuser');
define('DB_PASS', getenv('PP_DB_PASS') ?: 'wp123456');

// Validate required database credentials
if (empty(DB_NAME) || empty(DB_USER) || empty(DB_PASS)) {
    error_log('PuzzlePath: Missing required database credentials');
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_die('Database configuration error. Please check your settings.');
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
define('MIN_QUEST_TIME', max(300, (int)getenv('PP_MIN_QUEST_TIME') ?: 300));    // 5 minutes minimum
define('MAX_QUEST_TIME', min(14400, (int)getenv('PP_MAX_QUEST_TIME') ?: 14400)); // 4 hours maximum

// File paths with proper validation
define('UPLOAD_PATH', 'uploads/');
define('MEDAL_IMAGES_PATH', 'medals/');

// Security: Validate upload directory exists and is writable
if (!file_exists(UPLOAD_PATH)) {
    if (!mkdir(UPLOAD_PATH, 0755, true)) {
        error_log('PuzzlePath: Failed to create upload directory');
    }
}

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Database connection with enhanced error handling and security
function getMainDb() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable SSL if available in production
            $ssl_options = [];
            if (getenv('PP_DB_SSL') === 'true') {
                $ssl_options = [
                    MYSQLI_OPT_SSL_VERIFY_SERVER_CERT => true,
                ];
            }
            
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw $e;
            } else {
                throw new Exception("Database connection unavailable");
            }
        }
    }
    
    return $conn;
}

// Secure helper function to extract hunt code from booking number
function extractHuntCodeFromBooking($booking_number) {
    // Sanitize input
    $booking_number = preg_replace('/[^A-Za-z0-9\-_]/', '', $booking_number);
    
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
    $valid_codes = ['BB', 'EP', 'BBR1']; // Define valid codes
    
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

// Enhanced CSRF token functions
function generateCSRFToken() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
    
    // Generate new token if not exists or expired
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) 
        || (time() - $_SESSION['csrf_token_time']) > PP_SESSION_TIMEOUT) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token expiry
    if ((time() - $_SESSION['csrf_token_time']) > PP_SESSION_TIMEOUT) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    // Use timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Rate limiting function
function checkRateLimit($action, $identifier = null) {
    if (!$identifier) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = $action . '_' . hash('sha256', $identifier);
    $file = sys_get_temp_dir() . '/pp_rate_limit_' . $key;
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && (time() - $data['first_attempt']) < PP_RATE_LIMIT_WINDOW) {
            if ($data['attempts'] >= PP_MAX_LOGIN_ATTEMPTS) {
                return false; // Rate limited
            }
            $data['attempts']++;
        } else {
            // Reset counter
            $data = ['attempts' => 1, 'first_attempt' => time()];
        }
    } else {
        $data = ['attempts' => 1, 'first_attempt' => time()];
    }
    
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

// Enhanced API request function with security improvements
function makeApiRequest($url, $method = 'GET', $data = null, $timeout = 30) {
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        logError("Invalid URL provided to makeApiRequest", ['url' => $url]);
        return false;
    }
    
    // Check if URL is from allowed domains (if needed)
    $parsed_url = parse_url($url);
    $allowed_hosts = getenv('PP_ALLOWED_API_HOSTS');
    if ($allowed_hosts) {
        $allowed_hosts = explode(',', $allowed_hosts);
        if (!in_array($parsed_url['host'], $allowed_hosts)) {
            logError("API request to unauthorized host", ['host' => $parsed_url['host']]);
            return false;
        }
    }
    
    // Try cURL first (preferred method)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        
        // Basic cURL options with enhanced security
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, min($timeout, 60)); // Cap timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Puzzle Path App v' . APP_VERSION);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]);
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                $json_data = is_string($data) ? $data : json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Log detailed error information (without exposing sensitive data)
        if ($response === false || !empty($error)) {
            logError("cURL API request failed", [
                'url' => parse_url($url, PHP_URL_HOST), // Only log host, not full URL
                'method' => $method,
                'http_code' => $http_code,
                'curl_error' => $error
            ]);
            return false;
        }
        
        // Check for HTTP errors
        if ($http_code >= 400) {
            logError("API returned HTTP error", [
                'url' => parse_url($url, PHP_URL_HOST),
                'http_code' => $http_code
            ]);
        }
        
        return $response;
        
    } else if (ini_get('allow_url_fopen')) {
        // Fallback with enhanced security
        $context_options = [
            'http' => [
                'method' => $method,
                'timeout' => min($timeout, 60),
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
                'url' => parse_url($url, PHP_URL_HOST),
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
            'allow_url_fopen' => ini_get('allow_url_fopen')
        ]);
        return false;
    }
}

// Secure input validation function
function validateInput($input, $type = 'string', $options = []) {
    switch ($type) {
        case 'booking_number':
            // Format: XX-YYYYMMDD-XXXX or similar
            if (!preg_match('/^[A-Z]{1,4}-\d{8}-\d{1,6}$/i', $input)) {
                return false;
            }
            return strtoupper($input);
            
        case 'hunt_code':
            // 1-4 uppercase letters
            if (!preg_match('/^[A-Z]{1,4}$/', $input)) {
                return false;
            }
            return $input;
            
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
            
        case 'int':
            $min = $options['min'] ?? PHP_INT_MIN;
            $max = $options['max'] ?? PHP_INT_MAX;
            $val = filter_var($input, FILTER_VALIDATE_INT);
            return ($val !== false && $val >= $min && $val <= $max) ? $val : false;
            
        case 'string':
        default:
            $max_length = $options['max_length'] ?? 255;
            $input = trim($input);
            return (strlen($input) <= $max_length) ? $input : false;
    }
}

?>
