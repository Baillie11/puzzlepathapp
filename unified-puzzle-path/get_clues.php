<?php
// Simple test clues endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Get hunt_id from query parameter
$hunt_id = $_GET['hunt_id'] ?? 1;

// Simple test clues data
$test_clues = [
    [
        'id' => 1,
        'title' => 'Welcome to Your Adventure!',
        'clue' => 'This is your first clue. Look around and find the starting point of your quest.',
        'task' => 'Take a photo of yourself at the starting location.',
        'hint' => 'The starting point is usually marked with a sign or landmark.'
    ],
    [
        'id' => 2,
        'title' => 'The Journey Continues',
        'clue' => 'Now that you\'ve started, follow the path to discover the next location.',
        'task' => 'Find the hidden object at the next waypoint.',
        'hint' => 'Look for something that doesn\'t belong in the natural environment.'
    ],
    [
        'id' => 3,
        'title' => 'Getting Warmer',
        'clue' => 'You\'re making great progress! The next clue will test your observation skills.',
        'task' => 'Count how many red objects you can see from this vantage point.',
        'hint' => 'Red objects might include signs, flowers, or painted items.'
    ],
    [
        'id' => 4,
        'title' => 'Halfway There!',
        'clue' => 'You\'ve reached the halfway point. Time for a more challenging puzzle.',
        'task' => 'Solve this riddle: I have keys but no locks. I have space but no room. What am I?',
        'hint' => 'Think about things you use every day for communication.'
    ],
    [
        'id' => 5,
        'title' => 'Almost Done',
        'clue' => 'The end is near! This clue will lead you to the final destination.',
        'task' => 'Find the landmark that has been here the longest.',
        'hint' => 'Look for something historical or with a date marking its age.'
    ],
    [
        'id' => 6,
        'title' => 'The Final Challenge',
        'clue' => 'Congratulations on making it this far! Complete this final task to finish your quest.',
        'task' => 'Take a group photo at the finish line and celebrate your achievement!',
        'hint' => 'The finish line is where you started - but look for the completion marker.'
    ]
];

echo json_encode([
    'success' => true,
    'message' => 'Test clues loaded successfully!',
    'hunt_name' => 'Test Hunt Adventure',
    'total_clues' => count($test_clues),
    'clues' => $test_clues
]);
?>
