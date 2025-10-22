<?php
/**
 * Puzzle Path Quest - Registration Handler
 * Handles user registration form submissions
 */

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Validate password strength (basic)
    if (strlen($data['password']) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Extract user data
    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = trim($data['email']);
    $password = $data['password'];
    $completionData = $data['completionData'] ?? [];
    
    // For testing/development - simulate successful registration
    // In production, this would:
    // 1. Hash the password
    // 2. Store user in database
    // 3. Create user session
    // 4. Award medal/achievement
    
    // Simulate processing delay
    usleep(500000); // 0.5 second delay
    
    // Log registration attempt (for development)
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'registration',
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'completionData' => $completionData,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log to file for development purposes
    error_log("REGISTRATION: " . json_encode($logData) . "\n", 3, 'registration.log');
    
    // Store user session data for dashboard
    session_start();
    $userId = rand(1000, 9999); // Simulated user ID
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    $_SESSION['user_email'] = $email;
    $_SESSION['completion_data'] = $completionData; // Pass completion data to dashboard
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Welcome aboard, $firstName! Your achievement has been recorded and your account has been created successfully.",
        'data' => [
            'userId' => $userId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'registrationDate' => date('Y-m-d H:i:s'),
            'medalAwarded' => !empty($completionData),
            'completionData' => $completionData
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log error for debugging
    error_log("REGISTRATION ERROR: " . $e->getMessage() . " - Data: " . json_encode($data ?? 'no data'), 3, 'registration_errors.log');
}

// For production database integration, you would add:
/*
// Include config file
require_once 'config-local.php'; // or appropriate config

// Database operations
try {
    $db = getMainDb();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email address already registered');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
    $stmt->execute();
    $userId = $db->insert_id;
    
    // If completion data exists, award medal/achievement
    if (!empty($completionData)) {
        $stmt = $db->prepare("INSERT INTO user_achievements (user_id, hunt_id, completion_time, completion_date, medal_earned) VALUES (?, ?, ?, ?, 1)");
        $huntId = $completionData['huntId'] ?? 1;
        $completionTime = $completionData['completionTime'] ?? 0;
        $completionDate = $completionData['completionDate'] ?? date('Y-m-d H:i:s');
        $stmt->bind_param("iiis", $userId, $huntId, $completionTime, $completionDate);
        $stmt->execute();
    }
    
    // Create session or return auth token
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    
} catch (mysqli_sql_exception $e) {
    throw new Exception('Registration failed. Please try again.');
}
*/
?>
