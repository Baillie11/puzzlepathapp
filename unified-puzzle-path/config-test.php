<?php
// Test Configuration for Unified Puzzle Path App
// This file contains dummy data for testing without a real database

// Simulate database connections for testing
function getMainDb() {
    // Return a mock database connection
    return new MockDatabase();
}

function getUserDb() {
    // Return a mock database connection  
    return new MockDatabase();
}

// Mock Database Class for Testing
class MockDatabase {
    private $testData = [
        'hunts' => [
            ['id' => 1, 'hunt_code' => 'BB', 'hunt_name' => 'Broadbeach Quest', 'location' => 'Broadbeach', 'description' => 'Explore vibrant Broadbeach!', 'instructions' => 'Start at the All Abilities Playground and follow the path clockwise.', 'total_clues' => 6, 'estimated_duration' => 90],
            ['id' => 2, 'hunt_code' => 'EP', 'hunt_name' => 'Emerald Lakes Explorer\'s Quest', 'location' => 'Emerald Park', 'description' => 'Discover the beauty of Emerald Lakes!', 'instructions' => 'Start at the All Abilities Playground and follow the path clockwise.', 'total_clues' => 8, 'estimated_duration' => 120]
        ],
        'bookings' => [
            ['booking_code' => 'BB-20250108-1234', 'payment_status' => 'paid', 'participant_names' => 'Test User', 'participant_count' => 2],
            ['booking_code' => 'EP-20250108-5678', 'payment_status' => 'paid', 'participant_names' => 'Test User', 'participant_count' => 1],
            ['booking_code' => 'TEST-20250108-0001', 'payment_status' => 'paid', 'participant_names' => 'Demo User', 'participant_count' => 1]
        ],
        'clues' => [
            // Broadbeach clues
            ['id' => 1, 'hunt_id' => 1, 'clue_order' => 1, 'title' => 'Clue 1: Sound & Sand', 'clue_text' => 'Where music meets the sand and grass,\nA stage stands proud where events do pass.\nFace the sea, your journey\'s begun —\nFind the shell that captures sound and sun.', 'task_description' => 'Form a \'band\' using air instruments and take a group photo on the amphitheatre stage.', 'hint_text' => 'It\'s shaped like a shell and often hosts music or markets.'],
            ['id' => 2, 'hunt_id' => 1, 'clue_order' => 2, 'title' => 'Clue 2: A Golden Stroll', 'clue_text' => 'Walk the path where runners glide,\nBeside the beach, the ocean\'s tide.\nFind the exercise zone where you can flex —\nIt\'s part of fitness, not just for pecs.', 'task_description' => 'Pick one group member to demonstrate a piece of equipment while others cheer like it\'s the Olympics.', 'hint_text' => 'It\'s free, public, and often used by beachgoers.'],
            // Emerald Park clues  
            ['id' => 9, 'hunt_id' => 2, 'clue_order' => 1, 'title' => 'Clue 1: Counting Light', 'clue_text' => 'This bridge guides both day and night,\nWith metal posts that give off light.\nWalk from end to end and count each pole —\nNot bollards, just the tall light goal.', 'task_description' => 'Count the tall light poles along the bridge.', 'hint_text' => 'Only count the tall upright light poles — not small ones or ground fixtures.'],
            ['id' => 10, 'hunt_id' => 2, 'clue_order' => 2, 'title' => 'Clue 2: Steel Strength', 'clue_text' => 'Along the lake, where runners train,\nStands gear to stretch your back or strain.\nOne has bars in a laddered row —\nWhat colour are the handles where your hands would go?', 'task_description' => 'Visit the exercise station and check the ladder-like bar handles.', 'hint_text' => 'Look near the water\'s edge, past the bridge. It\'s shaped like a ladder.']
        ]
    ];
    
    public function prepare($query) {
        return new MockStatement($query, $this->testData);
    }
    
    public function query($query) {
        // Return mock results based on query
        if (strpos($query, 'pp_hunts') !== false) {
            return new MockResult($this->testData['hunts']);
        }
        return new MockResult([]);
    }
    
    public function get_results($query) {
        return $this->testData['hunts'];
    }
    
    public function close() {
        // Mock close
    }
}

class MockStatement {
    private $query;
    private $data;
    private $params = [];
    
    public function __construct($query, $data) {
        $this->query = $query;
        $this->data = $data;
    }
    
    public function bind_param($types, ...$params) {
        $this->params = $params;
    }
    
    public function execute() {
        return true;
    }
    
    public function get_result() {
        // Mock result based on query and params
        if (strpos($this->query, 'wp2s_pp_bookings') !== false || strpos($this->query, 'pp_bookings') !== false) {
            $booking_code = $this->params[0] ?? 'BB-20250108-1234';
            foreach ($this->data['bookings'] as $booking) {
                if ($booking['booking_code'] === $booking_code) {
                    return new MockResult([$booking]);
                }
            }
            return new MockResult([]);
        }
        
        if (strpos($this->query, 'pp_hunts') !== false) {
            $hunt_code = $this->params[0] ?? 'BB';
            foreach ($this->data['hunts'] as $hunt) {
                if ($hunt['hunt_code'] === $hunt_code) {
                    return new MockResult([$hunt]);
                }
            }
            return new MockResult([]);
        }
        
        if (strpos($this->query, 'pp_clues') !== false) {
            $hunt_id = $this->params[0] ?? 1;
            $clues = array_filter($this->data['clues'], function($clue) use ($hunt_id) {
                return $clue['hunt_id'] == $hunt_id;
            });
            return new MockResult(array_values($clues));
        }
        
        return new MockResult([]);
    }
    
    public function close() {
        // Mock close
    }
}

class MockResult {
    private $data;
    private $index = 0;
    
    public function __construct($data) {
        $this->data = is_array($data) ? $data : [$data];
    }
    
    public $num_rows;
    
    public function __get($name) {
        if ($name === 'num_rows') {
            return count($this->data);
        }
        return null;
    }
    
    public function fetch_assoc() {
        if ($this->index < count($this->data)) {
            return $this->data[$this->index++];
        }
        return null;
    }
}

// Helper functions for testing
function extractHuntCodeFromBooking($booking_number) {
    $parts = explode('-', $booking_number);
    if (count($parts) >= 1) {
        return strtoupper($parts[0]);
    }
    return 'BB'; // Default for testing
}

function logError($message, $context = []) {
    error_log("TEST MODE: $message - " . json_encode($context));
}

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Test mode indicator
define('TEST_MODE', true);
define('APP_NAME', 'Puzzle Path (Test Mode)');

echo "<!-- TEST MODE: Using mock data -->\n";
?>
