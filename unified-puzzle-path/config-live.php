<?php
/**
 * Puzzle Path Unified App - Live Production Configuration
 * 
 * IMPORTANT: Update the database credentials below with your live server details
 * before uploading to your production environment.
 */

// Database configuration for LIVE SERVER
$db_config = [
    'host' => 'YOUR_LIVE_DB_HOST',           // e.g., 'localhost' or 'mysql.yourdomain.com'
    'username' => 'YOUR_LIVE_DB_USERNAME',   // Your live database username
    'password' => 'YOUR_LIVE_DB_PASSWORD',   // Your live database password  
    'database' => 'YOUR_LIVE_DB_NAME',       // Your live database name
    'port' => 3306,                          // Usually 3306 for MySQL
    'charset' => 'utf8mb4'
];

// Application settings
$app_config = [
    'environment' => 'production',
    'debug_mode' => false,                   // Set to false for production
    'log_errors' => true,
    'display_errors' => false,               // Never show errors to users in production
    'timezone' => 'Australia/Queensland',    // Adjust for your timezone
    'session_timeout' => 3600,               // 1 hour in seconds
    'max_quest_duration' => 7200             // 2 hours max quest time
];

// WordPress integration settings
$wordpress_config = [
    'wp_db_prefix' => 'wp_',                 // Your WordPress database table prefix
    'booking_table_prefix' => 'pp_',         // Puzzle Path plugin table prefix
    'booking_view' => 'wp2s_pp_bookings'     // The view that maps WP bookings to unified app format
];

// Security settings
$security_config = [
    'allowed_origins' => [
        'https://yourdomain.com',            // Replace with your actual domain
        'https://www.yourdomain.com'         // Add www version if needed
    ],
    'api_rate_limit' => 100,                 // Requests per minute per IP
    'booking_code_encryption' => true,
    'require_https' => true                  // Enforce HTTPS in production
];

// Error logging configuration
$error_config = [
    'log_file' => '/path/to/logs/puzzle-path-errors.log',  // Update with actual log path
    'max_log_size' => 10485760,              // 10MB max log file size
    'email_errors' => true,
    'admin_email' => 'admin@yourdomain.com'  // Replace with your email
];

// Create PDO connection with error handling
function getDatabaseConnection() {
    global $db_config, $app_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 30,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['charset']}"
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        
        // Set timezone
        $pdo->exec("SET time_zone = '+10:00'");  // Adjust for your timezone
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error securely without exposing credentials
        error_log("Database connection failed: " . $e->getMessage());
        
        if ($app_config['debug_mode']) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        } else {
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
}

// Utility functions for production environment
function isProductionEnvironment() {
    global $app_config;
    return $app_config['environment'] === 'production';
}

function logError($message, $context = []) {
    global $error_config;
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logEntry = "[{$timestamp}] ERROR: {$message} {$contextStr}" . PHP_EOL;
    
    if (file_exists(dirname($error_config['log_file']))) {
        file_put_contents($error_config['log_file'], $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // Also use PHP's error log as fallback
    error_log($message);
}

function validateOrigin($origin) {
    global $security_config;
    
    if (!$origin) {
        return false;
    }
    
    return in_array($origin, $security_config['allowed_origins']);
}

// Set error handling for production
if (isProductionEnvironment()) {
    // Don't display errors to users
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    
    // But log them for debugging
    ini_set('log_errors', 1);
    ini_set('error_log', $error_config['log_file']);
    
    // Set custom error handler
    set_error_handler(function($severity, $message, $file, $line) {
        logError("PHP Error: {$message} in {$file} on line {$line}", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);
    });
}

// CORS headers for API endpoints
function setCorsHeaders() {
    global $security_config;
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (validateOrigin($origin)) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 86400"); // 24 hours
}

// Initialize session with security settings
function initializeSession() {
    global $app_config;
    
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isProductionEnvironment() ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', $app_config['session_timeout']);
        
        session_start();
    }
}

// Check if HTTPS is required and redirect if needed
if (isProductionEnvironment() && $security_config['require_https'] && !isset($_SERVER['HTTPS'])) {
    $redirectURL = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL", true, 301);
    exit();
}

?>
