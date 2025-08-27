<?php
// Test script to debug booking verification issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PuzzlePath Booking Debug Test</h1>";

// Test 1: Database connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    $conn = new mysqli('localhost', 'wpuser', 'wp123456', 'puzzlepath_wp');
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error;
    } else {
        echo "✅ Database connection successful<br>";
        echo "MySQL version: " . $conn->server_info . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
}

// Test 2: Check if tables exist
echo "<h2>2. Checking Tables</h2>";
$tables = ['wp_pp_bookings', 'wp_pp_events'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "<details><summary>Table structure for $table</summary>";
        echo "<pre>";
        while ($row = $structure->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
        }
        echo "</pre></details>";
        
        // Show row count
        $count = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count_row = $count->fetch_assoc();
        echo "Row count: " . $count_row['count'] . "<br>";
        
    } else {
        echo "❌ Table '$table' does not exist<br>";
    }
}

// Test 3: Show recent bookings
echo "<h2>3. Recent Bookings</h2>";
$bookings = $conn->query("SELECT booking_code, customer_name, payment_status, created_at FROM wp_pp_bookings ORDER BY created_at DESC LIMIT 5");
if ($bookings && $bookings->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Booking Code</th><th>Customer</th><th>Status</th><th>Created</th></tr>";
    while ($row = $bookings->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['booking_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No bookings found or query failed<br>";
}

// Test 4: Test verify_booking.php endpoint
echo "<h2>4. Testing verify_booking.php endpoint</h2>";
$test_booking_code = 'BB-20250827-5157'; // Replace with your actual booking code

echo "Testing with booking code: $test_booking_code<br>";

// Simulate the AJAX request
$url = 'http://localhost/puzzlepath-app/unified-puzzle-path/verify_booking.php';
$data = json_encode(['booking_number' => $test_booking_code]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
if ($error) {
    echo "❌ cURL Error: $error<br>";
} else {
    echo "✅ Response received<br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test 5: Check config-secure.php constants
echo "<h2>5. Configuration Check</h2>";
define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (empty(DB_PASS) ? 'EMPTY' : 'SET (length: ' . strlen(DB_PASS) . ')') . "<br>";

$conn->close();
?>
