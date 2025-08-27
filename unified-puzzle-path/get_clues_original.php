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
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Rate limiting check
    if (!checkRateLimit('get_clues')) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please wait before trying again.'
        ]);
        exit;
    }
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Validate and sanitize hunt_id
    $hunt_id = validateInput($_GET['hunt_id'] ?? null, 'int', ['min' => 1, 'max' => 999999]);
    
    if (!$hunt_id) {
        throw new Exception('Valid hunt ID is required');
    }
    
    $mainDb = getMainDb();
    
    // Verify hunt exists with enhanced error handling
    $huntStmt = $mainDb->prepare("SELECT hunt_name, title FROM wp2s_pp_events WHERE id = ? LIMIT 1");
    
    if (!$huntStmt) {
        logError('Hunt query prepare failed', ['error' => $mainDb->error]);
        throw new Exception('Database error occurred');
    }
    
    $huntStmt->bind_param("i", $hunt_id);
    
    if (!$huntStmt->execute()) {
        logError('Hunt query execute failed', ['error' => $huntStmt->error]);
        throw new Exception('Hunt query failed');
    }
    
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        logError('Hunt not found for clue retrieval', ['hunt_id' => $hunt_id]);
        throw new Exception('Hunt not found');
    }
    
    $hunt = $huntResult->fetch_assoc();
    
    // Check if clues table exists (secured check)
    $tableCheck = $mainDb->prepare("SHOW TABLES LIKE ?");
    $tableName = 'wp2s_pp_clues';
    $tableCheck->bind_param("s", $tableName);
    $tableCheck->execute();
    $tableResult = $tableCheck->get_result();
    
    if ($tableResult->num_rows === 0) {
        logError('Clues table missing', ['table' => $tableName]);
        throw new Exception('Clues system not configured. Please contact support.');
    }
    $tableCheck->close();
    
    // Fetch clues with enhanced security and validation
    $cluesStmt = $mainDb->prepare("
        SELECT id, clue_order, title, clue_text, task_description, hint_text 
        FROM wp2s_pp_clues 
        WHERE hunt_id = ? 
        ORDER BY clue_order ASC
        LIMIT 20
    ");
    
    if (!$cluesStmt) {
        logError('Clues query prepare failed', ['error' => $mainDb->error]);
        throw new Exception('Database error occurred');
    }
    
    $cluesStmt->bind_param("i", $hunt_id);
    
    if (!$cluesStmt->execute()) {
        logError('Clues query execute failed', ['error' => $cluesStmt->error]);
        throw new Exception('Clues query failed');
    }
    
    $cluesResult = $cluesStmt->get_result();
    
    $clues = [];
    while ($row = $cluesResult->fetch_assoc()) {
        // Validate and sanitize all clue data
        $clues[] = [
            'id' => (int)$row['id'],
            'order' => (int)$row['clue_order'],
            'title' => h($row['title'] ?? ''),
            'clue' => h($row['clue_text'] ?? ''),
            'task' => h($row['task_description'] ?? ''),
            'hint' => h($row['hint_text'] ?? '')
        ];
    }
    
    // Log successful clue retrieval
    logError('Clues retrieved successfully', [
        'hunt_id' => $hunt_id,
        'clue_count' => count($clues)
    ], 'INFO');
    
    $response = [
        'success' => true,
        'hunt_name' => h($hunt['hunt_name'] ?: $hunt['title']),
        'total_clues' => count($clues),
        'clues' => $clues
    ];
    
    // Add debug info only in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $response['debug_info'] = [
            'hunt_id' => $hunt_id,
            'clues_found' => count($clues),
            'hunt_title' => h($hunt['title'])
        ];
    }
    
    echo json_encode($response);
    
    $huntStmt->close();
    $cluesStmt->close();
    $mainDb->close();
    
} catch (Exception $e) {
    // Log error securely
    logError("Get clues error", [
        'error' => $e->getMessage(),
        'hunt_id' => $hunt_id ?? 'N/A',
        'file' => basename(__FILE__)
    ]);
    
    // Return user-friendly error message
    $message = $e->getMessage();
    
    // Don't expose internal errors in production
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        if (strpos($message, 'Database') !== false) {
            $message = 'Unable to load hunt content. Please try again later.';
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
}
?>
