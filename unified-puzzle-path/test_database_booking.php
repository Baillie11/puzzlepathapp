<?php
/**
 * Test Database Booking Integration
 * This script tests the booking verification with your actual database
 */

// Security: Define access constant before loading config
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

echo "<!DOCTYPE html><html><head><title>Database Booking Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
input { padding: 10px; width: 300px; margin: 5px; }
</style></head><body>";

echo "<h1>🧪 Database Booking Integration Test</h1>";

try {
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database Connected Successfully</div>";
    
    // Test 1: Check which booking table exists
    echo "<h2>📊 Step 1: Check Booking Tables</h2>";
    
    $possible_tables = ['wp_pp_bookings', 'wp2s_pp_bookings'];
    $found_table = null;
    
    foreach ($possible_tables as $table) {
        $result = $mainDb->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ Found table: $table</div>";
            $found_table = $table;
            
            // Check table structure
            $structure = $mainDb->query("DESCRIBE $table");
            if ($structure) {
                echo "<h3>Table Structure for $table</h3>";
                echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($col = $structure->fetch_assoc()) {
                    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
                }
                echo "</table>";
            }
            
            // Show sample data
            $sample = $mainDb->query("SELECT booking_code, customer_name, customer_email, payment_status, created_at FROM $table ORDER BY created_at DESC LIMIT 5");
            if ($sample && $sample->num_rows > 0) {
                echo "<h3>📋 Recent Bookings</h3>";
                echo "<table><tr><th>Booking Code</th><th>Customer Name</th><th>Customer Email</th><th>Status</th><th>Created</th></tr>";
                while ($row = $sample->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['booking_code'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['customer_email'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['payment_status'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='info'>No booking data found in $table</div>";
            }
            break;
        } else {
            echo "<div class='error'>❌ Table not found: $table</div>";
        }
    }
    
    if (!$found_table) {
        echo "<div class='error'>❌ No booking table found! Please check your WordPress plugin is installed and activated.</div>";
        exit;
    }
    
    // Test 2: Interactive booking verification test
    echo "<h2>🔍 Step 2: Test Booking Verification</h2>";
    
    if (isset($_POST['test_booking'])) {
        $test_booking_code = trim($_POST['booking_code']);
        
        echo "<div class='info'>Testing booking code: " . htmlspecialchars($test_booking_code) . "</div>";
        
        // Test the same query used in verify_booking.php
        $stmt = $mainDb->prepare("
            SELECT 
                booking_code, 
                payment_status, 
                participant_names, 
                tickets as participant_count,
                customer_name,
                customer_email,
                created_at
            FROM $found_table
            WHERE booking_code = ? AND payment_status IN ('paid', 'succeeded', 'confirmed', 'complete', 'completed')
            LIMIT 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $test_booking_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $booking = $result->fetch_assoc();
                echo "<div class='success'>✅ Booking Found!</div>";
                
                echo "<h3>Booking Details:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($booking as $field => $value) {
                    echo "<tr><td>$field</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
                
                // Test personalized greeting
                $customer_name = $booking['customer_name'] ?? '';
                if ($customer_name) {
                    $first_name = explode(' ', trim($customer_name))[0];
                    echo "<div class='success'>🎉 Personalized Greeting: Hello $first_name!</div>";
                } else {
                    echo "<div class='info'>No customer name found for personalized greeting</div>";
                }
                
            } else {
                echo "<div class='error'>❌ Booking not found or payment not confirmed</div>";
                echo "<div class='info'>Make sure the booking code exists and payment status is one of: paid, succeeded, confirmed, complete, completed</div>";
            }
            $stmt->close();
        } else {
            echo "<div class='error'>❌ Database query failed: " . $mainDb->error . "</div>";
        }
    }
    
    // Test form
    echo "<form method='post'>";
    echo "<input type='text' name='booking_code' placeholder='Enter booking code (e.g., BB-20250117-1234)' required>";
    echo "<button type='submit' name='test_booking'>Test Booking Verification</button>";
    echo "</form>";
    
    // Test 3: Direct API test
    echo "<h2>🌐 Step 3: Test verify_booking.php Endpoint</h2>";
    echo "<div class='info'>";
    echo "<p>Your app is now configured to use <code>verify_booking.php</code> instead of the test endpoint.</p>";
    echo "<p>Next steps:</p>";
    echo "<ul>";
    echo "<li>1. Test the app by opening <a href='index.html' target='_blank'>index.html</a></li>";
    echo "<li>2. Enter a real booking number from your WordPress booking system</li>";
    echo "<li>3. You should see a personalized greeting like 'Hello Andrew!' instead of 'Hello Test Customer!'</li>";
    echo "</ul>";
    echo "</div>";
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>✨ Summary</h2>";
echo "<div class='success'>";
echo "<h3>What's Been Updated:</h3>";
echo "<ul>";
echo "<li>✅ <strong>verify_booking.php</strong> - Now includes customer_email field and uses correct table name</li>";
echo "<li>✅ <strong>index.html</strong> - Updated to call verify_booking.php instead of test_json.php</li>";
echo "<li>✅ <strong>Database Integration</strong> - App now pulls real customer names and emails from booking database</li>";
echo "<li>✅ <strong>Personalized Greetings</strong> - Front page will show 'Hello [Customer Name]!' based on booking data</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
