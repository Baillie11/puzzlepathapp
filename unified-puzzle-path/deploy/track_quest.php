<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Action is required');
    }
    
    $action = $input['action'];
    $timestamp = $input['timestamp'] ?? time() * 1000;
    $userAgent = $input['userAgent'] ?? '';
    
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
    logError("Quest tracking error", [
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown',
        'input' => $input ?? []
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleQuestStart($db, $data) {
    $huntId = $data['huntId'] ?? null;
    $bookingCode = $data['bookingCode'] ?? null;
    $startTime = $data['startTime'] ?? null;
    
    if (!$huntId || !$bookingCode || !$startTime) {
        throw new Exception('Missing required fields for quest start');
    }
    
    // For anonymous tracking, we'll create a temporary tracking entry
    // In a real implementation, you'd link this to a user account
    $stmt = $db->prepare("
        INSERT INTO pp_quest_tracking (
            hunt_id, booking_code, start_time, user_agent, ip_address, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    // Create tracking table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS pp_quest_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hunt_id INT NOT NULL,
            booking_code VARCHAR(50),
            start_time TIMESTAMP NULL,
            end_time TIMESTAMP NULL,
            total_time_ms BIGINT NULL,
            user_agent TEXT,
            ip_address VARCHAR(45),
            completion_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_booking (booking_code),
            INDEX idx_hunt_completion (hunt_id, end_time)
        )
    ";
    
    $db->query($createTableQuery);
    
    $userAgent = $data['userAgent'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO pp_quest_tracking (
            hunt_id, booking_code, start_time, user_agent, ip_address
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issss", $huntId, $bookingCode, $startTime, $userAgent, $ipAddress);
    $stmt->execute();
    
    // Store the tracking ID in session for later use
    session_start();
    $_SESSION['quest_tracking_id'] = $db->insert_id;
    
    $stmt->close();
}

function handleClueCompletion($db, $data) {
    $clueId = $data['clueId'] ?? null;
    $clueIndex = $data['clueIndex'] ?? null;
    $completedAt = $data['completedAt'] ?? null;
    
    if ($clueId === null || $clueIndex === null || !$completedAt) {
        throw new Exception('Missing required fields for clue completion');
    }
    
    session_start();
    $trackingId = $_SESSION['quest_tracking_id'] ?? null;
    
    if (!$trackingId) {
        // Create anonymous clue tracking table if needed
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS pp_clue_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tracking_id INT,
                clue_id INT,
                clue_index INT,
                completed_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_tracking (tracking_id)
            )
        ";
        $db->query($createTableQuery);
        return; // Skip tracking if no tracking ID
    }
    
    $stmt = $db->prepare("
        INSERT INTO pp_clue_tracking (tracking_id, clue_id, clue_index, completed_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at)
    ");
    
    $stmt->bind_param("iiis", $trackingId, $clueId, $clueIndex, $completedAt);
    $stmt->execute();
    $stmt->close();
}

function handleQuestFinish($db, $data) {
    $huntId = $data['huntId'] ?? null;
    $bookingCode = $data['bookingCode'] ?? null;
    $startTime = $data['startTime'] ?? null;
    $endTime = $data['endTime'] ?? null;
    $totalTime = $data['totalTime'] ?? null;
    
    if (!$huntId || !$bookingCode || !$startTime || !$endTime || !$totalTime) {
        throw new Exception('Missing required fields for quest completion');
    }
    
    session_start();
    $trackingId = $_SESSION['quest_tracking_id'] ?? null;
    
    if ($trackingId) {
        // Update existing tracking record
        $stmt = $db->prepare("
            UPDATE pp_quest_tracking 
            SET end_time = ?, total_time_ms = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $endTime, $totalTime, $trackingId);
        $stmt->execute();
        $stmt->close();
    } else {
        // Create new completion record for anonymous completion
        $stmt = $db->prepare("
            INSERT INTO pp_quest_tracking (
                hunt_id, booking_code, start_time, end_time, total_time_ms, 
                user_agent, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userAgent = $data['userAgent'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt->bind_param("isssiis", $huntId, $bookingCode, $startTime, $endTime, 
                         $totalTime, $userAgent, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
    
    // Clear the tracking ID from session
    unset($_SESSION['quest_tracking_id']);
}
?>
