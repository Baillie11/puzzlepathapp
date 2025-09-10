<?php
// Mark a booking as used (redeemed) so it cannot be reused

define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['booking_code'])) {
        throw new Exception('Missing booking_code');
    }

    $booking_code = validateInput($input['booking_code'], 'booking_number');
    if (!$booking_code) {
        throw new Exception('Invalid booking code');
    }

    $db = getMainDb();

    // Ensure redemptions table exists
    $db->query("CREATE TABLE IF NOT EXISTS wp2s_pp_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_code VARCHAR(50) UNIQUE NOT NULL,
        redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert redemption if not exists
    $stmt = $db->prepare("INSERT IGNORE INTO wp2s_pp_redemptions (booking_code) VALUES (?)");
    $stmt->bind_param('s', $booking_code);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        throw new Exception('Failed to mark booking as used');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

