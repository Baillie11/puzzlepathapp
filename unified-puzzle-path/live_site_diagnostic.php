<?php
/**
 * Live Site Diagnostic Tool
 * Quick check to see what's happening on your live site
 */

echo "<!DOCTYPE html><html><head><title>Live Site Diagnostic</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
.warning { color: orange; background: #fff8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>🔍 Live Site Diagnostic</h1>";
echo "<p><strong>Site:</strong> " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>📁 Step 1: Check Required Files</h2>";

// Check if files exist
$required_files = [
    'index.html' => 'Main application file',
    'verify_booking.php' => 'Booking verification endpoint', 
    'test_json.php' => 'Old test endpoint (should exist but not be used)',
    'config-secure.php' => 'Database configuration'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists ($description)</div>";
        
        if ($file === 'index.html') {
            // Check if index.html contains the updated endpoint
            $content = file_get_contents($file);
            if (strpos($content, "fetch('verify_booking.php'") !== false) {
                echo "<div class='success'>✅ index.html is using verify_booking.php endpoint</div>";
            } elseif (strpos($content, "fetch('test_json.php'") !== false) {
                echo "<div class='error'>❌ index.html is still using test_json.php endpoint - FILE NOT UPDATED</div>";
            } else {
                echo "<div class='warning'>⚠️ Cannot determine which endpoint index.html is using</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ $file missing ($description)</div>";
    }
}

echo "<h2>🗄️ Step 2: Database Connection Test</h2>";

// Try to load config and test database
try {
    if (file_exists('config-secure.php')) {
        define('PUZZLE_PATH_ACCESS', true);
        require_once 'config-secure.php';
        
        $mainDb = getMainDb();
        echo "<div class='success'>✅ Database connection successful</div>";
        
        // Check for booking tables
        $possible_tables = ['wp_pp_bookings', 'wp2s_pp_bookings', 'ozbizfin_wp793_pp_bookings'];
        $found_tables = [];
        
        foreach ($possible_tables as $table) {
            $result = $mainDb->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $found_tables[] = $table;
                echo "<div class='success'>✅ Found booking table: $table</div>";
                
                // Show sample data
                $sample = $mainDb->query("SELECT booking_code, customer_name, customer_email, payment_status FROM $table LIMIT 3");
                if ($sample && $sample->num_rows > 0) {
                    echo "<h3>Sample data from $table:</h3>";
                    echo "<table><tr><th>Booking Code</th><th>Customer Name</th><th>Email</th><th>Status</th></tr>";
                    while ($row = $sample->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['booking_code'] ?? 'NULL') . "</td>";
                        echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'NULL') . "</td>";
                        echo "<td>" . htmlspecialchars($row['customer_email'] ?? 'NULL') . "</td>";
                        echo "<td>" . htmlspecialchars($row['payment_status'] ?? 'NULL') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        }
        
        if (empty($found_tables)) {
            echo "<div class='error'>❌ No booking tables found! WordPress plugin may not be installed or activated.</div>";
        }
        
        $mainDb->close();
        
    } else {
        echo "<div class='error'>❌ config-secure.php not found - cannot test database</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>🌐 Step 3: Test Endpoints</h2>";

// Test which endpoint is actually being called
echo "<div class='info'>";
echo "<h3>Quick Test:</h3>";
echo "<p>Open your browser's developer tools (F12), go to the Network tab, then try entering a booking number in your app.</p>";
echo "<p>Look for which file is being called:</p>";
echo "<ul>";
echo "<li><strong>If you see 'test_json.php'</strong> - Your index.html wasn't updated properly</li>";
echo "<li><strong>If you see 'verify_booking.php'</strong> - Good! But there might be a database issue</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔧 Step 4: What to Check Next</h2>";

echo "<div class='info'>";
echo "<h3>If you're still seeing 'Hello Test!':</h3>";
echo "<ol>";
echo "<li><strong>Clear your browser cache</strong> completely</li>";
echo "<li><strong>Check if index.html was uploaded correctly</strong> (see results above)</li>";
echo "<li><strong>Verify booking data exists</strong> in your database (see sample data above)</li>";
echo "<li><strong>Test with a real booking number</strong> that exists in your system</li>";
echo "</ol>";
echo "</div>";

echo "<h2>📋 Current Status Summary</h2>";

if (file_exists('index.html') && strpos(file_get_contents('index.html'), "fetch('verify_booking.php'") !== false) {
    echo "<div class='success'>✅ Frontend is configured correctly</div>";
} else {
    echo "<div class='error'>❌ Frontend needs to be updated - please re-upload index.html</div>";
}

if (file_exists('verify_booking.php')) {
    echo "<div class='success'>✅ Backend endpoint exists</div>";
} else {
    echo "<div class='error'>❌ Backend endpoint missing - please upload verify_booking.php</div>";
}

try {
    if (defined('PUZZLE_PATH_ACCESS') && function_exists('getMainDb')) {
        $testDb = getMainDb();
        echo "<div class='success'>✅ Database connectivity works</div>";
        $testDb->close();
    } else {
        echo "<div class='warning'>⚠️ Database connectivity unknown</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database has issues</div>";
}

echo "</body></html>";
?>
