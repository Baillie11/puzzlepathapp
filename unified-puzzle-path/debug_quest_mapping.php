<?php
/**
 * Debug Quest Mapping and Clue Loading
 * Check what's happening with quest identification and clue retrieval
 */

echo "<!DOCTYPE html><html><head><title>Quest Mapping Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
input { padding: 10px; width: 300px; margin: 5px; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style></head><body>";

echo "<h1>🐛 Quest Mapping & Clue Debug</h1>";

try {
    define('PUZZLE_PATH_ACCESS', true);
    require_once 'config-secure.php';
    
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database connected</div>";
    
    // Test quest mapping with a sample booking
    if (isset($_POST['test_booking'])) {
        $test_booking = trim($_POST['booking_code']);
        
        echo "<h2>🔍 Testing Booking: " . htmlspecialchars($test_booking) . "</h2>";
        
        // Step 1: Extract hunt code
        $extracted_hunt_code = extractHuntCodeFromBooking($test_booking);
        echo "<div class='info'><strong>Step 1 - Extracted Hunt Code:</strong> " . ($extracted_hunt_code ?? 'NULL') . "</div>";
        
        if ($extracted_hunt_code) {
            // Step 2: Map to database hunt code  
            $db_hunt_code = mapBookingCodeToHuntCode($extracted_hunt_code);
            echo "<div class='info'><strong>Step 2 - Mapped Database Hunt Code:</strong> " . ($db_hunt_code ?? 'NULL') . "</div>";
            
            if ($db_hunt_code) {
                // Step 3: Query for hunt in events table
                $huntStmt = $mainDb->prepare("
                    SELECT id, title, location, hunt_code, hunt_name, description 
                    FROM wp2s_pp_events
                    WHERE hunt_code = ? AND (created_at IS NULL OR created_at <= NOW())
                    LIMIT 1
                ");
                $huntStmt->bind_param("s", $db_hunt_code);
                $huntStmt->execute();
                $huntResult = $huntStmt->get_result();
                
                if ($huntResult->num_rows > 0) {
                    $hunt = $huntResult->fetch_assoc();
                    echo "<div class='success'>✅ <strong>Step 3 - Hunt Found in Database!</strong></div>";
                    
                    echo "<h3>Hunt Details:</h3>";
                    echo "<table>";
                    echo "<tr><th>Field</th><th>Value</th></tr>";
                    foreach ($hunt as $field => $value) {
                        echo "<tr><td><strong>$field</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                    }
                    echo "</table>";
                    
                    // Step 4: Check for clues
                    echo "<h3>🧩 Step 4 - Checking Clues:</h3>";
                    
                    // Check if clues table exists and what it contains
                    $clue_tables = ['pp_clues', 'wp2s_pp_clues', 'wp_pp_clues'];
                    $found_clues_table = null;
                    
                    foreach ($clue_tables as $table) {
                        $check = $mainDb->query("SHOW TABLES LIKE '$table'");
                        if ($check && $check->num_rows > 0) {
                            echo "<div class='success'>✅ Found clues table: $table</div>";
                            $found_clues_table = $table;
                            
                            // Check for clues for this hunt
                            $clueCheck = $mainDb->prepare("SELECT id, title, clue_text FROM $table WHERE hunt_id = ?");
                            $clueCheck->bind_param("i", $hunt['id']);
                            $clueCheck->execute();
                            $clueResult = $clueCheck->get_result();
                            
                            if ($clueResult && $clueResult->num_rows > 0) {
                                echo "<div class='success'>✅ Found " . $clueResult->num_rows . " clues for hunt_id " . $hunt['id'] . "</div>";
                                
                                echo "<h4>Clues for this hunt:</h4>";
                                echo "<table><tr><th>ID</th><th>Title</th><th>Clue Text</th></tr>";
                                while ($clue = $clueResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $clue['id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($clue['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($clue['clue_text'], 0, 100)) . "...</td>";
                                    echo "</tr>";
                                }
                                echo "</table>";
                            } else {
                                echo "<div class='error'>❌ No clues found for hunt_id " . $hunt['id'] . " in $table</div>";
                                
                                // Show what hunt_ids DO have clues
                                $allClues = $mainDb->query("SELECT DISTINCT hunt_id FROM $table");
                                if ($allClues && $allClues->num_rows > 0) {
                                    echo "<div class='info'>Available hunt_ids with clues in $table: ";
                                    $hunt_ids = [];
                                    while ($row = $allClues->fetch_assoc()) {
                                        $hunt_ids[] = $row['hunt_id'];
                                    }
                                    echo implode(', ', $hunt_ids) . "</div>";
                                }
                            }
                            $clueCheck->close();
                            break;
                        } else {
                            echo "<div class='error'>❌ Table not found: $table</div>";
                        }
                    }
                    
                    if (!$found_clues_table) {
                        echo "<div class='error'>❌ No clues table found! This is why clues aren't loading.</div>";
                    }
                    
                } else {
                    echo "<div class='error'>❌ <strong>Step 3 - No Hunt Found for code '$db_hunt_code'</strong></div>";
                    
                    // Show available hunt codes
                    $available = $mainDb->query("SELECT hunt_code, title FROM wp2s_pp_events");
                    if ($available) {
                        echo "<h4>Available hunt codes in wp2s_pp_events:</h4>";
                        echo "<table><tr><th>Hunt Code</th><th>Title</th></tr>";
                        while ($row = $available->fetch_assoc()) {
                            echo "<tr><td><strong>" . htmlspecialchars($row['hunt_code']) . "</strong></td><td>" . htmlspecialchars($row['title']) . "</td></tr>";
                        }
                        echo "</table>";
                    }
                }
                $huntStmt->close();
            }
        }
        
        echo "<hr>";
        echo "<h3>🔧 Diagnosis Summary:</h3>";
        if ($extracted_hunt_code && $db_hunt_code) {
            echo "<div class='info'>The quest mapping appears to be working. If you're still seeing wrong quest names, there might be browser caching or the wrong config file is being used.</div>";
        } else {
            echo "<div class='error'>The quest mapping is failing. Check the booking code format and mapping rules.</div>";
        }
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>🧪 Test a Booking Code</h2>";
echo "<form method='post'>";
echo "<input type='text' name='booking_code' placeholder='Enter booking code (e.g., CLG-20250810-1234)' value='" . htmlspecialchars($_POST['booking_code'] ?? '') . "' required>";
echo "<button type='submit' name='test_booking'>Debug This Booking</button>";
echo "</form>";

echo "<div class='info'>";
echo "<h3>📋 Test These Codes:</h3>";
echo "<ul>";
echo "<li><strong>CLG-*</strong> should map to Coolangatta Walking Quest (CLG562)</li>";
echo "<li><strong>BBR1-*</strong> should map to Broadbeach Adventurer Quest (BBQ123)</li>";
echo "<li><strong>PP-*</strong> should map to Broadbeach Adventurer Quest (BBQ123)</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
