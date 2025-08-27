<?php
// Simple test to see if we can access the verify_booking endpoint
echo "<h1>Simple Verify Booking Test</h1>";

// Check if verify_booking.php file exists
if (file_exists('verify_booking.php')) {
    echo "✅ verify_booking.php file exists<br>";
} else {
    echo "❌ verify_booking.php file NOT found<br>";
}

// Check if config-secure.php exists
if (file_exists('config-secure.php')) {
    echo "✅ config-secure.php file exists<br>";
} else {
    echo "❌ config-secure.php file NOT found<br>";
}

// Test direct POST to verify_booking.php
echo "<h2>Testing Direct POST Request</h2>";

$test_booking_code = 'BB-20250827-5157'; // Your booking code
$post_data = json_encode(['booking_number' => $test_booking_code]);

// Use curl to POST to the same domain
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/puzzlepath-app/unified-puzzle-path/verify_booking.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($post_data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://temp', 'w+'));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Get verbose output
rewind(curl_getinfo($ch, CURLOPT_STDERR));
$verbose_log = stream_get_contents(curl_getinfo($ch, CURLOPT_STDERR));

curl_close($ch);

echo "HTTP Status Code: $http_code<br>";
echo "cURL Error: " . ($error ?: 'None') . "<br>";
echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($verbose_log) {
    echo "<h3>Verbose Log:</h3>";
    echo "<pre>" . htmlspecialchars($verbose_log) . "</pre>";
}

// Also try a GET request to see if the file is accessible at all
echo "<h2>Testing GET Request (should fail with Method not allowed)</h2>";
$get_response = file_get_contents('http://localhost/puzzlepath-app/unified-puzzle-path/verify_booking.php');
echo "<pre>" . htmlspecialchars($get_response) . "</pre>";
?>
