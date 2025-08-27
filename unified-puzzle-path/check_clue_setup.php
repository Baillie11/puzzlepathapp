<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Clue System Setup</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>🧩 Clue System Analysis</h1>";

try {
    $mainDb = getMainDb();
    
    // Check what tables exist
    echo "<h2>📊 Existing Tables</h2>";
    $tables = $mainDb->query("SHOW TABLES");
    $table_list = [];
    
    while ($table = $tables->fetch_array()) {
        $table_list[] = $table[0];
    }
    
    echo "<div class='info'>Tables in your database:</div>";
    echo "<ul>";
    foreach ($table_list as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check if clue tables exist
    echo "<h2>🎯 Clue Storage Analysis</h2>";
    
    $clue_tables = ['pp_clues', 'wp2s_pp_clues'];
    $clue_table_found = null;
    
    foreach ($clue_tables as $table) {
        if (in_array($table, $table_list)) {
            $clue_table_found = $table;
            echo "<div class='success'>✅ Found clue table: $table</div>";
            break;
        }
    }
    
    if (!$clue_table_found) {
        echo "<div class='error'>❌ No clue tables found</div>";
        echo "<div class='info'>";
        echo "<h3>💡 Solutions:</h3>";
        echo "<p>You need to store your clues somewhere. Options:</p>";
        echo "<ol>";
        echo "<li><strong>Create a new clues table</strong> (wp2s_pp_clues)</li>";
        echo "<li><strong>Add clue data to existing wp2s_pp_events table</strong></li>";
        echo "<li><strong>Create hardcoded clues in PHP</strong> (quick solution)</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        // Check clue table structure
        $structure = $mainDb->query("DESCRIBE $clue_table_found");
        echo "<h3>🏗️ Clue Table Structure: $clue_table_found</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Description</th></tr>";
        
        while ($col = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . $col['Field'] . "</strong></td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>"; 
            // Add descriptions for common fields
            switch($col['Field']) {
                case 'hunt_id': echo "Links to hunt/event"; break;
                case 'clue_order': echo "Order of clue (1, 2, 3...)"; break;
                case 'title': echo "Clue title"; break;
                case 'clue_text': echo "The actual clue"; break;
                case 'task_description': echo "What to do"; break;
                case 'hint_text': echo "Hint for clue"; break;
                default: echo ""; break;
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for sample data
        $sample = $mainDb->query("SELECT * FROM $clue_table_found LIMIT 3");
        if ($sample && $sample->num_rows > 0) {
            echo "<h3>📋 Sample Clue Data</h3>";
            echo "<p>Found " . $sample->num_rows . " clue(s):</p>";
            
            while ($clue = $sample->fetch_assoc()) {
                echo "<div class='info'>";
                echo "<strong>Clue " . ($clue['clue_order'] ?? 'N/A') . ":</strong> ";
                echo htmlspecialchars($clue['title'] ?? $clue['clue_text'] ?? 'No title');
                echo "</div>";
            }
        } else {
            echo "<div class='error'>❌ No clues found in table</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Quick Fix Options</h2>";
echo "<div class='info'>";
echo "<h3>Option 1: Create Hardcoded Clues (Fastest)</h3>";
echo "<p>I can create a version of get_clues.php that returns hardcoded Broadbeach clues to get you running immediately.</p>";

echo "<h3>Option 2: Create Clues Table</h3>";
echo "<p>Create a wp2s_pp_clues table and populate it with your quest clues.</p>";

echo "<h3>Option 3: Add Clues to Events Table</h3>";
echo "<p>Add clue fields to your existing wp2s_pp_events table.</p>";
echo "</div>";

echo "</body></html>";
?>
