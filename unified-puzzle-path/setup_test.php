<?php
/**
 * Quick Setup and Testing Script for Unified Puzzle Path
 * Run this file to verify your installation and populate test data
 */

require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Puzzle Path Setup</title><style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
h1 { color: #333; border-bottom: 2px solid #4ca6a8; padding-bottom: 10px; }
h2 { color: #4ca6a8; margin-top: 30px; }
</style></head><body>";

echo "<h1>🚀 Puzzle Path Unified App Setup</h1>";

// Test database connections
echo "<h2>📊 Database Connection Tests</h2>";

try {
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Main database connection: SUCCESS</div>";
    
    $userDb = getUserDb();
    echo "<div class='success'>✅ User database connection: SUCCESS</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Please check your database credentials in config.php</div>";
    exit;
}

// Check for existing tables
echo "<h2>🗄️ Table Structure Check</h2>";

$tables_to_check = [
    'pp_hunts' => 'Hunt definitions',
    'pp_clues' => 'Quest clues',
    'pp_medals' => 'Medal system',
    'wp2s_pp_bookings' => 'Booking system (existing)'
];

foreach ($tables_to_check as $table => $description) {
    $result = $mainDb->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Table '$table' exists ($description)</div>";
    } else {
        echo "<div class='error'>❌ Table '$table' missing ($description)</div>";
        if ($table != 'wp2s_pp_bookings') {
            echo "<div class='info'>Run the database_schema.sql file to create this table</div>";
        }
    }
}

// Check for sample data
echo "<h2>📝 Sample Data Check</h2>";

$huntResult = $mainDb->query("SELECT COUNT(*) as count FROM pp_hunts");
if ($huntResult) {
    $huntCount = $huntResult->fetch_assoc()['count'];
    if ($huntCount > 0) {
        echo "<div class='success'>✅ Found $huntCount hunt(s) in database</div>";
        
        // Show hunt details
        $hunts = $mainDb->query("SELECT hunt_code, hunt_name, location, total_clues FROM pp_hunts ORDER BY id");
        echo "<pre>";
        echo "Available Hunts:\n";
        echo str_pad("CODE", 6) . str_pad("NAME", 25) . str_pad("LOCATION", 20) . "CLUES\n";
        echo str_repeat("-", 60) . "\n";
        while ($hunt = $hunts->fetch_assoc()) {
            echo str_pad($hunt['hunt_code'], 6) . 
                 str_pad(substr($hunt['hunt_name'], 0, 24), 25) . 
                 str_pad(substr($hunt['location'], 0, 19), 20) . 
                 $hunt['total_clues'] . "\n";
        }
        echo "</pre>";
        
    } else {
        echo "<div class='error'>❌ No hunts found in database</div>";
        echo "<div class='info'>Run the populate_clues.sql file to add sample hunt data</div>";
    }
} else {
    echo "<div class='error'>❌ Cannot query hunts table</div>";
}

// Test booking number parsing
echo "<h2>🔍 Booking Number Testing</h2>";

$test_bookings = ['BB-20250101-1234', 'EP-20250201-5678', 'INVALID-123', 'BB123', 'EMERALD-456'];

echo "<pre>";
echo "Testing booking number parsing:\n";
echo str_pad("BOOKING NUMBER", 20) . "EXTRACTED CODE\n";
echo str_repeat("-", 35) . "\n";

foreach ($test_bookings as $booking) {
    $code = extractHuntCodeFromBooking($booking);
    echo str_pad($booking, 20) . ($code ?? 'NULL') . "\n";
}
echo "</pre>";

// File permissions check
echo "<h2>📁 File Permissions Check</h2>";

$files_to_check = [
    '.' => 'Current directory (for error.log)',
    'index.html' => 'Main application file',
    'config.php' => 'Configuration file',
    'verify_booking.php' => 'Booking API',
    'puzzlepath-logo-web.png' => 'Logo image'
];

foreach ($files_to_check as $file => $description) {
    if ($file == '.') {
        if (is_writable('.')) {
            echo "<div class='success'>✅ Directory is writable for error logging</div>";
        } else {
            echo "<div class='error'>❌ Directory not writable - error logging may fail</div>";
        }
    } else {
        if (file_exists($file)) {
            if (is_readable($file)) {
                echo "<div class='success'>✅ $file is readable ($description)</div>";
            } else {
                echo "<div class='error'>❌ $file not readable ($description)</div>";
            }
        } else {
            echo "<div class='error'>❌ $file missing ($description)</div>";
        }
    }
}

// API endpoint testing
echo "<h2>🌐 API Endpoint Testing</h2>";

$endpoints = [
    'verify_booking.php' => 'Booking verification',
    'get_clues.php' => 'Clue loading',
    'track_quest.php' => 'Quest tracking'
];

foreach ($endpoints as $endpoint => $description) {
    if (file_exists($endpoint)) {
        echo "<div class='success'>✅ $endpoint available ($description)</div>";
    } else {
        echo "<div class='error'>❌ $endpoint missing ($description)</div>";
    }
}

// Create test booking if needed
echo "<h2>🧪 Test Booking Creation</h2>";

$test_booking_code = 'TEST-' . date('Ymd') . '-0001';

// Check if test booking exists
$testBookingCheck = $mainDb->prepare("SELECT booking_code FROM wp2s_pp_bookings WHERE booking_code = ?");
$testBookingCheck->bind_param("s", $test_booking_code);
$testBookingCheck->execute();
$result = $testBookingCheck->get_result();

if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Test booking '$test_booking_code' already exists</div>";
} else {
    // Try to create test booking (may fail if table doesn't exist)
    try {
        $createTestBooking = $mainDb->prepare("
            INSERT INTO wp2s_pp_bookings (booking_code, payment_status, hunt_id) 
            VALUES (?, 'Paid', 1)
        ");
        $createTestBooking->bind_param("s", $test_booking_code);
        
        if ($createTestBooking->execute()) {
            echo "<div class='success'>✅ Created test booking: '$test_booking_code'</div>";
            echo "<div class='info'>You can use this booking number to test the application</div>";
        } else {
            echo "<div class='error'>❌ Failed to create test booking</div>";
        }
        $createTestBooking->close();
    } catch (Exception $e) {
        echo "<div class='error'>❌ Cannot create test booking: " . $e->getMessage() . "</div>";
        echo "<div class='info'>This is normal if the booking table structure hasn't been updated yet</div>";
    }
}

$testBookingCheck->close();

// Summary and next steps
echo "<h2>📋 Next Steps</h2>";

echo "<div class='info'>";
echo "<h3>To complete setup:</h3>";
echo "<ol>";
echo "<li>If any database tables are missing, run: <code>database_schema.sql</code></li>";
echo "<li>If no hunt data exists, run: <code>populate_clues.sql</code></li>";
echo "<li>Update <code>config.php</code> with your actual database credentials</li>";
echo "<li>Test the application by visiting <code>index.html</code></li>";
echo "<li>Use booking number format: <code>BB-YYYYMMDD-XXXX</code> or <code>EP-YYYYMMDD-XXXX</code></li>";
echo "</ol>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>✨ Features Available:</h3>";
echo "<ul>";
echo "<li>🎯 Multi-quest support (Broadbeach & Emerald Park)</li>";
echo "<li>📱 Mobile-responsive design with progress tracking</li>";
echo "<li>🏆 Automatic medal system</li>";
echo "<li>👥 User registration and leaderboards</li>";
echo "<li>📊 Quest completion analytics</li>";
echo "<li>🔒 Secure booking verification</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🚀 Ready to Test?</h3>";
echo "<p>Visit <a href='index.html' target='_blank' style='color: #4ca6a8; font-weight: bold;'>index.html</a> to start testing your unified Puzzle Path app!</p>";
if (isset($test_booking_code)) {
    echo "<p>Use test booking: <strong>$test_booking_code</strong></p>";
}
echo "</div>";

// Close database connections
$mainDb->close();
$userDb->close();

echo "</body></html>";
?>
