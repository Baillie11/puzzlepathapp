<?php
/**
 * Events Table Diagnostic
 * Check what hunt/event data exists
 */

echo "<!DOCTYPE html><html><head><title>Events Table Check</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>🎯 Events/Hunt Table Diagnostic</h1>";

try {
    define('PUZZLE_PATH_ACCESS', true);
    require_once 'config-secure.php';
    
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check if wp2s_pp_events table exists
    echo "<h2>📊 Step 1: Check Events Table</h2>";
    
    $tables_to_check = ['wp2s_pp_events', 'wp_pp_events', 'ozbizfin_wp793_pp_events'];
    $found_events_table = null;
    
    foreach ($tables_to_check as $table) {
        $result = $mainDb->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ Found events table: $table</div>";
            $found_events_table = $table;
            
            // Show table structure
            $structure = $mainDb->query("DESCRIBE $table");
            if ($structure) {
                echo "<h3>🏗️ Table Structure for $table</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                while ($col = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . $col['Field'] . "</strong></td>";
                    echo "<td>" . $col['Type'] . "</td>";
                    echo "<td>" . $col['Null'] . "</td>";
                    echo "<td>" . $col['Key'] . "</td>";
                    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Show all events/hunts
            $events = $mainDb->query("SELECT * FROM $table ORDER BY id");
            if ($events && $events->num_rows > 0) {
                echo "<h3>📋 All Events/Hunts in $table</h3>";
                echo "<table>";
                
                // Get first row to determine columns
                $firstRow = $events->fetch_assoc();
                if ($firstRow) {
                    // Header row
                    echo "<tr>";
                    foreach (array_keys($firstRow) as $col) {
                        echo "<th>$col</th>";
                    }
                    echo "</tr>";
                    
                    // First row
                    echo "<tr>";
                    foreach ($firstRow as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                    
                    // Additional rows
                    while ($row = $events->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ No events/hunts found in $table</div>";
            }
            break;
        } else {
            echo "<div class='error'>❌ Table not found: $table</div>";
        }
    }
    
    if (!$found_events_table) {
        echo "<div class='error'>❌ No events table found!</div>";
        echo "<div class='info'>The system needs an events/hunts table to work properly.</div>";
    }
    
    // Test hunt code extraction and mapping
    echo "<h2>🔍 Step 2: Test Hunt Code Processing</h2>";
    
    $test_booking = 'BBR1-20250816-2871';
    echo "<div class='info'>Testing with booking code: <strong>$test_booking</strong></div>";
    
    // Test extraction
    $hunt_code = extractHuntCodeFromBooking($test_booking);
    echo "<div class='info'><strong>Extracted Hunt Code:</strong> " . ($hunt_code ?? 'NULL') . "</div>";
    
    if ($hunt_code) {
        $db_hunt_code = mapBookingCodeToHuntCode($hunt_code);
        echo "<div class='info'><strong>Mapped Database Hunt Code:</strong> " . ($db_hunt_code ?? 'NULL') . "</div>";
        
        // Test if this hunt exists in the events table
        if ($found_events_table) {
            $huntCheck = $mainDb->prepare("SELECT id, title, hunt_code, hunt_name FROM $found_events_table WHERE hunt_code = ?");
            $huntCheck->bind_param("s", $db_hunt_code);
            $huntCheck->execute();
            $huntResult = $huntCheck->get_result();
            
            if ($huntResult->num_rows > 0) {
                $hunt = $huntResult->fetch_assoc();
                echo "<div class='success'>✅ Hunt found in database!</div>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($hunt as $field => $value) {
                    echo "<tr><td>$field</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Hunt with code '$db_hunt_code' not found in events table</div>";
                echo "<div class='info'>This is why you're getting 'Hunt not available' error.</div>";
                
                // Show what hunt codes ARE available
                $availableCodes = $mainDb->query("SELECT DISTINCT hunt_code FROM $found_events_table");
                if ($availableCodes && $availableCodes->num_rows > 0) {
                    echo "<h4>Available hunt codes in database:</h4>";
                    echo "<ul>";
                    while ($code = $availableCodes->fetch_assoc()) {
                        echo "<li><strong>" . htmlspecialchars($code['hunt_code'] ?? 'NULL') . "</strong></li>";
                    }
                    echo "</ul>";
                }
            }
            $huntCheck->close();
        }
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>💡 Possible Solutions</h2>";
echo "<div class='info'>";
echo "<p>If no hunt was found, you might need to:</p>";
echo "<ol>";
echo "<li><strong>Create a hunt/event</strong> with code 'BBR1' in your WordPress admin</li>";
echo "<li><strong>Update the mapping</strong> to use an existing hunt code</li>";
echo "<li><strong>Add default hunt data</strong> to the events table</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
