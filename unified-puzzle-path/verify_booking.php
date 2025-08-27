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
    if (!checkRateLimit('booking_verify')) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many attempts. Please wait before trying again.'
        ]);
        exit;
    }
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }
    
    if (!isset($input['booking_number']) || empty($input['booking_number'])) {
        throw new Exception('Booking number is required');
    }
    
    // Validate and sanitize booking number
    $booking_number = validateInput($input['booking_number'], 'booking_number');
    if (!$booking_number) {
        throw new Exception('Invalid booking number format. Expected format: XX-YYYYMMDD-XXXX');
    }
    
    // Extract hunt code from booking number
    $hunt_code = extractHuntCodeFromBooking($booking_number);
    
    if (!$hunt_code) {
        throw new Exception('Invalid booking number format. Please check your booking number.');
    }
    
    // Database query with enhanced error handling
    $mainDb = getMainDb();
    
    // Prepare statement with additional booking details
    $stmt = $mainDb->prepare("
        SELECT 
            booking_code, 
            payment_status, 
            participant_names, 
            tickets as participant_count,
            customer_name,
            created_at
        FROM wp_pp_bookings
        WHERE booking_code = ? AND payment_status IN ('paid', 'succeeded', 'confirmed', 'complete', 'completed')
        LIMIT 1
    ");
    
    if (!$stmt) {
        logError('Database prepare failed', ['error' => $mainDb->error]);
        throw new Exception('Database error occurred');
    }
    
    $stmt->bind_param("s", $booking_number);
    
    if (!$stmt->execute()) {
        logError('Database execute failed', ['error' => $stmt->error]);
        throw new Exception('Database query failed');
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log failed booking verification attempt
        logError('Booking verification failed', [
            'booking_number' => $booking_number,
            'reason' => 'not_found_or_unpaid'
        ]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or payment not confirmed. Please check your booking number and try again.'
        ]);
        $stmt->close();
        exit;
    }
    
    $booking_db = $result->fetch_assoc();
    $stmt->close();
    
    // Since we already filtered by payment status in the query, 
    // this booking is valid
    
    // Create booking object with actual data
    $booking = [
        'booking_code' => h($booking_db['booking_code']),
        'payment_status' => h($booking_db['payment_status']),
        'participant_names' => h($booking_db['participant_names'] ?? ''),
        'participant_count' => (int)($booking_db['participant_count'] ?? 1),
        'customer_name' => h($booking_db['customer_name'] ?? '')
    ];
    
    // Get hunt information with enhanced security
    $db_hunt_code = mapBookingCodeToHuntCode($hunt_code);
    
    if (!$db_hunt_code) {
        throw new Exception('Unknown hunt code: ' . $hunt_code);
    }
    
    $huntStmt = $mainDb->prepare("
        SELECT id, title, location, hunt_code, hunt_name 
        FROM wp_pp_events
        WHERE hunt_code = ? AND (created_at IS NULL OR created_at <= NOW())
        LIMIT 1
    ");
    
    if (!$huntStmt) {
        logError('Hunt query prepare failed', ['error' => $mainDb->error]);
        throw new Exception('Database error occurred');
    }
    
    $huntStmt->bind_param("s", $db_hunt_code);
    
    if (!$huntStmt->execute()) {
        logError('Hunt query execute failed', ['error' => $huntStmt->error]);
        throw new Exception('Hunt query failed');
    }
    
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        logError('Hunt not found', [
            'hunt_code' => $hunt_code,
            'db_hunt_code' => $db_hunt_code
        ]);
        throw new Exception('Hunt not available. Please contact support.');
    }
    
    $hunt = $huntResult->fetch_assoc();
    $hunt_id = $hunt['id'];
    
    // Success - booking is valid and paid
    $response = [
        'success' => true,
        'message' => 'Booking verified successfully! Get ready for your ' . h($hunt['hunt_name']) . ' adventure!',
        'booking_data' => [
            'booking_code' => $booking['booking_code'],
            'participant_names' => $booking['participant_names'],
            'participant_count' => $booking['participant_count'],
            'customer_name' => $booking['customer_name']
        ],
        'hunt_data' => [
            'hunt_id' => (int)$hunt_id,
            'hunt_code' => h($hunt_code),
            'hunt_name' => h($hunt['hunt_name']),
            'location' => h($hunt['location']),
            'description' => h($hunt['title']),
            'instructions' => 'Start your quest adventure!',
            'total_clues' => 6, // TODO: Store in database
            'estimated_duration' => 90 // TODO: Store in database
        ]
    ];
    
    // Log successful verification
    logError('Booking verified successfully', [
        'booking_code' => $booking_number,
        'hunt_code' => $hunt_code
    ], 'INFO');
    
    echo json_encode($response);
    
    $huntStmt->close();
    $mainDb->close();
    
} catch (Exception $e) {
    // Log error securely (without exposing internal details)
    logError("Booking verification error", [
        'error' => $e->getMessage(),
        'booking_number' => isset($booking_number) ? substr($booking_number, 0, 5) . '...' : 'N/A',
        'file' => basename(__FILE__)
    ]);
    
    // Return user-friendly error message
    $message = $e->getMessage();
    
    // Don't expose internal errors in production
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        if (strpos($message, 'Database') !== false) {
            $message = 'Service temporarily unavailable. Please try again later.';
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
}
?>
