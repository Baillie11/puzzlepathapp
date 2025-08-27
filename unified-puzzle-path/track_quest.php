<?php
// Security: Define access constant before loading config
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Access-Control-Allow-Origin: *'); // TODO: Restrict in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Rate limiting check
    if (!checkRateLimit('track_quest')) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many tracking requests. Please wait before trying again.'
        ]);
        exit;
    }
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Parse and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    // Validate action
    $action = validateInput($input['action'], 'string', ['max_length' => 50]);
    if (!$action || !in_array($action, ['start', 'clue_completed', 'finish'])) {
        throw new Exception('Invalid action');
    }
    
    $timestamp = validateInput($input['timestamp'] ?? time() * 1000, 'int', ['min' => 0]);
    $userAgent = substr(validateInput($input['userAgent'] ?? '', 'string') ?: '', 0, 500);
    
    // Get database connection
    $mainDb = getMainDb();
    
    switch ($action) {
        case 'start':
            handleQuestStart($mainDb, $input);
            break;
            
        case 'clue_completed':
            handleClueCompletion($mainDb, $input);
            break;
            
        case 'finish':
            handleQuestFinish($mainDb, $input);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Log error securely (without exposing input data)
    logError("Quest tracking error", [
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown',
        'file' => basename(__FILE__)
    ]);
    
    // Return user-friendly error message
    $message = $e->getMessage();
    
    // Don't expose internal errors in production
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        if (strpos($message, 'Database') !== false) {
            $message = 'Unable to save quest progress. Please try again.';
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
}

function handleQuestStart($db, $data) {
    // Validate and sanitize all inputs
    $huntId = validateInput($data['huntId'] ?? null, 'int', ['min' => 1, 'max' => 999999]);
    $bookingCode = validateInput($data['bookingCode'] ?? null, 'booking_number');
    $startTime = validateInput($data['startTime'] ?? null, 'string', ['max_length' => 50]);
    
    if (!$huntId || !$bookingCode || !$startTime) {
        throw new Exception('Invalid or missing required fields for quest start');
    }
    
    // Validate startTime format (should be ISO datetime or timestamp)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}|^\d{10,13}$/', $startTime)) {
        throw new Exception('Invalid start time format');
    }
    
    // Create tracking table if it doesn't exist (with enhanced security)
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS pp_quest_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hunt_id INT NOT NULL,
            booking_code VARCHAR(50) NOT NULL,
            start_time TIMESTAMP NULL,
            end_time TIMESTAMP NULL,
            total_time_ms BIGINT NULL,
            user_agent TEXT,
            ip_address VARCHAR(45),
            completion_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_booking (booking_code),
            INDEX idx_hunt_completion (hunt_id, end_time),
            INDEX idx_start_time (start_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!$db->query($createTableQuery)) {
        logError('Failed to create tracking table', ['error' => $db->error]);
        throw new Exception('Database initialization failed');
    }
    
    // Sanitize additional fields
    $userAgent = substr(validateInput($data['userAgent'] ?? '', 'string') ?: '', 0, 1000);
    $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) ?: 'unknown';
    
    // Check for duplicate starts (prevent spam)
    $checkStmt = $db->prepare("
        SELECT id FROM pp_quest_tracking 
        WHERE booking_code = ? AND hunt_id = ? 
        AND start_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        LIMIT 1
    ");
    
    if (!$checkStmt) {
        logError('Check query prepare failed', ['error' => $db->error]);
        throw new Exception('Database error occurred');
    }
    
    $checkStmt->bind_param("si", $bookingCode, $huntId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        logError('Duplicate quest start attempt', [
            'booking_code' => substr($bookingCode, 0, 5) . '...',
            'hunt_id' => $huntId
        ]);
        throw new Exception('Quest already started recently');
    }
    $checkStmt->close();
    
    // Insert tracking record
    $stmt = $db->prepare("
        INSERT INTO pp_quest_tracking (
            hunt_id, booking_code, start_time, user_agent, ip_address
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        logError('Insert tracking prepare failed', ['error' => $db->error]);
        throw new Exception('Database error occurred');
    }
    
    $stmt->bind_param("issss", $huntId, $bookingCode, $startTime, $userAgent, $ipAddress);
    
    if (!$stmt->execute()) {
        logError('Insert tracking execute failed', ['error' => $stmt->error]);
        throw new Exception('Failed to save quest start');
    }
    
    // Store the tracking ID in secure session
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
    
    $_SESSION['quest_tracking_id'] = $db->insert_id;
    $_SESSION['quest_start_time'] = time();
    
    $stmt->close();
    
    logError('Quest started successfully', [
        'hunt_id' => $huntId,
        'tracking_id' => $db->insert_id
    ], 'INFO');
}

function handleClueCompletion($db, $data) {
    // Validate and sanitize inputs
    $clueId = validateInput($data['clueId'] ?? null, 'int', ['min' => 1, 'max' => 999999]);
    $clueIndex = validateInput($data['clueIndex'] ?? null, 'int', ['min' => 0, 'max' => 50]);
    $completedAt = validateInput($data['completedAt'] ?? null, 'string', ['max_length' => 50]);
    
    if (!$clueId || $clueIndex === false || !$completedAt) {
        throw new Exception('Invalid or missing required fields for clue completion');
    }
    
    // Validate completedAt format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}|^\d{10,13}$/', $completedAt)) {
        throw new Exception('Invalid completion time format');
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $trackingId = validateInput($_SESSION['quest_tracking_id'] ?? null, 'int', ['min' => 1]);
    
    if (!$trackingId) {
        logError('No tracking session for clue completion', [
            'clue_id' => $clueId,
            'clue_index' => $clueIndex
        ]);
        return; // Skip tracking if no valid tracking ID
    }
    
    // Validate session age (prevent stale sessions)
    $sessionStartTime = $_SESSION['quest_start_time'] ?? 0;
    if ((time() - $sessionStartTime) > MAX_QUEST_TIME) {
        logError('Quest session expired', ['session_age' => time() - $sessionStartTime]);
        throw new Exception('Quest session expired. Please start a new quest.');
    }
    
    // Create clue tracking table if needed
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS pp_clue_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tracking_id INT NOT NULL,
            clue_id INT NOT NULL,
            clue_index INT NOT NULL,
            completed_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tracking_clue (tracking_id, clue_id),
            INDEX idx_tracking (tracking_id),
            INDEX idx_completed (completed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!$db->query($createTableQuery)) {
        logError('Failed to create clue tracking table', ['error' => $db->error]);
        throw new Exception('Database initialization failed');
    }
    
    $stmt = $db->prepare("
        INSERT INTO pp_clue_tracking (tracking_id, clue_id, clue_index, completed_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            completed_at = VALUES(completed_at),
            clue_index = VALUES(clue_index)
    ");
    
    if (!$stmt) {
        logError('Clue tracking prepare failed', ['error' => $db->error]);
        throw new Exception('Database error occurred');
    }
    
    $stmt->bind_param("iiis", $trackingId, $clueId, $clueIndex, $completedAt);
    
    if (!$stmt->execute()) {
        logError('Clue tracking execute failed', ['error' => $stmt->error]);
        throw new Exception('Failed to save clue completion');
    }
    
    $stmt->close();
    
    logError('Clue completed', [
        'tracking_id' => $trackingId,
        'clue_id' => $clueId,
        'clue_index' => $clueIndex
    ], 'INFO');
}

function handleQuestFinish($db, $data) {
    // Validate and sanitize all inputs
    $huntId = validateInput($data['huntId'] ?? null, 'int', ['min' => 1, 'max' => 999999]);
    $bookingCode = validateInput($data['bookingCode'] ?? null, 'booking_number');
    $startTime = validateInput($data['startTime'] ?? null, 'string', ['max_length' => 50]);
    $endTime = validateInput($data['endTime'] ?? null, 'string', ['max_length' => 50]);
    $totalTime = validateInput($data['totalTime'] ?? null, 'int', ['min' => 1, 'max' => MAX_QUEST_TIME * 1000]);
    
    if (!$huntId || !$bookingCode || !$startTime || !$endTime || !$totalTime) {
        throw new Exception('Invalid or missing required fields for quest completion');
    }
    
    // Validate time formats
    if (!preg_match('/^\d{4}-\d{2}-\d{2}|^\d{10,13}$/', $startTime) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}|^\d{10,13}$/', $endTime)) {
        throw new Exception('Invalid time format');
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $trackingId = validateInput($_SESSION['quest_tracking_id'] ?? null, 'int', ['min' => 1]);
    
    if ($trackingId) {
        // Update existing tracking record with validation
        $stmt = $db->prepare("
            UPDATE pp_quest_tracking 
            SET end_time = ?, total_time_ms = ?, updated_at = NOW()
            WHERE id = ? AND booking_code = ? AND hunt_id = ?
            LIMIT 1
        ");
        
        if (!$stmt) {
            logError('Quest finish update prepare failed', ['error' => $db->error]);
            throw new Exception('Database error occurred');
        }
        
        $stmt->bind_param("siisi", $endTime, $totalTime, $trackingId, $bookingCode, $huntId);
        
        if (!$stmt->execute()) {
            logError('Quest finish update execute failed', ['error' => $stmt->error]);
            throw new Exception('Failed to save quest completion');
        }
        
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affectedRows === 0) {
            logError('No tracking record updated', [
                'tracking_id' => $trackingId,
                'booking_code' => substr($bookingCode, 0, 5) . '...'
            ]);
            throw new Exception('Invalid quest session');
        }
        
    } else {
        // Create new completion record for anonymous completion
        $userAgent = substr(validateInput($data['userAgent'] ?? '', 'string') ?: '', 0, 1000);
        $ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) ?: 'unknown';
        
        $stmt = $db->prepare("
            INSERT INTO pp_quest_tracking (
                hunt_id, booking_code, start_time, end_time, total_time_ms, 
                user_agent, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            logError('Quest finish insert prepare failed', ['error' => $db->error]);
            throw new Exception('Database error occurred');
        }
        
        $stmt->bind_param("isssiss", $huntId, $bookingCode, $startTime, $endTime, 
                         $totalTime, $userAgent, $ipAddress);
        
        if (!$stmt->execute()) {
            logError('Quest finish insert execute failed', ['error' => $stmt->error]);
            throw new Exception('Failed to save quest completion');
        }
        
        $stmt->close();
    }
    
    // Clear the tracking ID from session
    unset($_SESSION['quest_tracking_id'], $_SESSION['quest_start_time']);
    
    logError('Quest completed successfully', [
        'hunt_id' => $huntId,
        'total_time_ms' => $totalTime
    ], 'INFO');
}
?>
