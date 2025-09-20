<?php
// Submit Answer endpoint - handles user answer submissions for clues
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Security: Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Rate limiting check
    if (!checkRateLimit('submit_answer')) {
        http_response_code(429);
        throw new Exception('Too many attempts. Please wait before trying again.');
    }
    
    // Start session for CSRF protection
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
    
    // Get request data based on content type
    $input = [];
    $files = $_FILES;
    
    // Check if this is a multipart/form-data request (file upload)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // File upload request - get data from $_POST
        $input = $_POST;
    } else {
        // JSON request - get from body
        $jsonInput = file_get_contents('php://input');
        if (!$jsonInput) {
            throw new Exception('No input data provided');
        }
        
        $input = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
    }
    
    // Validate required fields
    $booking_code = trim($input['booking_code'] ?? '');
    $clue_id = $input['clue_id'] ?? null;
    $answer_text = trim($input['answer_text'] ?? '');
    $answer_numeric = $input['answer_numeric'] ?? null;
    $csrf_token = $input['csrf_token'] ?? '';
    
    if (empty($booking_code) || !$clue_id || !is_numeric($clue_id)) {
        throw new Exception('Missing required fields: booking_code and clue_id');
    }
    
    // CSRF protection - TEMPORARILY DISABLED FOR TESTING
    // if (!validateCSRFToken($csrf_token)) {
    //     throw new Exception('Invalid or expired security token. Please refresh the page and try again.');
    // }
    
    $clue_id = (int)$clue_id;
    
    // Validate booking code format
    $booking_code = validateInput($booking_code, 'booking_number');
    if ($booking_code === false) {
        throw new Exception('Invalid booking code format');
    }
    
    // Database connection
    $db = getMainDb();
    
    // Get clue information with input type details
    $clueStmt = $db->prepare("
        SELECT 
            c.id, c.hunt_id, c.clue_order, c.title,
            c.input_type, c.required_answer, c.answer_options, 
            c.is_case_sensitive, c.validation_type, 
            c.min_value, c.max_value, c.photo_required, c.auto_advance,
            h.title as hunt_title
        FROM wp2s_pp_clues c
        JOIN wp2s_pp_events h ON c.hunt_id = h.id
        WHERE c.id = ? AND c.is_active = 1 
        LIMIT 1
    ");
    $clueStmt->bind_param("i", $clue_id);
    $clueStmt->execute();
    $clueResult = $clueStmt->get_result();
    
    if ($clueResult->num_rows === 0) {
        throw new Exception('Clue not found or inactive');
    }
    
    $clue = $clueResult->fetch_assoc();
    $clueStmt->close();
    
    // Verify booking exists (simplified check)
    $bookingStmt = $db->prepare("
        SELECT booking_code, customer_name, payment_status 
        FROM wp2s_pp_bookings 
        WHERE booking_code = ? AND payment_status IN ('paid', 'succeeded', 'confirmed', 'complete', 'completed')
        LIMIT 1
    ");
    $bookingStmt->bind_param("s", $booking_code);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    
    if ($bookingResult->num_rows === 0) {
        throw new Exception('Invalid booking code or payment not confirmed');
    }
    
    $booking = $bookingResult->fetch_assoc();
    $bookingStmt->close();
    
    // Check if user has already submitted a correct answer for this clue
    $existingStmt = $db->prepare("
        SELECT id, is_correct, attempt_count 
        FROM wp2s_pp_user_answers 
        WHERE booking_code = ? AND clue_id = ?
        ORDER BY submitted_at DESC 
        LIMIT 1
    ");
    $existingStmt->bind_param("si", $booking_code, $clue_id);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    
    $previousAnswer = $existingResult->fetch_assoc();
    $existingStmt->close();
    
    if ($previousAnswer && $previousAnswer['is_correct']) {
        // Already answered correctly
        echo json_encode([
            'success' => true,
            'message' => 'You have already answered this clue correctly!',
            'is_correct' => true,
            'already_completed' => true,
            'can_advance' => true
        ]);
        exit();
    }
    
    $attempt_count = $previousAnswer ? $previousAnswer['attempt_count'] + 1 : 1;
    
    // Limit attempts per clue
    if ($attempt_count > 10) {
        throw new Exception('Maximum attempts exceeded for this clue');
    }
    
    // Initialize validation result
    $is_correct = false;
    $validation_message = '';
    $photo_filename = null;
    
    // Handle file upload if present
    if (!empty($files['photo']['tmp_name'])) {
        $uploadResult = handlePhotoUpload($files['photo'], $booking_code, $clue_id);
        if (!$uploadResult['success']) {
            throw new Exception($uploadResult['message']);
        }
        $photo_filename = $uploadResult['filename'];
    }
    
    // Validate answer based on input type
    switch ($clue['input_type']) {
        case 'none':
            // No input required - just advance
            $is_correct = true;
            $validation_message = 'Clue completed!';
            break;
            
        case 'photo':
            if ($clue['photo_required'] && !$photo_filename) {
                throw new Exception('Photo upload is required for this clue');
            }
            // For photo uploads, consider it correct if photo was uploaded
            $is_correct = !empty($photo_filename);
            $validation_message = $is_correct ? 'Photo uploaded successfully!' : 'Please upload a photo';
            break;
            
        case 'text':
            if (empty($answer_text)) {
                throw new Exception('Text answer is required');
            }
            $is_correct = validateTextAnswer($answer_text, $clue);
            break;
            
        case 'number':
            if ($answer_numeric === null || $answer_numeric === '') {
                throw new Exception('Numeric answer is required');
            }
            $answer_numeric = (float)$answer_numeric;
            $is_correct = validateNumericAnswer($answer_numeric, $clue);
            break;
            
        case 'multiple_choice':
            if (empty($answer_text)) {
                throw new Exception('Please select an answer');
            }
            $is_correct = validateMultipleChoiceAnswer($answer_text, $clue);
            break;
            
        default:
            throw new Exception('Unknown input type');
    }
    
    if (!$validation_message) {
        $validation_message = $is_correct ? 'Correct answer!' : 'Incorrect answer. Try again!';
    }
    
    // Store user answer in database
    $answerStmt = $db->prepare("
        INSERT INTO wp2s_pp_user_answers 
        (booking_code, clue_id, answer_text, answer_numeric, photo_filename, is_correct, attempt_count) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $answerStmt->bind_param("sisssii", 
        $booking_code, $clue_id, $answer_text, $answer_numeric, $photo_filename, $is_correct, $attempt_count
    );
    $answerStmt->execute();
    $answerStmt->close();
    
    // If photo was uploaded, store it in photos table
    if ($photo_filename) {
        $photoStmt = $db->prepare("
            INSERT INTO wp2s_pp_photos 
            (booking_code, clue_id, filename, original_filename, file_size, mime_type, upload_ip) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $original_filename = $files['photo']['name'] ?? 'unknown';
        $file_size = $files['photo']['size'] ?? 0;
        $mime_type = $files['photo']['type'] ?? 'unknown';
        $upload_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $photoStmt->bind_param("sissiis", 
            $booking_code, $clue_id, $photo_filename, $original_filename, $file_size, $mime_type, $upload_ip
        );
        $photoStmt->execute();
        $photoStmt->close();
    }
    
    $db->close();
    
    // Return response
    echo json_encode([
        'success' => true,
        'message' => $validation_message,
        'is_correct' => $is_correct,
        'can_advance' => $is_correct,
        'auto_advance' => $is_correct && (bool)$clue['auto_advance'],
        'attempt_count' => $attempt_count,
        'clue_title' => $clue['title'],
        'photo_uploaded' => !empty($photo_filename)
    ]);
    
} catch (Exception $e) {
    // Log error securely
    logError("Submit answer error", [
        'error' => $e->getMessage(),
        'booking_code' => isset($booking_code) ? substr($booking_code, 0, 5) . '***' : 'N/A',
        'clue_id' => isset($clue_id) ? $clue_id : 'N/A',
        'file' => basename(__FILE__)
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'is_correct' => false,
        'can_advance' => false
    ]);
}

// Helper function to validate text answers
function validateTextAnswer($answer, $clue) {
    $required_answer = $clue['required_answer'];
    if (empty($required_answer)) {
        return true; // No specific answer required
    }
    
    $user_answer = $clue['is_case_sensitive'] ? $answer : strtolower($answer);
    $expected_answer = $clue['is_case_sensitive'] ? $required_answer : strtolower($required_answer);
    
    switch ($clue['validation_type']) {
        case 'exact':
            return $user_answer === $expected_answer;
            
        case 'contains':
            return strpos($user_answer, $expected_answer) !== false;
            
        case 'regex':
            return preg_match('/' . $expected_answer . '/i', $answer);
            
        default:
            return $user_answer === $expected_answer;
    }
}

// Helper function to validate numeric answers
function validateNumericAnswer($answer, $clue) {
    $required_answer = (float)$clue['required_answer'];
    
    switch ($clue['validation_type']) {
        case 'exact':
            return abs($answer - $required_answer) < 0.001; // Handle float precision
            
        case 'numeric_range':
            $min = $clue['min_value'] !== null ? (float)$clue['min_value'] : PHP_FLOAT_MIN;
            $max = $clue['max_value'] !== null ? (float)$clue['max_value'] : PHP_FLOAT_MAX;
            return $answer >= $min && $answer <= $max;
            
        default:
            return abs($answer - $required_answer) < 0.001;
    }
}

// Helper function to validate multiple choice answers
function validateMultipleChoiceAnswer($answer, $clue) {
    $required_answer = trim($clue['required_answer']);
    $user_answer = trim($answer);
    
    if ($clue['is_case_sensitive']) {
        return $user_answer === $required_answer;
    }
    
    return strtolower($user_answer) === strtolower($required_answer);
}

// Helper function to handle photo uploads
function handlePhotoUpload($file, $booking_code, $clue_id) {
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . getUploadErrorMessage($file['error'])];
    }
    
    // Check file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $file['type'];
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Additional validation using file info
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($detected_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file format detected.'];
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = UPLOAD_PATH . 'quest_photos/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = sprintf(
        '%s_%d_%s.%s',
        $booking_code,
        $clue_id,
        uniqid(),
        strtolower($extension)
    );
    
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.'];
    }
    
    // Set appropriate permissions
    chmod($upload_path, 0644);
    
    return ['success' => true, 'filename' => $filename];
}

// Helper function to get user-friendly upload error messages
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File exceeds maximum upload size.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds form maximum size.';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload stopped by extension.';
        default:
            return 'Unknown upload error.';
    }
}
?>
