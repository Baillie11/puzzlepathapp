<?php
/**
 * Specific Booking Code Checker
 * Test specific booking codes to see what's in your database
 */

echo "<!DOCTYPE html><html><head><title>Booking Code Checker</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
input { padding: 10px; width: 300px; margin: 5px; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style></head><body>";

echo "<h1>🔍 Booking Code Checker</h1>";

// Test database connection
try {
    define('PUZZLE_PATH_ACCESS', true);
    require_once 'config-secure.php';
    
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database connected</div>";
    
    // Show all booking codes in database
    echo "<h2>📋 All Booking Codes in Your Database</h2>";
    
    $allBookings = $mainDb->query("SELECT booking_code, customer_name, customer_email, payment_status FROM wp2s_pp_bookings ORDER BY created_at DESC LIMIT 20");
    
    if ($allBookings && $allBookings->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Booking Code</th><th>Customer Name</th><th>Email</th><th>Status</th></tr>";
        
        while ($row = $allBookings->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['booking_code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No bookings found</div>";
    }
    
    // Test specific booking code
    if (isset($_POST['test_code'])) {
        $test_code = trim($_POST['booking_code']);
        echo "<h2>🧪 Testing Booking Code: " . htmlspecialchars($test_code) . "</h2>";
        
        // Test the hunt code extraction
        $hunt_code = extractHuntCodeFromBooking($test_code);
        echo "<div class='info'><strong>Extracted Hunt Code:</strong> " . ($hunt_code ?? 'NULL') . "</div>";
        
        if ($hunt_code) {
            $db_hunt_code = mapBookingCodeToHuntCode($hunt_code);
            echo "<div class='info'><strong>Mapped Database Hunt Code:</strong> " . ($db_hunt_code ?? 'NULL') . "</div>";
        }
        
        // Search for the booking code
        $stmt = $mainDb->prepare("SELECT * FROM wp2s_pp_bookings WHERE booking_code = ?");
        $stmt->bind_param("s", $test_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            echo "<div class='success'>✅ Booking Found!</div>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($booking as $field => $value) {
                echo "<tr><td>$field</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Booking code not found in database</div>";
            
            // Check if it might be in a different table
            echo "<h3>Checking other possible tables...</h3>";
            $other_tables = ['wp_pp_bookings', 'ozbizfin_wp793_pp_bookings'];
            
            foreach ($other_tables as $table) {
                $check_table = $mainDb->query("SHOW TABLES LIKE '$table'");
                if ($check_table && $check_table->num_rows > 0) {
                    echo "<div class='info'>Checking table: $table</div>";
                    $stmt2 = $mainDb->prepare("SELECT booking_code, customer_name, payment_status FROM $table WHERE booking_code = ?");
                    $stmt2->bind_param("s", $test_code);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    
                    if ($result2->num_rows > 0) {
                        $booking2 = $result2->fetch_assoc();
                        echo "<div class='success'>✅ Found in $table!</div>";
                        echo "<pre>" . print_r($booking2, true) . "</pre>";
                    } else {
                        echo "<div class='error'>❌ Not found in $table</div>";
                    }
                    $stmt2->close();
                }
            }
        }
        $stmt->close();
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

?>

<h2>🧪 Test a Specific Booking Code</h2>
<form method="post">
    <input type="text" name="booking_code" placeholder="Enter booking code to test" value="<?php echo htmlspecialchars($_POST['booking_code'] ?? ''); ?>" required>
    <button type="submit" name="test_code">Test This Code</button>
</form>

<div class="info">
    <h3>📝 Test These Known Codes:</h3>
    <ul>
        <li><strong>PP-QRBZE0</strong> (from your database)</li>
        <li><strong>PP-JB0KGE</strong> (from your database)</li>
        <li><strong>PP-JYHF5B</strong> (from your database)</li>
        <li><strong>BBR1-20250816-2871</strong> (the one you're testing)</li>
    </ul>
</div>

</body></html>
