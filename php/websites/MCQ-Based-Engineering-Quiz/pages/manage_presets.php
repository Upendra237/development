<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check for password authentication
$authorized = false;
$error = '';
$successMessage = '';

// Session setup
session_start();

// Check if already logged in
if (isset($_SESSION['quiz_admin_auth']) && $_SESSION['quiz_admin_auth'] === true) {
    $authorized = true;
}
// Check if login attempt
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $authorized = true;
        $_SESSION['quiz_admin_auth'] = true;
    } else {
        $error = 'Incorrect password. Please try again.';
    }
}

// Process form submissions for CRUD operations
if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new preset
    if (isset($_POST['action']) && $_POST['action'] === 'add_preset') {
        $newPreset = [
            'id' => time(), // Use timestamp as ID
            'name' => sanitizeInput($_POST['preset_name']),
            'description' => sanitizeInput($_POST['preset_description']),
            'tags' => array_map('sanitizeInput', isset($_POST['tags']) ? $_POST['tags'] : []),
            'num_questions' => (int)$_POST['num_questions'],
            'time_limit' => $_POST['time_limit'] === 'none' ? null : (int)$_POST['time_limit']
        ];
        
        // Add preset to database
        $result = addPreset($newPreset);
        if ($result) {
            $successMessage = 'Preset added successfully.';
        } else {
            $error = 'Failed to add preset.';
        }
    }
    
    // Delete a preset
    else if (isset($_POST['action']) && $_POST['action'] === 'delete_preset') {
        $presetId = (int)$_POST['preset_id'];
        $result = deletePreset($presetId);
        if ($result) {
            $successMessage = 'Preset deleted successfully.';
        } else {
            $error = 'Failed to delete preset.';
        }
    }
    
    // Update a preset
    else if (isset($_POST['action']) && $_POST['action'] === 'update_preset') {
        $presetId = (int)$_POST['preset_id'];
        $updatedPreset = [
            'id' => $presetId,
            'name' => sanitizeInput($_POST['preset_name']),
            'description' => sanitizeInput($_POST['preset_description']),
            'tags' => array_map('sanitizeInput', isset($_POST['tags']) ? $_POST['tags'] : []),
            'num_questions' => (int)$_POST['num_questions'],
            'time_limit' => $_POST['time_limit'] === 'none' ? null : (int)$_POST['time_limit']
        ];
        
        $result = updatePreset($presetId, $updatedPreset);
        if ($result) {
            $successMessage = 'Preset updated successfully.';
        } else {
            $error = 'Failed to update preset.';
        }
    }
}

// Get all presets and tags if authorized
if ($authorized) {
    $presets = getPresets();
    $allTags = getAllTags();
}

/**
 * Add a new preset to the database
 */
