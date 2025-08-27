<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $hunt_id = $_GET['hunt_id'] ?? null;
    
    if (!$hunt_id || !is_numeric($hunt_id)) {
        throw new Exception('Valid hunt ID is required');
    }
    
    // Get hunt info from wp2s_pp_events table
    $mainDb = getMainDb();
    $huntStmt = $mainDb->prepare("SELECT hunt_name, title FROM wp2s_pp_events WHERE id = ?");
    $huntStmt->bind_param("i", $hunt_id);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception('Hunt not found');
    }
    
    $hunt = $huntResult->fetch_assoc();
    
    // Hardcoded clues for Broadbeach quest (from your original files)
    $broadbeach_clues = [
        [
            'id' => 1,
            'order' => 1,
            'title' => 'Sound & Sand',
            'clue' => 'Where music meets the sand and grass, A stage stands proud where events do pass. Face the sea, your journey\'s begun — Find the shell that captures sound and sun.',
            'task' => 'Form a \'band\' using air instruments and take a group photo on the amphitheatre stage.',
            'hint' => 'It\'s shaped like a shell and often hosts music or markets.'
        ],
        [
            'id' => 2,
            'order' => 2,
            'title' => 'A Golden Stroll',
            'clue' => 'Walk the path where runners glide, Beside the beach, the ocean\'s tide. Find the exercise zone where you can flex — It\'s part of fitness, not just for pecs.',
            'task' => 'Pick one group member to demonstrate a piece of equipment while others cheer like it\'s the Olympics.',
            'hint' => 'It\'s free, public, and often used by beachgoers.'
        ],
        [
            'id' => 3,
            'order' => 3,
            'title' => 'Tall and Timely',
            'clue' => 'A tower of time with a golden hue, It marks the spot with a coastal view. Count its stories from base to sky, Then turn west and wave goodbye.',
            'task' => 'Snap a group photo with the clock in the background, mid \'freeze-frame\' like you\'re caught in time.',
            'hint' => 'It\'s a tribute to timekeeping, not a hotel.'
        ],
        [
            'id' => 4,
            'order' => 4,
            'title' => 'Literary Escape',
            'clue' => 'Not far from sand, in shade and peace, A place for books where whispers cease. Chairs, not waves, are where you\'ll dwell, Find the tales you know so well.',
            'task' => 'Each team member must find a book that starts with the same letter as their first name and pose like a character from the cover.',
            'hint' => 'It\'s quiet, cool, and full of shelves.'
        ],
        [
            'id' => 5,
            'order' => 5,
            'title' => 'Beneath the Arches',
            'clue' => 'Where arches glow as sunsets fall, The foreshore welcomes one and all. Return to waves and sandy floor, A photo near these lights is lore.',
            'task' => 'Take a creative group beach photo using the arch as a frame — bonus if everyone is jumping mid-air.',
            'hint' => 'The lights come on at dusk and shine bright at night.'
        ],
        [
            'id' => 6,
            'order' => 6,
            'title' => 'Memory Lane',
            'clue' => 'Final stop, near where you began, A playground built for every clan. Look for signs with plaques and dates, Time to tally and test your fates.',
            'task' => 'Find three plaques or signs with dates. Use them in a math equation (e.g., oldest year minus youngest participant\'s age) and submit your answer to unlock the medal.',
            'hint' => 'You passed it at the start. It\'s where fun and inclusivity meet.'
        ]
    ];
    
    // Hardcoded clues for Emerald Park quest
    $emerald_clues = [
        [
            'id' => 7,
            'order' => 1,
            'title' => 'Counting Light',
            'clue' => 'This bridge guides both day and night, With metal posts that give off light. Walk from end to end and count each pole — Not bollards, just the tall light goal.',
            'task' => 'Count the tall light poles along the bridge.',
            'hint' => 'Only count the tall upright light poles — not small ones or ground fixtures.'
        ],
        [
            'id' => 8,
            'order' => 2,
            'title' => 'Steel Strength',
            'clue' => 'Along the lake, where runners train, Stands gear to stretch your back or strain. One has bars in a laddered row — What colour are the handles where your hands would go?',
            'task' => 'Visit the exercise station and check the ladder-like bar handles.',
            'hint' => 'Look near the water\'s edge, past the bridge. It\'s shaped like a ladder.'
        ],
        [
            'id' => 9,
            'order' => 3,
            'title' => 'Carved in Stone',
            'clue' => 'Just off the track, a plaque is found, In stone it rests near the picnic ground. What year is carved to mark the day This place was opened in a formal way?',
            'task' => 'Find the stone plaque near the BBQ area. What year is engraved?',
            'hint' => 'Look low to the ground, near picnic tables or paths.'
        ],
        [
            'id' => 10,
            'order' => 4,
            'title' => 'The Sculpture Watcher',
            'clue' => 'A figure waits, not made of flesh, But metal curves or angles fresh. Find the sculpture near the bend — What is the first word of the plaque at its end?',
            'task' => 'Read the sculpture\'s plaque. What\'s the first word?',
            'hint' => 'The sculpture is near the first major bend in the lake trail.'
        ],
        [
            'id' => 11,
            'order' => 5,
            'title' => 'Colour by Nature',
            'clue' => 'Look to the gardens with planned design, Three flower beds in a tidy line. One red, one white, one violet too — Which flower is planted with the colour blue?',
            'task' => 'Identify the blue-flowering plant. What is it?',
            'hint' => 'Check landscaped flower beds near the lake\'s western side.'
        ],
        [
            'id' => 12,
            'order' => 6,
            'title' => 'Benched With a View',
            'clue' => 'Along the lake, one bench stands proud, Facing the water, away from the crowd. It\'s not curved or in a set — Just one alone — have you found it yet?',
            'task' => 'Find the lone bench with a lake view. Take a selfie there.',
            'hint' => 'Look along the southern lake path for a solo bench facing open water.'
        ],
        [
            'id' => 13,
            'order' => 7,
            'title' => 'Final Fill-Up',
            'clue' => 'Before you finish, hydrate true, There\'s one blue fountain waiting for you. What small raised icon is on the tap — Not letters — just a watery map?',
            'task' => 'Describe the symbol (not text) on the fountain.',
            'hint' => 'Look near shaded areas or fitness spots for the water station.'
        ],
        [
            'id' => 14,
            'order' => 8,
            'title' => 'Hero\'s Seat',
            'clue' => 'With all clues solved, you\'ve reached the end, Your journey\'s done, adventurer friend. One wide bench gives a champion\'s view — Take your photo, the quest is through!',
            'task' => 'Find the largest bench near the starting playground with lake view.',
            'hint' => 'You saw this bench at the start near the All Abilities Playground.'
        ]
    ];
    
    // Determine which clues to return based on hunt name/title
    $hunt_name_lower = strtolower($hunt['hunt_name'] . ' ' . $hunt['title']);
    
    if (strpos($hunt_name_lower, 'broadbeach') !== false || strpos($hunt_name_lower, 'bbr') !== false) {
        $clues = $broadbeach_clues;
        $hunt_display_name = 'Broadbeach Quest';
        $total_clues = 6;
    } elseif (strpos($hunt_name_lower, 'emerald') !== false || strpos($hunt_name_lower, 'ep') !== false) {
        $clues = $emerald_clues;
        $hunt_display_name = 'Emerald Lakes Quest';
        $total_clues = 8;
    } else {
        // Default to Broadbeach if we can't determine
        $clues = $broadbeach_clues;
        $hunt_display_name = $hunt['hunt_name'];
        $total_clues = 6;
    }
    
    echo json_encode([
        'success' => true,
        'hunt_name' => $hunt_display_name,
        'total_clues' => $total_clues,
        'clues' => $clues,
        'debug_info' => [
            'hunt_id' => $hunt_id,
            'db_hunt_name' => $hunt['hunt_name'],
            'db_title' => $hunt['title'],
            'detected_quest' => strpos($hunt_name_lower, 'broadbeach') !== false ? 'Broadbeach' : 'Emerald'
        ]
    ]);
    
    $huntStmt->close();
    $mainDb->close();
    
} catch (Exception $e) {
    logError("Get clues error", [
        'error' => $e->getMessage(),
        'hunt_id' => $hunt_id ?? 'N/A'
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
