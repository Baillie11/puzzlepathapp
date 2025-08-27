<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Clues Database Setup</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🗄️ Clues Database Setup</h1>";

$action = $_GET['action'] ?? 'show';

try {
    $mainDb = getMainDb();
    
    if ($action === 'create_table') {
        echo "<h2>📋 Creating Clues Table</h2>";
        
        // Create clues table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS wp2s_pp_clues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hunt_id INT NOT NULL,
            clue_order INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            clue_text TEXT NOT NULL,
            task_description TEXT NOT NULL,
            hint_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_hunt_clue (hunt_id, clue_order),
            INDEX idx_hunt_id (hunt_id)
        )";
        
        if ($mainDb->query($createTableSQL)) {
            echo "<div class='success'>✅ Created wp2s_pp_clues table successfully</div>";
        } else {
            throw new Exception("Failed to create table: " . $mainDb->error);
        }
    }
    
    if ($action === 'add_test_data') {
        echo "<h2>🧪 Adding Test Clue Data</h2>";
        
        // Get the Broadbeach event ID (hunt_id = 2 from your data)
        $huntResult = $mainDb->query("SELECT id FROM wp2s_pp_events WHERE hunt_code = 'BBR1'");
        if ($huntResult && $huntResult->num_rows > 0) {
            $hunt = $huntResult->fetch_assoc();
            $hunt_id = $hunt['id'];
            
            echo "<div class='info'>Found Broadbeach event with ID: $hunt_id</div>";
            
            // Clear existing test data for this hunt
            $clearStmt = $mainDb->prepare("DELETE FROM wp2s_pp_clues WHERE hunt_id = ?");
            $clearStmt->bind_param("i", $hunt_id);
            $clearStmt->execute();
            
            // Add test clues
            $test_clues = [
                [1, 'Test Clue 1', 'This is clue 1 from the database', 'Complete task 1', 'Hint for clue 1'],
                [2, 'Test Clue 2', 'This is clue 2 from the database', 'Complete task 2', 'Hint for clue 2'],
                [3, 'Test Clue 3', 'This is clue 3 from the database', 'Complete task 3', 'Hint for clue 3'],
                [4, 'Test Clue 4', 'This is clue 4 from the database', 'Complete task 4', 'Hint for clue 4'],
                [5, 'Test Clue 5', 'This is clue 5 from the database', 'Complete task 5', 'Hint for clue 5'],
                [6, 'Test Clue 6', 'This is clue 6 from the database', 'Complete task 6', 'Hint for clue 6']
            ];
            
            $insertStmt = $mainDb->prepare("
                INSERT INTO wp2s_pp_clues (hunt_id, clue_order, title, clue_text, task_description, hint_text)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $inserted_count = 0;
            foreach ($test_clues as $clue) {
                $insertStmt->bind_param("iissss", $hunt_id, $clue[0], $clue[1], $clue[2], $clue[3], $clue[4]);
                if ($insertStmt->execute()) {
                    $inserted_count++;
                    echo "<div class='success'>✅ Added: {$clue[1]}</div>";
                } else {
                    echo "<div class='error'>❌ Failed to add: {$clue[1]} - " . $insertStmt->error . "</div>";
                }
            }
            
            echo "<div class='success'>🎉 Added $inserted_count test clues successfully!</div>";
            $insertStmt->close();
            
        } else {
            echo "<div class='error'>❌ Could not find Broadbeach event (BBR1) in wp2s_pp_events table</div>";
        }
    }
    
    if ($action === 'show') {
        echo "<h2>📊 Current Database Status</h2>";
        
        // Check if clues table exists
        $tableCheck = $mainDb->query("SHOW TABLES LIKE 'wp2s_pp_clues'");
        if ($tableCheck->num_rows > 0) {
            echo "<div class='success'>✅ wp2s_pp_clues table exists</div>";
            
            // Show clues
            $cluesResult = $mainDb->query("
                SELECT c.*, e.hunt_name, e.title as event_title 
                FROM wp2s_pp_clues c
                LEFT JOIN wp2s_pp_events e ON c.hunt_id = e.id
                ORDER BY c.hunt_id, c.clue_order
            ");
            
            if ($cluesResult && $cluesResult->num_rows > 0) {
                echo "<h3>🎯 Current Clues in Database</h3>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Hunt</th><th>Order</th><th>Title</th><th>Clue Text</th></tr>";
                
                while ($clue = $cluesResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($clue['hunt_name'] ?? 'Unknown') . "</td>";
                    echo "<td>" . $clue['clue_order'] . "</td>";
                    echo "<td>" . htmlspecialchars($clue['title']) . "</td>";
                    echo "<td>" . htmlspecialchars(substr($clue['clue_text'], 0, 50)) . "...</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='info'>📝 No clues found in database</div>";
            }
        } else {
            echo "<div class='error'>❌ wp2s_pp_clues table does not exist</div>";
        }
        
        // Show available actions
        echo "<h2>🔧 Actions</h2>";
        
        if ($tableCheck->num_rows === 0) {
            echo "<button onclick=\"location.href='?action=create_table'\">📋 Create Clues Table</button>";
        } else {
            echo "<div class='success'>Table already exists</div>";
        }
        
        echo "<button onclick=\"location.href='?action=add_test_data'\">🧪 Add Test Clue Data</button>";
        echo "<button onclick=\"location.href='?action=show'\">🔄 Refresh Status</button>";
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
