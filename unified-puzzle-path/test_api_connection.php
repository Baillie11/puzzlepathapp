<?php
// Test API Connection Script
// Run this file to diagnose connection issues with WordPress REST API

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h1>🔍 WordPress REST API Connection Test</h1>\n";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

// Test 1: Configuration Check
echo "<h2>📋 Configuration Check</h2>\n";
echo "<div class='info'><strong>WordPress API Base URL:</strong> " . WORDPRESS_API_BASE_URL . "</div>\n";

// Test 2: Basic Connectivity Test
echo "<h2>🌐 Basic Connectivity Test</h2>\n";
$test_url = "https://puzzlepath.com.au/wp-json/wp/v2";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => "User-Agent: Puzzle Path Test Script\r\n"
    ]
]);

$test_response = @file_get_contents($test_url, false, $context);

if ($test_response !== false) {
    echo "<div class='success'>✅ WordPress site is accessible via REST API</div>\n";
} else {
    echo "<div class='error'>❌ Cannot reach WordPress REST API</div>\n";
    echo "<div class='info'>Error details: " . json_encode(error_get_last()) . "</div>\n";
}

// Test 3: Check if allow_url_fopen is enabled
echo "<h2>⚙️ PHP Configuration Check</h2>\n";
if (ini_get('allow_url_fopen')) {
    echo "<div class='success'>✅ allow_url_fopen is enabled</div>\n";
} else {
    echo "<div class='error'>❌ allow_url_fopen is disabled - this prevents file_get_contents() from working with URLs</div>\n";
}

// Test 4: Check if cURL is available (alternative method)
if (function_exists('curl_init')) {
    echo "<div class='success'>✅ cURL is available (alternative method)</div>\n";
} else {
    echo "<div class='error'>❌ cURL is not available</div>\n";
}

// Test 5: Test the actual Puzzle Path API endpoint
echo "<h2>🧩 Puzzle Path API Endpoint Test</h2>\n";
$api_url = WORDPRESS_API_BASE_URL . '/booking/TEST-12345';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => "User-Agent: Puzzle Path App\r\nContent-Type: application/json\r\n"
    ]
]);

echo "<div class='info'><strong>Testing URL:</strong> $api_url</div>\n";

$api_response = @file_get_contents($api_url, false, $context);

if ($api_response !== false) {
    echo "<div class='success'>✅ Puzzle Path API endpoint is reachable</div>\n";
    echo "<div class='info'><strong>Response:</strong></div>\n";
    echo "<pre>" . htmlspecialchars($api_response) . "</pre>\n";
} else {
    echo "<div class='error'>❌ Cannot reach Puzzle Path API endpoint</div>\n";
    
    $last_error = error_get_last();
    if ($last_error) {
        echo "<div class='info'><strong>Last PHP Error:</strong> " . htmlspecialchars($last_error['message']) . "</div>\n";
    }
    
    // Test with cURL as alternative
    if (function_exists('curl_init')) {
        echo "<h3>🔄 Testing with cURL (alternative method)</h3>\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Puzzle Path App');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
        
        $curl_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($curl_response !== false && empty($curl_error)) {
            echo "<div class='success'>✅ cURL successfully connected to API</div>\n";
            echo "<div class='info'><strong>HTTP Status:</strong> $http_code</div>\n";
            echo "<div class='info'><strong>Response:</strong></div>\n";
            echo "<pre>" . htmlspecialchars($curl_response) . "</pre>\n";
        } else {
            echo "<div class='error'>❌ cURL also failed</div>\n";
            echo "<div class='info'><strong>cURL Error:</strong> " . htmlspecialchars($curl_error) . "</div>\n";
            echo "<div class='info'><strong>HTTP Status:</strong> $http_code</div>\n";
        }
    }
}

// Test 6: Check HTTP response headers
echo "<h2>📡 HTTP Headers Analysis</h2>\n";
if (isset($http_response_header)) {
    echo "<div class='info'><strong>Response Headers:</strong></div>\n";
    echo "<pre>" . htmlspecialchars(implode("\n", $http_response_header)) . "</pre>\n";
} else {
    echo "<div class='info'>No response headers captured</div>\n";
}

// Test 7: Recommendations
echo "<h2>💡 Recommendations</h2>\n";

if ($test_response === false) {
    echo "<div class='error'>";
    echo "<h3>Connection Issues Detected</h3>";
    echo "<ol>";
    echo "<li><strong>Check WordPress Plugin:</strong> Ensure the Puzzle Path WordPress plugin is installed and activated</li>";
    echo "<li><strong>Verify REST API:</strong> Visit <a href='https://puzzlepath.com.au/wp-json/puzzlepath/v1' target='_blank'>https://puzzlepath.com.au/wp-json/puzzlepath/v1</a> in your browser</li>";
    echo "<li><strong>PHP Configuration:</strong> Contact your hosting provider if allow_url_fopen is disabled</li>";
    echo "<li><strong>Firewall:</strong> Check if your server's firewall is blocking outbound connections</li>";
    echo "<li><strong>SSL Issues:</strong> The WordPress site may have SSL certificate issues</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>Basic connectivity is working</h3>";
    echo "<p>The issue may be specific to the Puzzle Path API endpoints. Check:</p>";
    echo "<ol>";
    echo "<li>WordPress plugin installation and activation</li>";
    echo "<li>Database table structure for bookings</li>";
    echo "<li>Plugin configuration and settings</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>🔧 Quick Fixes to Try</h2>\n";
echo "<div class='info'>";
echo "<h3>Option 1: Use cURL instead of file_get_contents</h3>";
echo "<p>Modify verify_booking.php to use cURL instead of file_get_contents for better compatibility.</p>";

echo "<h3>Option 2: Enable allow_url_fopen</h3>";
echo "<p>Contact your hosting provider to enable allow_url_fopen in PHP configuration.</p>";

echo "<h3>Option 3: Local WordPress Testing</h3>";
echo "<p>Test with a local WordPress installation first to isolate network issues.</p>";
echo "</div>";

?>
