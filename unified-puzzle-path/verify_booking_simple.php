<?php
// Simplified verify_booking.php for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Turn off error display to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Basic database connection test
    $conn = new mysqli('localhost', 'wpuser', 'wp123456', 'puzzlepath_wp');
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get the input
    $input_raw = file_get_contents('php://input');
    $input = json_decode($input_raw, true);
    
    if (!$input || !isset($input['booking_number'])) {
        throw new Exception('Invalid input');
    }
    
    $booking_number = $input['booking_number'];
    
    // Simple database query
    $stmt = $conn->prepare("SELECT booking_code, customer_name, payment_status FROM wp_pp_bookings WHERE booking_code = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $booking_number);
    
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found: ' . $booking_number,
            'debug' => 'No rows returned from database'
        ]);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking found successfully!',
        'booking_data' => [
            'booking_code' => $booking['booking_code'],
            'customer_name' => $booking['customer_name'],
            'payment_status' => $booking['payment_status']
        ],
        'hunt_data' => [
            'hunt_id' => 1,
            'hunt_code' => 'BB',
            'hunt_name' => 'Test Hunt',
            'location' => 'Test Location',
            'description' => 'Test Description',
            'instructions' => 'Test instructions',
            'total_clues' => 6,
            'estimated_duration' => 90
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => $e->getLine()
        ]
    ]);
}
?>
