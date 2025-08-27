<?php
echo "<!DOCTYPE html><html><head><title>Upload Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
.success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style></head><body>";

echo "<h1>🧪 Upload Test - SUCCESS!</h1>";
echo "<div class='success'>✅ This file is working! Your uploads are going to the right place.</div>";

echo "<h2>📂 Current Directory</h2>";
echo "<div class='info'>Files are being uploaded to: <strong>" . __DIR__ . "</strong></div>";

echo "<h2>📋 Files in This Directory</h2>";
$files = scandir('.');
echo "<ul>";
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

echo "<h2>🔍 Looking for Required Files</h2>";
$required_files = [
    'config.php' => 'Database configuration',
    'verify_booking.php' => 'Booking verification', 
    'get_clues.php' => 'Clue loading system',
    'setup_clues_database.php' => 'Database setup tool'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists ($description)</div>";
    } else {
        echo "<div style='color: red; background: #f8f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>❌ $file missing ($description)</div>";
    }
}

echo "<h2>🎯 Next Steps</h2>";
echo "<div class='info'>";
echo "<p>If you see missing files above:</p>";
echo "<ol>";
echo "<li>Upload the missing files to this same directory</li>";
echo "<li>Then try accessing: <strong>setup_clues_database.php</strong></li>";
echo "<li>Make sure all files are in the same folder as this test file</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
