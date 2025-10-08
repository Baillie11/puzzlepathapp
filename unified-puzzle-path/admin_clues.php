<?php
/**
 * Simple Clue Management Interface
 * Allows adding/editing clues and setting input types
 */

// Basic security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'puzzle_admin_2024') {
    die('Unauthorized access');
}

define('PUZZLE_PATH_ACCESS', true);
require_once 'config-secure.php';

// Handle form submissions
if ($_POST) {
    try {
        $db = getMainDb();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_clue':
                    $answer_options = null;
                    if ($_POST['input_type'] === 'multiple_choice' && !empty($_POST['answer_options'])) {
                        $options = [];
                        $option_lines = explode("\n", $_POST['answer_options']);
                        foreach ($option_lines as $line) {
                            $line = trim($line);
                            if ($line && strpos($line, ':') !== false) {
                                list($key, $value) = explode(':', $line, 2);
                                $options[trim($key)] = trim($value);
                            }
                        }
                        if (!empty($options)) {
                            $answer_options = json_encode($options);
                        }
                    }
                    
                    // Handle NULL values properly
                    $min_value = !empty($_POST['min_value']) ? $_POST['min_value'] : null;
                    $max_value = !empty($_POST['max_value']) ? $_POST['max_value'] : null;
                    
                    // Build the SQL dynamically to handle NULL properly
                    if ($answer_options === null) {
                        $stmt = $db->prepare("
                            INSERT INTO wp2s_pp_clues 
                            (hunt_id, clue_order, title, clue_text, task_description, hint_text, 
                             answer, required_answer, input_type, is_case_sensitive, 
                             answer_options, min_value, max_value, photo_required, auto_advance, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 1)
                        ");
                        $stmt->bind_param("iisssssssisissii", 
                            $_POST['hunt_id'],
                            $_POST['clue_order'],
                            $_POST['title'],
                            $_POST['clue_text'],
                            $_POST['task_description'],
                            $_POST['hint_text'],
                            $_POST['answer'],
                            $_POST['required_answer'],
                            $_POST['input_type'],
                            isset($_POST['is_case_sensitive']) ? 1 : 0,
                            $min_value,
                            $max_value,
                            isset($_POST['photo_required']) ? 1 : 0,
                            isset($_POST['auto_advance']) ? 1 : 0
                        );
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO wp2s_pp_clues 
                            (hunt_id, clue_order, title, clue_text, task_description, hint_text, 
                             answer, required_answer, input_type, is_case_sensitive, 
                             answer_options, min_value, max_value, photo_required, auto_advance, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->bind_param("iisssssssisssiii", 
                            $_POST['hunt_id'],
                            $_POST['clue_order'],
                            $_POST['title'],
                            $_POST['clue_text'],
                            $_POST['task_description'],
                            $_POST['hint_text'],
                            $_POST['answer'],
                            $_POST['required_answer'],
                            $_POST['input_type'],
                            isset($_POST['is_case_sensitive']) ? 1 : 0,
                            $answer_options,
                            $min_value,
                            $max_value,
                            isset($_POST['photo_required']) ? 1 : 0,
                            isset($_POST['auto_advance']) ? 1 : 0
                        );
                    }
                    
                    $stmt->execute();
                    $message = "Clue added successfully!";
                    break;
                    
                case 'edit_clue':
                    // Build the SQL dynamically to handle NULL properly
                    if ($answer_options === null) {
                        $stmt = $db->prepare("
                            UPDATE wp2s_pp_clues SET 
                            hunt_id = ?, clue_order = ?, title = ?, clue_text = ?, 
                            task_description = ?, hint_text = ?, answer = ?, required_answer = ?, 
                            input_type = ?, is_case_sensitive = ?, answer_options = NULL, 
                            min_value = ?, max_value = ?, photo_required = ?, auto_advance = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("iisssssssisissii", 
                            $_POST['hunt_id'],
                            $_POST['clue_order'],
                            $_POST['title'],
                            $_POST['clue_text'],
                            $_POST['task_description'],
                            $_POST['hint_text'],
                            $_POST['answer'],
                            $_POST['required_answer'],
                            $_POST['input_type'],
                            isset($_POST['is_case_sensitive']) ? 1 : 0,
                            $min_value,
                            $max_value,
                            isset($_POST['photo_required']) ? 1 : 0,
                            isset($_POST['auto_advance']) ? 1 : 0,
                            $_POST['id']
                        );
                    } else {
                        $stmt = $db->prepare("
                            UPDATE wp2s_pp_clues SET 
                            hunt_id = ?, clue_order = ?, title = ?, clue_text = ?, 
                            task_description = ?, hint_text = ?, answer = ?, required_answer = ?, 
                            input_type = ?, is_case_sensitive = ?, answer_options = ?, 
                            min_value = ?, max_value = ?, photo_required = ?, auto_advance = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("iisssssssisssiiii", 
                            $_POST['hunt_id'],
                            $_POST['clue_order'],
                            $_POST['title'],
                            $_POST['clue_text'],
                            $_POST['task_description'],
                            $_POST['hint_text'],
                            $_POST['answer'],
                            $_POST['required_answer'],
                            $_POST['input_type'],
                            isset($_POST['is_case_sensitive']) ? 1 : 0,
                            $answer_options,
                            $min_value,
                            $max_value,
                            isset($_POST['photo_required']) ? 1 : 0,
                            isset($_POST['auto_advance']) ? 1 : 0,
                            $_POST['id']
                        );
                    }
                    
                    $stmt->execute();
                    $message = "Clue updated successfully!";
                    break;
                    
                case 'delete_clue':
                    $stmt = $db->prepare("DELETE FROM wp2s_pp_clues WHERE id = ?");
                    $stmt->bind_param("i", $_POST['id']);
                    $stmt->execute();
                    $message = "Clue deleted successfully!";
                    break;
                    
                case 'fix_input_types':
                    // Auto-fix common input types based on task descriptions
                    $clues = $db->query("SELECT id, task_description, clue_text FROM wp2s_pp_clues WHERE input_type IS NULL OR input_type = '' OR input_type = 'none'");
                    $fixed_count = 0;
                    
                    while ($clue = $clues->fetch_assoc()) {
                        $task = strtolower($clue['task_description'] . ' ' . $clue['clue_text']);
                        
                        $input_type = 'none';
                        if (preg_match('/\b(type|enter|write|spell|word|letter|number|what is|how many|count|read the sign)\b/', $task)) {
                            $input_type = 'text';
                        } elseif (preg_match('/\b(photo|picture|selfie|take a|snap|pose|image)\b/', $task)) {
                            $input_type = 'photo';
                        }
                        
                        if ($input_type !== 'none') {
                            $update_stmt = $db->prepare("UPDATE wp2s_pp_clues SET input_type = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $input_type, $clue['id']);
                            $update_stmt->execute();
                            $fixed_count++;
                        }
                    }
                    
                    $message = "Auto-fixed $fixed_count clues with appropriate input types!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get clue for editing
$edit_clue = null;
if (isset($_GET['edit'])) {
    $db = getMainDb();
    $stmt = $db->prepare("SELECT * FROM wp2s_pp_clues WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $edit_clue = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clue Management - PuzzlePath</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: green; background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { color: red; background: #f8f0f0; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { color: blue; background: #f0f0f8; padding: 15px; border-radius: 8px; margin: 15px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input, select, textarea { padding: 8px; margin: 5px; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; max-width: 400px; }
        textarea { height: 100px; }
        button { background: #4ca6a8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #3a8a8c; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        .form-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-col { flex: 1; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        .input-type-options { display: none; margin-top: 10px; padding: 15px; background: #fff; border-radius: 5px; }
        .highlight { background-color: #ffeb3b; }
    </style>
</head>
<body>

<h1>🧩 PuzzlePath Clue Management</h1>

<?php if (isset($message)): ?>
    <div class="success">✅ <?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error">❌ Error: <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Quick Fix Button -->
<div class="info">
    <h3>🔧 Quick Fix</h3>
    <p>This will automatically detect and set input types for clues based on their task descriptions:</p>
    <form method="post" style="display: inline;">
        <input type="hidden" name="action" value="fix_input_types">
        <button type="submit" onclick="return confirm('This will update clues that currently have no input type set. Continue?')">
            🔄 Auto-Fix Input Types
        </button>
    </form>
</div>

<!-- Add/Edit Clue Form -->
<div class="form-section">
    <h2><?php echo $edit_clue ? 'Edit Clue' : 'Add New Clue'; ?></h2>
    
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $edit_clue ? 'edit_clue' : 'add_clue'; ?>">
        <?php if ($edit_clue): ?>
            <input type="hidden" name="id" value="<?php echo $edit_clue['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-col">
                <label>Hunt ID:</label>
                <select name="hunt_id" required>
                    <?php
                    $db = getMainDb();
                    $hunts = $db->query("SELECT id, hunt_name, title FROM wp2s_pp_events ORDER BY title");
                    while ($hunt = $hunts->fetch_assoc()): ?>
                        <option value="<?php echo $hunt['id']; ?>" <?php echo $edit_clue && $edit_clue['hunt_id'] == $hunt['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($hunt['title'] . ' (' . ($hunt['hunt_name'] ?: 'No hunt name') . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-col">
                <label>Clue Order:</label>
                <input type="number" name="clue_order" value="<?php echo $edit_clue ? $edit_clue['clue_order'] : ''; ?>" required min="1">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Title:</label>
                <input type="text" name="title" value="<?php echo $edit_clue ? htmlspecialchars($edit_clue['title']) : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Clue Text:</label>
                <textarea name="clue_text" required><?php echo $edit_clue ? htmlspecialchars($edit_clue['clue_text']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Task Description:</label>
                <textarea name="task_description" required><?php echo $edit_clue ? htmlspecialchars($edit_clue['task_description']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Hint Text:</label>
                <textarea name="hint_text"><?php echo $edit_clue ? htmlspecialchars($edit_clue['hint_text']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Input Type: <span style="color: red;">*</span></label>
                <select name="input_type" id="input_type" required onchange="toggleInputOptions()">
                    <option value="">Select Input Type</option>
                    <option value="none" <?php echo $edit_clue && $edit_clue['input_type'] == 'none' ? 'selected' : ''; ?>>None (Just mark as complete)</option>
                    <option value="text" <?php echo $edit_clue && $edit_clue['input_type'] == 'text' ? 'selected' : ''; ?>>Text Input</option>
                    <option value="number" <?php echo $edit_clue && $edit_clue['input_type'] == 'number' ? 'selected' : ''; ?>>Number Input</option>
                    <option value="multiple_choice" <?php echo $edit_clue && $edit_clue['input_type'] == 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                    <option value="photo" <?php echo $edit_clue && $edit_clue['input_type'] == 'photo' ? 'selected' : ''; ?>>Photo Upload</option>
                </select>
            </div>
        </div>
        
        <!-- Input Type Specific Options -->
        <div id="text_options" class="input-type-options">
            <label>
                <input type="checkbox" name="is_case_sensitive" <?php echo $edit_clue && $edit_clue['is_case_sensitive'] ? 'checked' : ''; ?>>
                Case Sensitive
            </label>
        </div>
        
        <div id="number_options" class="input-type-options">
            <div class="form-row">
                <div class="form-col">
                    <label>Min Value:</label>
                    <input type="number" name="min_value" value="<?php echo $edit_clue ? $edit_clue['min_value'] : ''; ?>">
                </div>
                <div class="form-col">
                    <label>Max Value:</label>
                    <input type="number" name="max_value" value="<?php echo $edit_clue ? $edit_clue['max_value'] : ''; ?>">
                </div>
            </div>
        </div>
        
        <div id="multiple_choice_options" class="input-type-options">
            <label>Answer Options (format: A: First option\nB: Second option):</label>
            <textarea name="answer_options"><?php 
                if ($edit_clue && $edit_clue['answer_options']) {
                    $options = json_decode($edit_clue['answer_options'], true);
                    if ($options) {
                        foreach ($options as $key => $value) {
                            echo htmlspecialchars($key . ': ' . $value) . "\n";
                        }
                    }
                }
            ?></textarea>
        </div>
        
        <div id="photo_options" class="input-type-options">
            <label>
                <input type="checkbox" name="photo_required" <?php echo $edit_clue && $edit_clue['photo_required'] ? 'checked' : ''; ?>>
                Photo Required
            </label>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>Answer:</label>
                <input type="text" name="answer" value="<?php echo $edit_clue ? htmlspecialchars($edit_clue['answer']) : ''; ?>">
            </div>
            <div class="form-col">
                <label>Required Answer:</label>
                <input type="text" name="required_answer" value="<?php echo $edit_clue ? htmlspecialchars($edit_clue['required_answer']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label>
                    <input type="checkbox" name="auto_advance" <?php echo $edit_clue && $edit_clue['auto_advance'] ? 'checked' : ''; ?>>
                    Auto Advance (automatically go to next clue after correct answer)
                </label>
            </div>
        </div>
        
        <button type="submit"><?php echo $edit_clue ? 'Update Clue' : 'Add Clue'; ?></button>
        <?php if ($edit_clue): ?>
            <a href="?admin_key=puzzle_admin_2024"><button type="button">Cancel</button></a>
        <?php endif; ?>
    </form>
</div>

<!-- Clue List -->
<h2>All Clues</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Hunt</th>
            <th>Order</th>
            <th>Title</th>
            <th>Input Type</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $db = getMainDb();
        $clues = $db->query("
            SELECT c.*, e.title as hunt_title 
            FROM wp2s_pp_clues c 
            LEFT JOIN wp2s_pp_events e ON c.hunt_id = e.id 
            ORDER BY c.hunt_id, c.clue_order
        ");
        
        while ($clue = $clues->fetch_assoc()): ?>
            <tr>
                <td><?php echo $clue['id']; ?></td>
                <td><?php echo htmlspecialchars($clue['hunt_title'] ?: 'Unknown Hunt'); ?></td>
                <td><?php echo $clue['clue_order']; ?></td>
                <td><?php echo htmlspecialchars($clue['title']); ?></td>
                <td class="<?php echo (!$clue['input_type'] || $clue['input_type'] == 'none') ? 'highlight' : ''; ?>">
                    <?php echo htmlspecialchars($clue['input_type'] ?: 'NOT SET'); ?>
                </td>
                <td>
                    <a href="?admin_key=puzzle_admin_2024&edit=<?php echo $clue['id']; ?>">
                        <button type="button">Edit</button>
                    </a>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this clue?')">
                        <input type="hidden" name="action" value="delete_clue">
                        <input type="hidden" name="id" value="<?php echo $clue['id']; ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
function toggleInputOptions() {
    // Hide all options
    document.querySelectorAll('.input-type-options').forEach(div => div.style.display = 'none');
    
    // Show relevant options
    const inputType = document.getElementById('input_type').value;
    if (inputType) {
        const optionsDiv = document.getElementById(inputType + '_options');
        if (optionsDiv) {
            optionsDiv.style.display = 'block';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleInputOptions);
</script>

<div class="info">
    <h3>📋 Input Type Guide</h3>
    <ul>
        <li><strong>None:</strong> Just shows "Mark as Complete" button - for photo tasks or observation tasks</li>
        <li><strong>Text:</strong> Shows text input field - for word answers, names, etc.</li>
        <li><strong>Number:</strong> Shows number input field - for counting, measurements, etc.</li>
        <li><strong>Multiple Choice:</strong> Shows radio buttons - for A/B/C type questions</li>
        <li><strong>Photo:</strong> Shows photo upload area - for selfies, proof photos, etc.</li>
    </ul>
    <p><strong>Note:</strong> Highlighted rows in the table have missing or 'none' input types that may need attention.</p>
</div>

</body>
</html>