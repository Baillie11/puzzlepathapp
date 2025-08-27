<?php
// Ultra-simple JSON test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Turn off ALL error reporting
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Simple JSON response with proper structure
echo json_encode([
    'success' => true,
    'message' => 'Test booking verified successfully!',
    'booking_data' => [
        'booking_code' => 'TEST-12345',
        'customer_name' => 'Test Customer',
        'payment_status' => 'succeeded'
    ],
    'hunt_data' => [
        'hunt_id' => 1,
        'hunt_code' => 'TEST',
        'hunt_name' => 'Test Hunt Adventure',
        'location' => 'Test Location',
        'description' => 'This is a test hunt for debugging',
        'instructions' => 'Click Start to begin your test adventure!',
        'total_clues' => 6,
        'estimated_duration' => 60
    ]
]);
?>
