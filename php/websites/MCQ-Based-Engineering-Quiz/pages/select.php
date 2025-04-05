<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Validate username
$username = isset($_GET['username']) ? sanitizeInput($_GET['username']) : '';
if (empty($username)) {
    $username = 'Guest';
}

// Get all available tags
$tags = getAllTags();

// Get presets
$presets = getPresets();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Quiz Options</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/select.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MCQ based Engineering Quiz</h1>
        </header>
        
        <main>
            <div class="selection-container fade-in">
                <div class="decorative-element element-1"></div>
                <div class="decorative-element element-2"></div>
                
                <div class="user-welcome">
                    <h2>Hello, <span class="username"><?php echo $username; ?></span>!</h2>
                    <p>Select quiz options</p>
                </div>
                
                <form id="quiz-options-form" action="quiz.php" method="GET">
                    <input type="hidden" name="username" value="<?php echo $username; ?>">
                    
                    <div id="custom-options" class="custom-options">
                        <div class="tags-section">
                            <h3>Topics</h3>
                            <div class="tags-wrapper">
                                <div class="tags-container" id="tags-container">
                                    <?php foreach ($tags as $tag): ?>
                                    <div class="tag-item">
                                        <input type="checkbox" id="tag-<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" class="tag-checkbox">
                                        <label for="tag-<?php echo $tag; ?>" class="tag-label"><?php echo $tag; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="show-more-tags" class="btn btn-secondary show-more-btn">More</button>
                            </div>
                        </div>
                        
                        <div class="form-options-row">
                            <div class="form-group">
                                <label for="num-questions">Questions:</label>
                                <select id="num-questions" name="num" class="question-count-select">
                                    <option value="random" selected>Random</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="time-limit">Time Limit:</label>
                                <select id="time-limit" name="time" class="time-select">
                                    <option value="none" selected>None</option>
                                    <option value="5">5 min</option>
                                    <option value="10">10 min</option>
                                    <option value="15">15 min</option>
                                    <option value="30">30 min</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary pulse-animation">Start Quiz</button>
                    </div>
                    
                    <div class="presets-section">
                        <h3>Or choose a preset quiz</h3>
                        <p class="preset-description">Don't want to customize? Use one of our ready-made quizzes:</p>
                        
                        <div class="presets-capsules">
                            <div class="preset-item capsule">
                                <input type="radio" id="preset-custom" name="preset" value="custom" class="preset-radio" checked>
                                <label for="preset-custom" class="preset-capsule-label">Custom</label>
                            </div>
                            
                            <div class="preset-item capsule">
                                <input type="radio" id="preset-random" name="preset" value="random" class="preset-radio">
                                <label for="preset-random" class="preset-capsule-label">Random</label>
                            </div>
                            
                            <?php foreach ($presets as $key => $preset): ?>
                            <div class="preset-item capsule">
                                <input type="radio" id="preset-<?php echo $key; ?>" name="preset" value="<?php echo $key; ?>" class="preset-radio">
                                <label for="preset-<?php echo $key; ?>" class="preset-capsule-label"><?php echo $preset['name']; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="preset-details-container" id="preset-details">
                            <!-- Preset details will be displayed here via JavaScript -->
                        </div>
                    </div>
                    
                    <div class="back-button-container">
                        <a href="../index.php" class="btn btn-secondary">Back</a>
                    </div>
                </form>
            </div>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle</p>
        </footer>
    </div>

    <script src="../assets/js/select.js"></script>
    <script>
    // Debug helper - log which preset is selected when user clicks any preset
    document.addEventListener('DOMContentLoaded', function() {
        // Log initial state
        const initialPreset = document.querySelector('input[name="preset"]:checked');
        console.log('Initial preset:', initialPreset ? initialPreset.value : 'none');
        
        // Add event listeners to all preset radio buttons
        document.querySelectorAll('input[name="preset"]').forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Preset selected:', this.value);
                
                // Ensure the preset value will be passed in the form
                setActivePreset(this.value);
                
                // Log all the preset radio buttons
                document.querySelectorAll('input[name="preset"]').forEach(r => {
                    console.log(`Preset ${r.value}: ${r.checked ? 'checked' : 'unchecked'}`);
                });
            });
        });
        
        // Handle form submission
        document.getElementById('quiz-options-form').addEventListener('submit', function(e) {
            const selectedPreset = document.querySelector('input[name="preset"]:checked');
            console.log('Form submitting with preset:', selectedPreset ? selectedPreset.value : 'none');
            
            // Double-check that the preset value is properly set before submission
            if (selectedPreset && selectedPreset.value !== 'custom') {
                setActivePreset(selectedPreset.value);
            }
        });
        
        // Create a hidden input to ensure the preset value is submitted
        function setActivePreset(presetValue) {
            // Remove any existing hidden preset inputs to avoid duplicates
            const existingHidden = document.getElementById('hidden-preset-value');
            if (existingHidden) {
                existingHidden.value = presetValue;
            } else {
                // Create a new hidden input to ensure the preset value is sent
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'hidden-preset-value';
                hiddenInput.name = 'preset';
                hiddenInput.value = presetValue;
                
                // Append to the form
                document.getElementById('quiz-options-form').appendChild(hiddenInput);
            }
            console.log('Active preset set to:', presetValue);
        }
    });
    </script>
</body>
</html> 