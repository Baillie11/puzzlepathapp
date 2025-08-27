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
    
    $mainDb = getMainDb();
    
    // Verify hunt exists and is active
    $huntStmt = $mainDb->prepare("SELECT hunt_name, total_clues FROM pp_hunts WHERE id = ? AND is_active = TRUE");
    $huntStmt->bind_param("i", $hunt_id);
    $huntStmt->execute();
    $huntResult = $huntStmt->get_result();
    
    if ($huntResult->num_rows === 0) {
        throw new Exception('Hunt not found or not active');
    }
    
    $hunt = $huntResult->fetch_assoc();
    
    // Fetch all clues for this hunt, ordered by clue_order
    $cluesStmt = $mainDb->prepare("
        SELECT id, clue_order, title, clue_text, task_description, hint_text 
        FROM pp_clues 
        WHERE hunt_id = ? 
        ORDER BY clue_order ASC
    ");
    $cluesStmt->bind_param("i", $hunt_id);
    $cluesStmt->execute();
    $cluesResult = $cluesStmt->get_result();
    
    $clues = [];
    while ($row = $cluesResult->fetch_assoc()) {
        $clues[] = [
            'id' => $row['id'],
            'order' => $row['clue_order'],
            'title' => $row['title'],
            'clue' => $row['clue_text'],
            'task' => $row['task_description'],
            'hint' => $row['hint_text']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'hunt_name' => $hunt['hunt_name'],
        'total_clues' => $hunt['total_clues'],
        'clues' => $clues
    ]);
    
    $huntStmt->close();
    $cluesStmt->close();
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
