<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Get the booking number from POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['booking_number']) || empty($input['booking_number'])) {
        throw new Exception('Booking number is required');
    }
    
    $booking_number = trim($input['booking_number']);
    
    // Extract hunt code from booking number
    $hunt_code = extractHuntCodeFromBooking($booking_number);
    
    if (!$hunt_code) {
        throw new Exception('Invalid booking number format. Please check your booking number.');
    }
    
    // First, try the WordPress API (in case it gets fixed)
    $wordpress_api_url = WORDPRESS_API_BASE_URL . '/booking/' . urlencode($booking_number);
    $api_response = makeApiRequest($wordpress_api_url, 'GET', null, 5); // Short timeout
    
    // Check if API response is valid (not a 404)
    $use_api = false;
    if ($api_response !== false) {
        $api_data = json_decode($api_response, true);
        if (!isset($api_data['code']) || $api_data['code'] !== 'rest_no_route') {
            $use_api = true;
        }
    }
    
    if ($use_api) {
        // Use API response if available and valid
        $booking_data = $api_data;
        
        if (!$booking_data || (isset($booking_data['code']) && $booking_data['code'] === 'booking_not_found')) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking number not found. Please check your booking number and try again.'
            ]);
            exit;
        }
        
        if (!isset($booking_data['booking'])) {
            throw new Exception('Invalid response from booking system.');
        }
        
        $booking = $booking_data['booking'];
        
        // Check if payment status is confirmed (succeeded or confirmed)
        $valid_statuses = ['succeeded', 'confirmed'];
        if (!in_array(strtolower($booking['payment_status']), $valid_statuses) && 
            !in_array(strtolower($booking['status']), ['confirmed'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking found but payment is not confirmed. Please contact support if you believe this is an error.'
            ]);
            exit;
        }
        
        $booking_source = "WordPress API";
        
    } else {
        // Fallback to local database
        logError("WordPress API unavailable, using local database fallback", [
            'api_response' => $api_response,
            'booking_number' => $booking_number
        ]);
        
        $mainDb = getMainDb();
        $stmt = $mainDb->prepare("SELECT booking_code, payment_status FROM wp2s_pp_bookings WHERE booking_code = ?");
        
        if (!$stmt) {
            throw new Exception("Database query preparation failed. Please contact support.");
        }
        
        $stmt->bind_param("s", $booking_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking number not found. Please check your booking number and try again.'
            ]);
            $stmt->close();
            exit;
        }
        
        $booking_db = $result->fetch_assoc();
        
        // Check payment status - accept multiple valid formats
        $valid_statuses = ['paid', 'succeeded', 'confirmed', 'complete', 'completed'];
        if (!in_array(strtolower($booking_db['payment_status']), $valid_statuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking found but payment is not confirmed. Please contact support if you believe this is an error.'
            ]);
            $stmt->close();
            exit;
        }
        
        // Create booking object in same format as API
        $booking = [
            'booking_code' => $booking_db['booking_code'],
            'payment_status' => $booking_db['payment_status'],
            'participant_names' => '',
            'participant_count' => 1
        ];
        
        $booking_source = "Local Database";
        $stmt->close();
    }
    
    // Get local hunt information from app database
    $mainDb = getMainDb();
    $huntStmt = $mainDb->prepare("SELECT id, hunt_name, location, description, instructions, total_clues, estimated_duration FROM pp_hunts WHERE hunt_code = ? AND is_active = TRUE");
    $huntStmt->bind_param("s", $hunt_code);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception('Hunt not found or not active. Hunt code: ' . $hunt_code);
    }
    
    $hunt = $huntResult->fetch_assoc();
    $hunt_id = $hunt['id'];
    
    // Success - booking is valid and paid
    $response = [
        'success' => true,
        'message' => 'Booking verified successfully! Get ready for your ' . $hunt['hunt_name'] . ' adventure!',
        'booking_data' => [
            'booking_code' => $booking['booking_code'],
            'participant_names' => $booking['participant_names'] ?? '',
            'participant_count' => $booking['participant_count'] ?? 1
        ],
        'hunt_data' => [
            'hunt_id' => $hunt_id,
            'hunt_code' => $hunt_code,
            'hunt_name' => $hunt['hunt_name'],
            'location' => $hunt['location'],
            'description' => $hunt['description'],
            'instructions' => $hunt['instructions'],
            'total_clues' => $hunt['total_clues'],
            'estimated_duration' => $hunt['estimated_duration']
        ],
        'debug_info' => [
            'source' => $booking_source,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response);
    
    $huntStmt->close();
    $mainDb->close();
    
} catch (Exception $e) {
    logError("Booking verification error", [
        'error' => $e->getMessage(),
        'booking_number' => $booking_number ?? 'N/A',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
