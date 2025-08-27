<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Test Booking Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style></head><body>";

echo "<h1>🧪 Test Booking Fix</h1>";

try {
    $mainDb = getMainDb();
    
    // Check existing bookings
    echo "<h2>📋 Current Bookings in Database</h2>";
    $bookingsResult = $mainDb->query("SELECT booking_code, payment_status, created_at FROM wp2s_pp_bookings ORDER BY created_at DESC LIMIT 10");
    
    if ($bookingsResult && $bookingsResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Booking Code</th><th>Payment Status</th><th>Created</th><th>Action</th></tr>";
        
        while ($booking = $bookingsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($booking['booking_code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($booking['payment_status']) . "</td>";
            echo "<td>" . htmlspecialchars($booking['created_at'] ?? 'N/A') . "</td>";
            echo "<td><button onclick='testBooking(\"" . htmlspecialchars($booking['booking_code']) . "\")'>Test This Booking</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No bookings found in database. Creating test bookings...</div>";
        
        // Create test bookings
        $test_bookings = [
            ['BB-20250117-1234', 'Paid'],
            ['EP-20250117-5678', 'Paid'],
            ['TEST-20250117-0001', 'Paid']
        ];
        
        foreach ($test_bookings as $booking_data) {
            $stmt = $mainDb->prepare("INSERT INTO wp2s_pp_bookings (booking_code, payment_status, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status)");
            $stmt->bind_param("ss", $booking_data[0], $booking_data[1]);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Created test booking: " . $booking_data[0] . "</div>";
            }
            $stmt->close();
        }
        
        echo "<div class='info'>Refresh this page to see the created bookings.</div>";
    }
    
    // Check hunt data
    echo "<h2>🎯 Available Hunts</h2>";
    $huntsResult = $mainDb->query("SELECT hunt_code, hunt_name, location, is_active FROM pp_hunts ORDER BY hunt_code");
    
    if ($huntsResult && $huntsResult->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Hunt Code</th><th>Hunt Name</th><th>Location</th><th>Status</th></tr>";
        
        while ($hunt = $huntsResult->fetch_assoc()) {
            $status_class = $hunt['is_active'] ? 'success' : 'error';
            $status_text = $hunt['is_active'] ? 'Active' : 'Inactive';
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($hunt['hunt_code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($hunt['hunt_name']) . "</td>";
            echo "<td>" . htmlspecialchars($hunt['location']) . "</td>";
            echo "<td><span style='color: " . ($hunt['is_active'] ? 'green' : 'red') . ";'>$status_text</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No hunts found in database. The app needs hunt data to work.</div>";
        echo "<div class='info'>Run the database setup scripts to create hunt data.</div>";
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Quick Actions</h2>";
echo "<div class='info'>";
echo "<h3>To Fix the Booking System RIGHT NOW:</h3>";
echo "<ol>";
echo "<li><strong>Rename the fixed file:</strong><br>";
echo "   Rename <code>verify_booking_fixed.php</code> to <code>verify_booking.php</code></li>";
echo "<li><strong>Test with any booking above</strong> - it should work immediately</li>";
echo "<li><strong>The system will automatically fallback to local database</strong> when WordPress API is unavailable</li>";
echo "</ol>";
echo "</div>";

echo "<h2>💡 WordPress Plugin Fix</h2>";
echo "<div class='info'>";
echo "<p>The WordPress plugin is not properly installed/activated. Here's how to fix it:</p>";
echo "<ol>";
echo "<li>Log into your WordPress admin at: <a href='https://puzzlepath.com.au/wp-admin' target='_blank'>https://puzzlepath.com.au/wp-admin</a></li>";
echo "<li>Go to <strong>Plugins → Installed Plugins</strong></li>";
echo "<li>Look for <strong>'Puzzle Path Booking System'</strong> or similar</li>";
echo "<li>If it's there but deactivated, click <strong>'Activate'</strong></li>";
echo "<li>If it's not there, you need to install the plugin</li>";
echo "<li>After activation, test this URL: <a href='https://puzzlepath.com.au/wp-json/puzzlepath/v1' target='_blank'>https://puzzlepath.com.au/wp-json/puzzlepath/v1</a></li>";
echo "</ol>";
echo "</div>";

?>

<script>
function testBooking(bookingCode) {
    // Test the booking by making a POST request
    const testData = {
        booking_number: bookingCode
    };
    
    fetch('verify_booking_fixed.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ SUCCESS!\n\nBooking: ' + bookingCode + '\nHunt: ' + data.hunt_data.hunt_name + '\nSource: ' + data.debug_info.source);
        } else {
            alert('❌ FAILED!\n\nError: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ NETWORK ERROR!\n\n' + error);
    });
}
</script>

</body></html>
