<?php
/**
 * Booking System Connection Fix Script
 * 
 * This script provides multiple solutions to fix the "Unable to connect to booking system" error
 */

require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Booking Connection Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
button:hover { background: #358a8c; }
</style></head><body>";

echo "<h1>🔧 Booking System Connection Fix</h1>";

$action = $_GET['action'] ?? 'diagnose';

switch ($action) {
    case 'diagnose':
        diagnoseConnection();
        break;
    
    case 'test_curl':
        testCurlConnection();
        break;
        
    case 'test_fallback':
        testFallbackConnection();
        break;
        
    case 'create_offline_mode':
        createOfflineMode();
        break;
        
    case 'check_wordpress':
        checkWordPressPlugin();
        break;
        
    default:
        diagnoseConnection();
}

function diagnoseConnection() {
    echo "<h2>🔍 Diagnosing Connection Issues</h2>";
    
    // Test 1: PHP Configuration
    echo "<h3>1. PHP Configuration</h3>";
    $curl_available = function_exists('curl_init');
    $fopen_enabled = ini_get('allow_url_fopen');
    
    if ($curl_available) {
        echo "<div class='success'>✅ cURL is available</div>";
    } else {
        echo "<div class='error'>❌ cURL is not available</div>";
    }
    
    if ($fopen_enabled) {
        echo "<div class='success'>✅ allow_url_fopen is enabled</div>";
    } else {
        echo "<div class='error'>❌ allow_url_fopen is disabled</div>";
    }
    
    // Test 2: WordPress Site Accessibility
    echo "<h3>2. WordPress Site Test</h3>";
    $wp_url = "https://puzzlepath.com.au/wp-json/wp/v2";
    $api_response = makeApiRequest($wp_url, 'GET', null, 10);
    
    if ($api_response !== false) {
        echo "<div class='success'>✅ WordPress site is reachable</div>";
    } else {
        echo "<div class='error'>❌ Cannot reach WordPress site</div>";
    }
    
    // Test 3: Puzzle Path API
    echo "<h3>3. Puzzle Path API Test</h3>";
    $puzzle_api_url = WORDPRESS_API_BASE_URL . '/booking/TEST-12345';
    $puzzle_response = makeApiRequest($puzzle_api_url, 'GET', null, 10);
    
    if ($puzzle_response !== false) {
        echo "<div class='success'>✅ Puzzle Path API is reachable</div>";
        echo "<div class='info'><strong>Response:</strong> " . htmlspecialchars(substr($puzzle_response, 0, 200)) . "...</div>";
    } else {
        echo "<div class='error'>❌ Cannot reach Puzzle Path API</div>";
    }
    
    // Show available fixes
    echo "<h2>🛠️ Available Fixes</h2>";
    
    echo "<div class='info'>";
    echo "<p>Choose the appropriate fix based on the diagnosis above:</p>";
    
    if (!$curl_available && !$fopen_enabled) {
        echo "<div class='error'><strong>Critical:</strong> Both cURL and allow_url_fopen are unavailable. Contact your hosting provider.</div>";
    }
    
    echo "<button onclick=\"location.href='?action=test_curl'\">🔧 Test cURL Connection</button>";
    echo "<button onclick=\"location.href='?action=test_fallback'\">🔄 Test Fallback Mode</button>";
    echo "<button onclick=\"location.href='?action=create_offline_mode'\">📴 Enable Offline Mode</button>";
    echo "<button onclick=\"location.href='?action=check_wordpress'\">🔍 Check WordPress Plugin</button>";
    
    echo "</div>";
}

function testCurlConnection() {
    echo "<h2>🔧 Testing cURL Connection</h2>";
    
    if (!function_exists('curl_init')) {
        echo "<div class='error'>❌ cURL is not available on this server</div>";
        echo "<div class='info'>Contact your hosting provider to enable cURL extension</div>";
        return;
    }
    
    $test_url = WORDPRESS_API_BASE_URL . '/booking/TEST-12345';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Puzzle Path Test Script');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    curl_close($ch);
    
    echo "<div class='info'><strong>Testing URL:</strong> $test_url</div>";
    echo "<div class='info'><strong>HTTP Status Code:</strong> $http_code</div>";
    
    if ($response !== false && empty($error)) {
        echo "<div class='success'>✅ cURL connection successful!</div>";
        echo "<div class='info'><strong>Response:</strong></div>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    } else {
        echo "<div class='error'>❌ cURL connection failed</div>";
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($error) . "</div>";
    }
    
    echo "<div class='info'><strong>Verbose Log:</strong></div>";
    echo "<pre>" . htmlspecialchars($verbose_log) . "</pre>";
    
    echo "<button onclick=\"location.href='?action=diagnose'\">← Back to Diagnosis</button>";
}

function testFallbackConnection() {
    echo "<h2>🔄 Testing Fallback Connection</h2>";
    
    // Try to connect to local database instead
    try {
        $mainDb = getMainDb();
        
        // Check if wp2s_pp_bookings table exists
        $tableCheck = $mainDb->query("SHOW TABLES LIKE 'wp2s_pp_bookings'");
        
        if ($tableCheck->num_rows > 0) {
            echo "<div class='success'>✅ Found local booking table: wp2s_pp_bookings</div>";
            
            // Create fallback verify_booking.php
            createFallbackBookingScript();
            
        } else {
            echo "<div class='error'>❌ Local booking table not found</div>";
            echo "<div class='info'>The system needs either API access or local booking data</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    }
    
    echo "<button onclick=\"location.href='?action=diagnose'\">← Back to Diagnosis</button>";
}

function createFallbackBookingScript() {
    $fallback_script = '<?php
// Fallback booking verification - uses local database instead of API
require_once \'config.php\';

header(\'Content-Type: application/json\');
header(\'Access-Control-Allow-Origin: *\');
header(\'Access-Control-Allow-Methods: POST\');
header(\'Access-Control-Allow-Headers: Content-Type\');

try {
    $input = json_decode(file_get_contents(\'php://input\'), true);
    
    if (!isset($input[\'booking_number\']) || empty($input[\'booking_number\'])) {
        throw new Exception(\'Booking number is required\');
    }
    
    $booking_number = trim($input[\'booking_number\']);
    $hunt_code = extractHuntCodeFromBooking($booking_number);
    
    if (!$hunt_code) {
        throw new Exception(\'Invalid booking number format. Please check your booking number.\');
    }
    
    // Use local database instead of API
    $mainDb = getMainDb();
    $stmt = $mainDb->prepare("SELECT booking_code, payment_status FROM wp2s_pp_bookings WHERE booking_code = ?");
    $stmt->bind_param("s", $booking_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            \'success\' => false,
            \'message\' => \'Booking number not found. Please check your booking number and try again.\'
        ]);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // Check payment status
    $valid_statuses = [\'paid\', \'succeeded\', \'confirmed\'];
    if (!in_array(strtolower($booking[\'payment_status\']), $valid_statuses)) {
        echo json_encode([
            \'success\' => false,
            \'message\' => \'Booking found but payment is not confirmed. Please contact support if you believe this is an error.\'
        ]);
        exit;
    }
    
    // Get hunt information
    $huntStmt = $mainDb->prepare("SELECT id, hunt_name, location, description, instructions, total_clues, estimated_duration FROM pp_hunts WHERE hunt_code = ? AND is_active = TRUE");
    $huntStmt->bind_param("s", $hunt_code);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception(\'Hunt not found or not active. Hunt code: \' . $hunt_code);
    }
    
    $hunt = $huntResult->fetch_assoc();
    
    echo json_encode([
        \'success\' => true,
        \'message\' => \'Booking verified successfully! Get ready for your \' . $hunt[\'hunt_name\'] . \' adventure!\',
        \'booking_data\' => [
            \'booking_code\' => $booking[\'booking_code\']
        ],
        \'hunt_data\' => [
            \'hunt_id\' => $hunt[\'id\'],
            \'hunt_code\' => $hunt_code,
            \'hunt_name\' => $hunt[\'hunt_name\'],
            \'location\' => $hunt[\'location\'],
            \'description\' => $hunt[\'description\'],
            \'instructions\' => $hunt[\'instructions\'],
            \'total_clues\' => $hunt[\'total_clues\'],
            \'estimated_duration\' => $hunt[\'estimated_duration\']
        ]
    ]);
    
    $stmt->close();
    $huntStmt->close();
    $mainDb->close();
    
} catch (Exception $e) {
    logError("Fallback booking verification error", [
        \'error\' => $e->getMessage(),
        \'booking_number\' => $booking_number ?? \'N/A\'
    ]);
    
    echo json_encode([
        \'success\' => false,
        \'message\' => \'Error: \' . $e->getMessage()
    ]);
}
?>';

    // Save fallback script
    $fallback_file = 'verify_booking_fallback.php';
    if (file_put_contents($fallback_file, $fallback_script)) {
        echo "<div class='success'>✅ Created fallback booking script: $fallback_file</div>";
        echo "<div class='info'>To use fallback mode, rename this file to verify_booking.php</div>";
        
        echo "<div class='warning'>";
        echo "<h4>⚠️ Important Notes:</h4>";
        echo "<ul>";
        echo "<li>Fallback mode uses local database only</li>";
        echo "<li>Booking data may not be as current as API data</li>";
        echo "<li>Make sure your local booking table is up to date</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Failed to create fallback script</div>";
    }
}

function createOfflineMode() {
    echo "<h2>📴 Creating Offline Mode</h2>";
    
    echo "<div class='info'>";
    echo "<p>Offline mode allows testing without API connectivity by creating sample bookings in the local database.</p>";
    echo "</div>";
    
    try {
        $mainDb = getMainDb();
        
        // Create sample bookings for testing
        $sample_bookings = [
            ['BB-20250117-1234', 'Paid', 'BB'],
            ['EP-20250117-5678', 'Paid', 'EP'],
            ['TEST-20250117-0001', 'Paid', 'BB']
        ];
        
        $created_count = 0;
        foreach ($sample_bookings as $booking) {
            list($code, $status, $hunt_code) = $booking;
            
            // Check if booking exists
            $checkStmt = $mainDb->prepare("SELECT booking_code FROM wp2s_pp_bookings WHERE booking_code = ?");
            $checkStmt->bind_param("s", $code);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows == 0) {
                // Insert sample booking
                $insertStmt = $mainDb->prepare("INSERT INTO wp2s_pp_bookings (booking_code, payment_status) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $code, $status);
                
                if ($insertStmt->execute()) {
                    echo "<div class='success'>✅ Created sample booking: $code</div>";
                    $created_count++;
                } else {
                    echo "<div class='error'>❌ Failed to create booking: $code</div>";
                }
                $insertStmt->close();
            } else {
                echo "<div class='info'>ℹ️ Booking already exists: $code</div>";
            }
            $checkStmt->close();
        }
        
        if ($created_count > 0) {
            echo "<div class='success'>✅ Created $created_count sample bookings for testing</div>";
            
            // Generate fallback script
            createFallbackBookingScript();
            
            echo "<div class='info'>";
            echo "<h4>Test Bookings Available:</h4>";
            echo "<ul>";
            foreach ($sample_bookings as $booking) {
                echo "<li><strong>{$booking[0]}</strong> - {$booking[2]} Hunt</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Failed to create offline mode: " . $e->getMessage() . "</div>";
    }
    
    echo "<button onclick=\"location.href='?action=diagnose'\">← Back to Diagnosis</button>";
}

function checkWordPressPlugin() {
    echo "<h2>🔍 Checking WordPress Plugin Status</h2>";
    
    // Test various WordPress endpoints to see if plugin is active
    $endpoints_to_test = [
        '/wp-json/wp/v2' => 'WordPress Core API',
        '/wp-json/puzzlepath/v1' => 'Puzzle Path Plugin API',
        '/wp-json/puzzlepath/v1/hunts' => 'Puzzle Path Hunts Endpoint',
        '/wp-json/puzzlepath/v1/bookings' => 'Puzzle Path Bookings Endpoint'
    ];
    
    $base_url = 'https://puzzlepath.com.au';
    
    foreach ($endpoints_to_test as $endpoint => $description) {
        $url = $base_url . $endpoint;
        $response = makeApiRequest($url, 'GET', null, 10);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if (strpos($endpoint, 'puzzlepath') !== false) {
                echo "<div class='success'>✅ $description is working</div>";
            } else {
                echo "<div class='info'>ℹ️ $description is accessible</div>";
            }
            
            if ($data && isset($data['code'])) {
                echo "<div class='info'>Response code: " . $data['code'] . "</div>";
            }
        } else {
            if (strpos($endpoint, 'puzzlepath') !== false) {
                echo "<div class='error'>❌ $description is not accessible</div>";
                echo "<div class='warning'>This suggests the Puzzle Path WordPress plugin may not be installed or activated</div>";
            } else {
                echo "<div class='error'>❌ $description is not accessible</div>";
            }
        }
    }
    
    echo "<div class='info'>";
    echo "<h4>If Puzzle Path Plugin endpoints are failing:</h4>";
    echo "<ol>";
    echo "<li>Log into your WordPress admin panel</li>";
    echo "<li>Go to Plugins → Installed Plugins</li>";
    echo "<li>Make sure 'Puzzle Path Booking System' is activated</li>";
    echo "<li>Check the plugin settings and configuration</li>";
    echo "<li>Verify that the booking database tables exist</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<button onclick=\"location.href='?action=diagnose'\">← Back to Diagnosis</button>";
}

echo "</body></html>";
?>
