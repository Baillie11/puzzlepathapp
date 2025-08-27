<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Table Structure Check</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>🔍 Table Structure Analysis</h1>";

try {
    $mainDb = getMainDb();
    echo "<div class='success'>✅ Database Connected: " . DB_NAME . "</div>";
    
    // Check all 3 tables and their structures
    $tables = ['wp2s_pp_bookings', 'wp2s_pp_events', 'wp2s_pp_coupons'];
    
    foreach ($tables as $table) {
        echo "<h2>📊 Table: $table</h2>";
        
        // Check if table exists
        $result = $mainDb->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div class='success'>✅ Table exists</div>";
            
            // Get table structure
            $structure = $mainDb->query("DESCRIBE $table");
            if ($structure) {
                echo "<h3>🏗️ Table Structure</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                while ($col = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . $col['Field'] . "</strong></td>";
                    echo "<td>" . $col['Type'] . "</td>";
                    echo "<td>" . $col['Null'] . "</td>";
                    echo "<td>" . $col['Key'] . "</td>";
                    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . $col['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Show sample data
                $sample = $mainDb->query("SELECT * FROM $table LIMIT 3");
                if ($sample && $sample->num_rows > 0) {
                    echo "<h3>📋 Sample Data</h3>";
                    echo "<table>";
                    
                    // Get column names
                    $firstRow = $sample->fetch_assoc();
                    if ($firstRow) {
                        // Header row
                        echo "<tr>";
                        foreach (array_keys($firstRow) as $col) {
                            echo "<th>$col</th>";
                        }
                        echo "</tr>";
                        
                        // First row
                        echo "<tr>";
                        foreach ($firstRow as $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                        
                        // Additional rows
                        while ($row = $sample->fetch_assoc()) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    echo "</table>";
                } else {
                    echo "<div class='info'>📝 No data in table</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ Table does not exist</div>";
        }
        
        echo "<hr>";
    }
    
    $mainDb->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Next Steps</h2>";
echo "<div class='info'>";
echo "<p>Based on the table structure above, we can:</p>";
echo "<ol>";
echo "<li>Identify the correct column names in wp2s_pp_events</li>";
echo "<li>Update verify_booking.php to use the correct column names</li>";
echo "<li>Fix any other references to match your actual database structure</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
