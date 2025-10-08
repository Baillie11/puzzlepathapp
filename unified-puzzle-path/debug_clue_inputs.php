<?php
/**
 * Debug Clue Input Types
 * Check what's happening with clue input_type values
 */

echo "<!DOCTYPE html><html><head><title>Clue Input Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
.info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
.warning { color: orange; background: #fff8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.highlight { background-color: #ffeb3b; font-weight: bold; }
</style></head><body>";

echo "<h1>🐛 Clue Input Type Debug</h1>";

try {
    define('PUZZLE_PATH_ACCESS', true);
    require_once 'config-secure.php';
    
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check if clues table exists
    echo "<h2>📋 Clues Table Structure</h2>";
    $tables = ['wp2s_pp_clues', 'pp_clues'];
    $clue_table = null;
    
    foreach ($tables as $table) {
        $check = $mainDb->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            $clue_table = $table;
            echo "<div class='success'>✅ Found clues table: $table</div>";
            break;
        }
    }
    
    if (!$clue_table) {
        echo "<div class='error'>❌ No clues table found</div>";
        echo "<div class='warning'>⚠️ This explains the issue! The system is trying to load clues from database but no clues table exists.</div>";
        
        echo "<h2>🔧 Possible Solutions</h2>";
        echo "<div class='info'>";
        echo "<h3>Option 1: Use hardcoded clues temporarily</h3>";
        echo "<p>Rename <code>get_clues_hardcoded.php</code> to <code>get_clues.php</code> and add input_type fields.</p>";
        
        echo "<h3>Option 2: Create clues table</h3>";
        echo "<p>Run the database setup script to create wp2s_pp_clues table.</p>";
        echo "</div>";
        
    } else {
        // Analyze clues table
        $structure = $mainDb->query("DESCRIBE $clue_table");
        echo "<h3>🏗️ Table Structure: $clue_table</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        
        $has_input_type = false;
        while ($col = $structure->fetch_assoc()) {
            if ($col['Field'] == 'input_type') {
                $has_input_type = true;
            }
            
            $highlight = ($col['Field'] == 'input_type') ? ' class="highlight"' : '';
            echo "<tr$highlight>";
            echo "<td><strong>" . $col['Field'] . "</strong></td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$has_input_type) {
            echo "<div class='error'>❌ Missing 'input_type' column! This is the problem.</div>";
            echo "<div class='warning'>⚠️ The clues table exists but doesn't have the input_type field needed for input fields.</div>";
        } else {
            echo "<div class='success'>✅ input_type column exists</div>";
        }
        
        // Get sample clues
        echo "<h2>📋 Sample Clues Data</h2>";
        $clues = $mainDb->query("SELECT * FROM $clue_table LIMIT 5");
        
        if ($clues && $clues->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Task</th><th>Input Type</th><th>Required Answer</th></tr>";
            
            while ($clue = $clues->fetch_assoc()) {
                $input_type_highlight = (empty($clue['input_type']) || $clue['input_type'] == 'none') ? ' class="highlight"' : '';
                echo "<tr>";
                echo "<td>" . $clue['id'] . "</td>";
                echo "<td>" . htmlspecialchars($clue['title'] ?? 'No title') . "</td>";
                echo "<td>" . htmlspecialchars(substr($clue['task_description'] ?? 'No task', 0, 50)) . "...</td>";
                echo "<td$input_type_highlight>" . ($clue['input_type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($clue['required_answer'] ?? 'No answer') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div class='info'>";
            echo "<h3>🔍 Analysis</h3>";
            echo "<ul>";
            echo "<li>Yellow highlighting shows clues with missing or 'none' input_type</li>";
            echo "<li>Clues that ask users to 'type', 'enter', or 'write' should have input_type='text'</li>";
            echo "<li>Clues that ask for photos should have input_type='photo'</li>";
            echo "<li>Clues that just need completion marking should have input_type='none'</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ No clues found in table</div>";
        }
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>🔧 Quick Fix Options</h2>";
echo "<div class='info'>";
echo "<h3>Immediate Solutions:</h3>";
echo "<ol>";
echo "<li><strong>Database Fix:</strong> Update clues to have correct input_type values</li>";
echo "<li><strong>Temporary Fix:</strong> Use hardcoded clues with proper input_type fields</li>";
echo "<li><strong>JavaScript Override:</strong> Add logic to detect input needed from task text</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>