<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Database Setup Check</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>🔍 Database Setup Check</h1>";

echo "<h2>📊 Database Connection (Single WordPress Database)</h2>";

// Test WordPress database (all tables)
try {
    $mainDb = getMainDb();
    echo "<div class='success'>✅ WordPress Database Connected: " . DB_NAME . "</div>";
    
    // Check required tables
    $required_tables = [
        'wp2s_pp_bookings' => 'Booking data',
        'wp2s_pp_events' => 'Hunt/Event data', 
        'wp2s_pp_coupons' => 'Coupon data'
    ];
    
    foreach ($required_tables as $table => $description) {
        $result = $mainDb->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div class='success'>✅ $table exists ($description)</div>";
            
            // Count records
            $count = $mainDb->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "<div class='info'>📋 Found $count record(s) in $table</div>";
        } else {
            echo "<div class='error'>❌ $table missing ($description)</div>";
        }
    }
    
    // Show event data (hunts)
    $events = $mainDb->query("SELECT hunt_code, hunt_name, title, location FROM wp2s_pp_events ORDER BY hunt_code");
    if ($events && $events->num_rows > 0) {
        echo "<h3>🎯 Available Events/Hunts</h3>";
        echo "<table>";
        echo "<tr><th>Hunt Code</th><th>Hunt Name</th><th>Title</th><th>Location</th></tr>";
        
        while ($event = $events->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($event['hunt_code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($event['hunt_name']) . "</td>";
            echo "<td>" . htmlspecialchars($event['title']) . "</td>";
            echo "<td>" . htmlspecialchars($event['location']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='info'>";
        echo "<h4>🔄 Code Mapping</h4>";
        echo "<p>Your system uses this mapping:</p>";
        echo "<ul>";
        echo "<li><strong>Booking codes:</strong> BB-YYYYMMDD-XXXX, EP-YYYYMMDD-XXXX</li>";
        echo "<li><strong>Database hunt codes:</strong> BBR1, EP (etc.)</li>";
        echo "<li><strong>Mapping:</strong> BB → BBR1</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ No events found in wp2s_pp_events table</div>";
        echo "<div class='info'>You need to add events/hunts to the wp2s_pp_events table.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧️ How the System Works Now</h2>";
echo "<div class='info'>";
echo "<p><strong>Single WordPress Database Setup:</strong></p>";
echo "<ul>";
echo "<li><strong>WordPress Database</strong> (" . DB_NAME . ") - Contains ALL puzzle path data:</li>";
echo "<li style='margin-left: 20px;'>📋 wp2s_pp_bookings - Booking and payment data</li>";
echo "<li style='margin-left: 20px;'>🎯 wp2s_pp_events - Hunt/event definitions</li>";
echo "<li style='margin-left: 20px;'>🎫 wp2s_pp_coupons - Coupon system</li>";
echo "</ul>";
echo "<p>The booking verification queries the single WordPress database for both booking verification and hunt information.</p>";
echo "</div>";

if (isset($mainDb)) $mainDb->close();

echo "</body></html>";
?>
