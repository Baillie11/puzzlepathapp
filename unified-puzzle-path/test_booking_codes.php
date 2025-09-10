<?php
// Test script to verify booking codes work correctly
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Test booking codes for different hunts
$test_bookings = [
    'CLG562-20240901-1234' => 'Dynamic Hunt Recognition Test',
    'BBR1-20240901-5678' => 'Dynamic Hunt Recognition Test',
    'SUR789-20240901-9012' => 'Dynamic Hunt Recognition Test',
    'EL-20240901-3456' => 'Dynamic Hunt Recognition Test',
    'KOALA-20240901-7890' => 'Dynamic Hunt Recognition Test',
    'NEWTEST-20241210-9999' => 'Should show "Hunt not found" (expected)'
];

echo "=== BOOKING CODE TO HUNT MAPPING TEST ===\n\n";

foreach ($test_bookings as $booking_code => $note) {
    echo "Testing Booking: $booking_code\n";
    echo "Note: $note\n";
    
    // Extract and map hunt code
    $hunt_code = extractHuntCodeFromBooking($booking_code);
    $db_hunt_code = mapBookingCodeToHuntCode($hunt_code);
    
    echo "Extracted Hunt Code: " . ($hunt_code ?: 'NULL') . "\n";
    echo "Mapped DB Hunt Code: " . ($db_hunt_code ?: 'NULL') . "\n";
    
    if ($db_hunt_code) {
        try {
            $db = getMainDb();
            $stmt = $db->prepare("SELECT id, title, hunt_code FROM wp2s_pp_events WHERE hunt_code = ? LIMIT 1");
            $stmt->bind_param("s", $db_hunt_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $hunt = $result->fetch_assoc();
                echo "✅ SUCCESS: Found Hunt ID=" . $hunt['id'] . ", Title='" . $hunt['title'] . "'\n";
                
                // Check if clues exist
                $clueStmt = $db->prepare("SELECT COUNT(*) as clue_count FROM wp2s_pp_clues WHERE hunt_id = ?");
                $clueStmt->bind_param("i", $hunt['id']);
                $clueStmt->execute();
                $clueResult = $clueStmt->get_result();
                if ($clueResult->num_rows > 0) {
                    $clueData = $clueResult->fetch_assoc();
                    echo "   📍 Clues available: " . $clueData['clue_count'] . "\n";
                }
                $clueStmt->close();
            } else {
                echo "❌ Hunt NOT FOUND in database\n";
            }
            $stmt->close();
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ No mapping found\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

try {
    $db = getMainDb();
    echo "=== ALL HUNTS IN DATABASE ===\n";
    $result = $db->query("SELECT id, title, hunt_code, hunt_name FROM wp2s_pp_events WHERE id IN (9,10,11,12,15,16,17,18,19,20,21) ORDER BY id");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['id']}, Hunt Code: '{$row['hunt_code']}', Title: '{$row['title']}'\n";
        }
    } else {
        echo "No results found\n";
    }
    
    $db->close();
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    echo "\nTo fix database issues:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check database credentials in config-secure.php\n";
    echo "3. Run the update_hunt_codes.sql script in phpMyAdmin\n";
}
?>
