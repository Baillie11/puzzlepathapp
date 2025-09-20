<?php
// Clues endpoint - loads clues from database based on hunt_id
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Get and validate hunt_id parameter
    $hunt_id = $_GET['hunt_id'] ?? null;
    
    if (!$hunt_id || !is_numeric($hunt_id)) {
        throw new Exception('Valid hunt_id parameter is required');
    }
    
    $hunt_id = (int)$hunt_id;
    
    // Database connection
    $db = getMainDb();
    
    // Get hunt information
    $huntStmt = $db->prepare("SELECT id, title, hunt_name FROM wp2s_pp_events WHERE id = ? LIMIT 1");
    $huntStmt->bind_param("i", $hunt_id);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception('Hunt not found');
    }
    
    $hunt = $huntResult->fetch_assoc();
    $huntStmt->close();
    
    // Get clues for this hunt with input type information
    $clueStmt = $db->prepare("
        SELECT 
            id,
            clue_order,
            title,
            clue_text as clue,
            task_description as task,
            hint_text as hint,
            answer,
            latitude,
            longitude,
            geofence_radius,
            input_type,
            required_answer,
            answer_options,
            is_case_sensitive,
            validation_type,
            min_value,
            max_value,
            photo_required,
            auto_advance
        FROM wp2s_pp_clues 
        WHERE hunt_id = ? AND is_active = 1 
        ORDER BY clue_order ASC
    ");
    
    $clueStmt->bind_param("i", $hunt_id);
    $clueStmt->execute();
    $clueResult = $clueStmt->get_result();
    
    $clues = [];
    while ($row = $clueResult->fetch_assoc()) {
        $clues[] = [
            'id' => (int)$row['id'],
            'order' => (int)$row['clue_order'],
            'title' => $row['title'],
            'clue' => $row['clue'],
            'task' => $row['task'],
            'hint' => $row['hint'],
            'answer' => $row['answer'],
            'latitude' => $row['latitude'] ? (float)$row['latitude'] : null,
            'longitude' => $row['longitude'] ? (float)$row['longitude'] : null,
            'geofence_radius' => $row['geofence_radius'] ? (int)$row['geofence_radius'] : null,
            'input_type' => $row['input_type'] ?? 'none',
            'required_answer' => $row['required_answer'],
            'answer_options' => $row['answer_options'] ? json_decode($row['answer_options'], true) : null,
            'is_case_sensitive' => (bool)($row['is_case_sensitive'] ?? false),
            'validation_type' => $row['validation_type'] ?? 'exact',
            'min_value' => $row['min_value'] ? (float)$row['min_value'] : null,
            'max_value' => $row['max_value'] ? (float)$row['max_value'] : null,
            'photo_required' => (bool)($row['photo_required'] ?? false),
            'auto_advance' => (bool)($row['auto_advance'] ?? false)
        ];
    }
    
    $clueStmt->close();
    $db->close();
    
    // Return clues
    echo json_encode([
        'success' => true,
        'message' => 'Clues loaded successfully',
        'hunt_id' => $hunt_id,
        'hunt_name' => $hunt['title'],
        'total_clues' => count($clues),
        'clues' => $clues
    ]);
    
} catch (Exception $e) {
    // Log error securely
    logError("Clue loading error", [
        'error' => $e->getMessage(),
        'hunt_id' => isset($hunt_id) ? $hunt_id : 'N/A',
        'file' => basename(__FILE__)
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'clues' => []
    ]);
}
?>
