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
    
    // Use direct database access (standalone app approach)
    $mainDb = getMainDb();
    $stmt = $mainDb->prepare("SELECT booking_code, payment_status FROM wp2s_pp_bookings WHERE booking_code = ?");
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $mainDb->error);
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
    $stmt->close();
    
    // Check payment status - accept multiple valid formats
    $valid_statuses = ['paid', 'succeeded', 'confirmed', 'complete', 'completed'];
    if (!in_array(strtolower($booking_db['payment_status']), $valid_statuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking found but payment is not confirmed. Please contact support if you believe this is an error.'
        ]);
        exit;
    }
    
    // Create booking object
    $booking = [
        'booking_code' => $booking_db['booking_code'],
        'payment_status' => $booking_db['payment_status'],
        'participant_names' => '',
        'participant_count' => 1
    ];
    
    // First, let's find out what columns actually exist in wp2s_pp_events
    $columnsQuery = $mainDb->query("SHOW COLUMNS FROM wp2s_pp_events");
    $columns = [];
    while ($col = $columnsQuery->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    
    // Try to find hunt/event data with whatever columns exist
    // Start with a basic query to see if we can find the event by code
    $eventQuery = "SELECT * FROM wp2s_pp_events WHERE ";
    
    // Try common variations of event code column names
    $possible_code_columns = ['event_code', 'code', 'hunt_code', 'id'];
    $code_column_found = null;
    
    foreach ($possible_code_columns as $col) {
        if (in_array($col, $columns)) {
            $code_column_found = $col;
            break;
        }
    }
    
    if ($code_column_found) {
        $eventQuery .= "$code_column_found = ?";
        
        // Add active condition if such column exists
        if (in_array('event_active', $columns)) {
            $eventQuery .= " AND event_active = 1";
        } elseif (in_array('active', $columns)) {
            $eventQuery .= " AND active = 1";
        } elseif (in_array('is_active', $columns)) {
            $eventQuery .= " AND is_active = 1";
        }
        
        $huntStmt = $mainDb->prepare($eventQuery);
        $huntStmt->bind_param("s", $hunt_code);
        $huntStmt->execute();
        $huntResult = $huntStmt->get_result();
        
        if ($huntResult->num_rows === 0) {
            // If no exact match, try to find any event and use hunt code as fallback
            $hunt = [
                'id' => 1,
                'title' => $hunt_code === 'BB' ? 'Broadbeach Quest' : 'Emerald Park Quest',
                'location' => $hunt_code === 'BB' ? 'Broadbeach' : 'Emerald Park',
                'description' => 'Puzzle Path Quest Adventure',
                'instructions' => 'Follow the clues and complete the quest!'
            ];
            $hunt_id = 1;
            
            logError("No event found in database, using fallback data", [
                'hunt_code' => $hunt_code,
                'available_columns' => $columns
            ]);
        } else {
            $hunt_raw = $huntResult->fetch_assoc();
            $hunt_id = $hunt_raw['id'] ?? 1;
            
            // Map columns to standard names
            $hunt = [
                'id' => $hunt_raw['id'] ?? 1,
                'title' => $hunt_raw['event_title'] ?? $hunt_raw['title'] ?? $hunt_raw['name'] ?? 'Unknown Quest',
                'location' => $hunt_raw['event_location'] ?? $hunt_raw['location'] ?? 'Unknown Location',
                'description' => $hunt_raw['event_description'] ?? $hunt_raw['description'] ?? 'Quest Description',
                'instructions' => $hunt_raw['event_short_description'] ?? $hunt_raw['instructions'] ?? $hunt_raw['short_description'] ?? 'Follow the quest instructions'
            ];
        }
        
        $huntStmt->close();
    } else {
        // No recognizable code column found, use fallback
        $hunt = [
            'id' => 1,
            'title' => $hunt_code === 'BB' ? 'Broadbeach Quest' : 'Emerald Park Quest',
            'location' => $hunt_code === 'BB' ? 'Broadbeach' : 'Emerald Park', 
            'description' => 'Puzzle Path Quest Adventure',
            'instructions' => 'Follow the clues and complete the quest!'
        ];
        $hunt_id = 1;
        
        logError("No recognizable code column found, using fallback data", [
            'hunt_code' => $hunt_code,
            'available_columns' => $columns
        ]);
    }
    
    // Success - booking is valid and paid
    $response = [
        'success' => true,
        'message' => 'Booking verified successfully! Get ready for your ' . $hunt['title'] . ' adventure!',
        'booking_data' => [
            'booking_code' => $booking['booking_code'],
            'participant_names' => $booking['participant_names'] ?? '',
            'participant_count' => $booking['participant_count'] ?? 1
        ],
        'hunt_data' => [
            'hunt_id' => $hunt_id,
            'hunt_code' => $hunt_code,
            'hunt_name' => $hunt['title'],
            'location' => $hunt['location'],
            'description' => $hunt['description'],
            'instructions' => $hunt['instructions'],
            'total_clues' => 6, // Default - could be stored in events table
            'estimated_duration' => 90 // Default - could be stored in events table
        ],
        'debug_info' => [
            'available_columns' => $columns,
            'code_column_used' => $code_column_found ?? 'none'
        ]
    ];
    
    echo json_encode($response);
    
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
