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
    
    // Get database connections
    $mainDb = getMainDb();
    
    // First, get the hunt information
    $huntStmt = $mainDb->prepare("SELECT id, hunt_name, location, description, instructions, total_clues, estimated_duration FROM pp_hunts WHERE hunt_code = ? AND is_active = TRUE");
    $huntStmt->bind_param("s", $hunt_code);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception('Hunt not found or not active. Hunt code: ' . $hunt_code);
    }
    
    $hunt = $huntResult->fetch_assoc();
    $hunt_id = $hunt['id'];
    
    // Check if booking exists and is valid
    $bookingStmt = $mainDb->prepare("SELECT booking_code, payment_status, participant_names, participant_count FROM wp2s_pp_bookings WHERE booking_code = ?");
    $bookingStmt->bind_param("s", $booking_number);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    
    if ($bookingResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking number not found. Please check your booking number and try again.'
        ]);
        exit;
    }
    
    $booking = $bookingResult->fetch_assoc();
    
    // Check if payment status is "Paid" (case-insensitive)
    if (strtolower($booking['payment_status']) !== 'paid') {
        echo json_encode([
            'success' => false,
            'message' => 'Booking found but payment is not confirmed. Please contact support if you believe this is an error.'
        ]);
        exit;
    }
    
    // Update booking with hunt_id if not already set
    $updateStmt = $mainDb->prepare("UPDATE wp2s_pp_bookings SET hunt_id = ? WHERE booking_code = ? AND hunt_id IS NULL");
    $updateStmt->bind_param("is", $hunt_id, $booking_number);
    $updateStmt->execute();
    
    // Success - booking is valid and paid
    $response = [
        'success' => true,
        'message' => 'Booking verified successfully! Get ready for your ' . $hunt['hunt_name'] . ' adventure!',
        'booking_data' => [
            'booking_code' => $booking['booking_code'],
            'participant_names' => $booking['participant_names'],
            'participant_count' => $booking['participant_count']
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
        ]
    ];
    
    echo json_encode($response);
    
    $huntStmt->close();
    $bookingStmt->close();
    if (isset($updateStmt)) $updateStmt->close();
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
