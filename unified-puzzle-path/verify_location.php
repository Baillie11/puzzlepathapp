<?php
// Location verification endpoint for geofencing
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Disable error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Check if geofencing is enabled
    if (!defined('GEOFENCING_ENABLED') || !GEOFENCING_ENABLED) {
        throw new Exception('Geofencing is disabled');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Required fields
    $hunt_id = (int)($input['hunt_id'] ?? 0);
    $clue_id = (int)($input['clue_id'] ?? 0);
    $user_lat = (float)($input['user_lat'] ?? 0);
    $user_lng = (float)($input['user_lng'] ?? 0);
    $user_accuracy = (float)($input['user_accuracy'] ?? 999);

    // Optional fields
    $booking_code = trim($input['booking_code'] ?? '');

    // Basic validation
    if ($hunt_id <= 0) {
        throw new Exception('Valid hunt_id is required');
    }
    
    if ($clue_id <= 0) {
        throw new Exception('Valid clue_id is required');
    }
    
    if (abs($user_lat) < 0.0001 || abs($user_lng) < 0.0001) {
        throw new Exception('Valid user coordinates are required');
    }
    
    // Validate coordinate ranges
    if ($user_lat < -90 || $user_lat > 90 || $user_lng < -180 || $user_lng > 180) {
        throw new Exception('Invalid coordinate values');
    }

    // Database connection
    $db = getMainDb();
    
    // Get clue location from database
    $clueStmt = $db->prepare("
        SELECT 
            id,
            title,
            latitude,
            longitude,
            geofence_radius
        FROM wp2s_pp_clues 
        WHERE id = ? AND hunt_id = ? 
        LIMIT 1
    ");
    
    $clueStmt->bind_param("ii", $clue_id, $hunt_id);
    $clueStmt->execute();
    $clueResult = $clueStmt->get_result();
    
    if ($clueResult->num_rows === 0) {
        $clueStmt->close();
        throw new Exception('Clue not found or invalid hunt');
    }
    
    $clue = $clueResult->fetch_assoc();
    $clueStmt->close();
    
    // Check if clue has coordinates
    if (empty($clue['latitude']) || empty($clue['longitude'])) {
        // Non-geofenced clue - always allow
        echo json_encode([
            'success' => true,
            'verified' => true,
            'message' => 'Non-geofenced clue - location verification skipped',
            'distance_m' => null,
            'required_radius_m' => null,
            'clue_title' => $clue['title']
        ]);
        exit;
    }
    
    // Calculate distance using Haversine formula
    $distance_m = calculateHaversineDistance(
        $user_lat, $user_lng,
        (float)$clue['latitude'], (float)$clue['longitude']
    );
    
    // Determine effective radius
    $base_radius = max(MIN_GEOFENCE_RADIUS, (float)($clue['geofence_radius'] ?? MIN_GEOFENCE_RADIUS));
    $accuracy_buffer = min($user_accuracy * 0.5, 50); // Use half accuracy, cap at 50m
    $effective_radius = $base_radius + $accuracy_buffer;
    
    // Check if user is within geofence
    $within_geofence = $distance_m <= $effective_radius;
    
    // Log verification attempt
    logError("Location verification", [
        'hunt_id' => $hunt_id,
        'clue_id' => $clue_id,
        'booking_code' => $booking_code,
        'user_coords' => [$user_lat, $user_lng],
        'clue_coords' => [(float)$clue['latitude'], (float)$clue['longitude']],
        'distance_m' => round($distance_m, 2),
        'effective_radius_m' => round($effective_radius, 2),
        'within_geofence' => $within_geofence,
        'user_accuracy' => $user_accuracy
    ], 'INFO');
    
    // Optional: Store location audit record
    if ($booking_code) {
        try {
            $auditStmt = $db->prepare("
                INSERT INTO pp_location_audit 
                (booking_code, hunt_id, clue_id, user_lat, user_lng, user_accuracy_m, distance_m, within_geofence, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $auditStmt->bind_param("siiddddi", 
                $booking_code, $hunt_id, $clue_id, 
                $user_lat, $user_lng, $user_accuracy, 
                $distance_m, $within_geofence
            );
            
            $auditStmt->execute();
            $auditStmt->close();
        } catch (Exception $e) {
            // Don't fail verification if audit fails
            logError("Location audit failed", ['error' => $e->getMessage()], 'WARNING');
        }
    }
    
    $db->close();
    
    // Return verification result
    echo json_encode([
        'success' => true,
        'verified' => $within_geofence,
        'message' => $within_geofence ? 'Location verified' : 'Outside geofence area',
        'distance_m' => round($distance_m, 1),
        'required_radius_m' => round($base_radius, 1),
        'effective_radius_m' => round($effective_radius, 1),
        'clue_title' => $clue['title'],
        'accuracy_m' => $user_accuracy
    ]);
    
} catch (Exception $e) {
    // Log error securely
    logError("Location verification error", [
        'error' => $e->getMessage(),
        'hunt_id' => $hunt_id ?? 'N/A',
        'clue_id' => $clue_id ?? 'N/A',
        'file' => basename(__FILE__)
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'verified' => false,
        'message' => $e->getMessage(),
        'distance_m' => null,
        'required_radius_m' => null
    ]);
}

/**
 * Calculate distance between two points using Haversine formula
 * Returns distance in meters
 */
function calculateHaversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // Earth's radius in meters
    
    $lat1_rad = deg2rad($lat1);
    $lng1_rad = deg2rad($lng1);
    $lat2_rad = deg2rad($lat2);
    $lng2_rad = deg2rad($lng2);
    
    $dlat = $lat2_rad - $lat1_rad;
    $dlng = $lng2_rad - $lng1_rad;
    
    $a = sin($dlat / 2) * sin($dlat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($dlng / 2) * sin($dlng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}
?>