function addPreset($preset) {
    $allPresets = getPresets();
    
    // Check if we need to create a new file
    if (file_exists(PRESETS_FILE)) {
        $data = json_decode(file_get_contents(PRESETS_FILE), true);
        if (!isset($data['presets'])) {
            $data['presets'] = [];
        }
    } else {
        $data = ['presets' => []];
    }
    
    $data['presets'][] = $preset;
    
    return file_put_contents(PRESETS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Delete a preset from the database
 */
function deletePreset($presetId) {
    if (!file_exists(PRESETS_FILE)) {
        return false;
    }
    
    $data = json_decode(file_get_contents(PRESETS_FILE), true);
    
    if (!isset($data['presets'])) {
        return false;
    }
    
    // Find and remove the preset
    $updatedPresets = array_filter($data['presets'], function($p) use ($presetId) {
        return $p['id'] !== $presetId;
    });
    
    $data['presets'] = array_values($updatedPresets); // Re-index array
    return file_put_contents(PRESETS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Update a preset in the database
 */
function updatePreset($presetId, $updatedPreset) {
    if (!file_exists(PRESETS_FILE)) {
        return false;
    }
    
    $data = json_decode(file_get_contents(PRESETS_FILE), true);
    
    if (!isset($data['presets'])) {
        return false;
    }
    
    // Find and update the preset
    foreach ($data['presets'] as $key => $preset) {
        if ($preset['id'] == $presetId) {
            $data['presets'][$key] = $updatedPreset;
            break;
        }
    }
    
    return file_put_contents(PRESETS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz Presets</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/analytics.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/manage_presets.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MCQ based Engineering Quiz</h1>
        </header>
        
        <main>
            <?php if (!$authorized): ?>
                <div class="auth-container fade-in">
                    <h2>Administrator Access Required</h2>
                    <p>Please enter the password to manage quiz presets.</p>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="password-form">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Access Preset Manager</button>
                    </form>
                    
                    <div class="action-buttons">
                        <a href="../index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="analytics-container fade-in">
                    <div class="analytics-header">
                        <h2>Quiz Preset Manager</h2>
                        <p>Create, update, and delete quiz presets</p>
                    </div>
                    
                    <?php if ($successMessage): ?>
                        <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="navigation-links">
                        <div>
                            <a href="manage_quizes.php" class="btn btn-secondary">Manage Questions</a>
                            <a href="analytics.php" class="btn btn-secondary">View Analytics</a>
                        </div>
                        <a href="../index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                    
                    <div class="preset-form">
                        <h3>Add New Preset</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_preset">
                            
                            <div class="form-row">
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="preset_name">Preset Name:</label>
                                        <input type="text" id="preset_name" name="preset_name" required class="form-control">
                                    </div>
                                </div>
                                
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="preset_description">Description:</label>
                                        <textarea id="preset_description" name="preset_description" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="num_questions">Number of Questions:</label>
                                        <select id="num_questions" name="num_questions" class="form-control">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="15">15</option>
                                            <option value="20">20</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="time_limit">Time Limit:</label>
                                        <select id="time_limit" name="time_limit" class="form-control">
                                            <option value="none" selected>None</option>
                                            <option value="5">5 minutes</option>
                                            <option value="10">10 minutes</option>
                                            <option value="15">15 minutes</option>
                                            <option value="30">30 minutes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Tags:</label>
                                <div class="tag-selector">
                                    <?php foreach ($allTags as $tag): ?>
                                        <input type="checkbox" id="tag_add_<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" class="tag-checkbox">
                                        <label for="tag_add_<?php echo $tag; ?>"><?php echo $tag; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Preset</button>
                        </form>
                    </div>
                    
                    <div class="preset-list">
                        <h3>Preset List</h3>
                        <p>Total: <?php echo count($presets); ?> presets</p>
                        
                        <?php foreach ($presets as $preset): ?>
                            <div class="preset-item slide-in">
                                <div class="preset-header">
                                    <h4><?php echo $preset['name']; ?></h4>
                                    <div class="preset-controls">
                                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($preset), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this preset?');">
                                            <input type="hidden" name="action" value="delete_preset">
                                            <input type="hidden" name="preset_id" value="<?php echo $preset['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="preset-description"><?php echo $preset['description']; ?></div>
                                
                                <div class="preset-details">
                                    <p>Questions: <strong><?php echo $preset['num_questions']; ?></strong></p>
                                    <p>Time Limit: <strong><?php echo $preset['time_limit'] ? $preset['time_limit'] . ' minutes' : 'None'; ?></strong></p>
                                </div>
                                
                                <div class="tag-list">
                                    <?php foreach ($preset['tags'] as $tag): ?>
                                        <span class="tag-badge"><?php echo $tag; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Edit preset modal -->
                    <div id="editModal" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="closeEditModal()">&times;</span>
                            <h3>Edit Preset</h3>
                            <form method="POST" action="" id="editForm">
                                <input type="hidden" name="action" value="update_preset">
                                <input type="hidden" name="preset_id" id="edit_preset_id">
                                
                                <div class="form-group">
                                    <label for="edit_preset_name">Preset Name:</label>
                                    <input type="text" id="edit_preset_name" name="preset_name" required class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_preset_description">Description:</label>
                                    <textarea id="edit_preset_description" name="preset_description" class="form-control"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-column">
                                        <div class="form-group">
                                            <label for="edit_num_questions">Number of Questions:</label>
                                            <select id="edit_num_questions" name="num_questions" class="form-control">
                                                <option value="5">5</option>
                                                <option value="10">10</option>
                                                <option value="15">15</option>
                                                <option value="20">20</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-column">
                                        <div class="form-group">
                                            <label for="edit_time_limit">Time Limit:</label>
                                            <select id="edit_time_limit" name="time_limit" class="form-control">
                                                <option value="none">None</option>
                                                <option value="5">5 minutes</option>
                                                <option value="10">10 minutes</option>
                                                <option value="15">15 minutes</option>
                                                <option value="30">30 minutes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tags:</label>
                                    <div class="tag-selector" id="edit_tags_selector">
                                        <?php foreach ($allTags as $tag): ?>
                                            <input type="checkbox" id="edit_tag_<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" class="tag-checkbox">
                                            <label for="edit_tag_<?php echo $tag; ?>"><?php echo $tag; ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Update Preset</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle</p>
        </footer>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/manage_presets.js"></script>
</body>
</html> 