<?php
/**
 * Quiz Page
 * 
 * This file handles:
 * - Loading quiz questions based on selected tags or preset
 * - Managing quiz configuration (time limit, number of questions)
 * - Randomizing question options for better quiz experience
 * - Displaying quiz interface to the user
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Validate username
$username = isset($_GET['username']) ? sanitizeInput($_GET['username']) : 'Guest';

// Debug incoming data
error_log("Quiz request data: " . print_r($_GET, true));

// Check if preset was selected (explicitly check against 'custom' preset)
$preset = null;
if (isset($_GET['preset'])) {
    error_log("Raw preset value: '" . $_GET['preset'] . "'");
    
    // Only use preset if it's not 'custom' and not empty
    if ($_GET['preset'] !== 'custom' && !empty($_GET['preset'])) {
        $preset = $_GET['preset'];
        error_log("Using preset: " . $preset);
    } else {
        error_log("Preset not used: " . $_GET['preset']);
    }
} else {
    error_log("No preset parameter found");
}

// Get time limit if set
$timeLimit = isset($_GET['time']) && $_GET['time'] !== 'none' ? (int)$_GET['time'] : null;

if ($preset) {
    // Use preset settings
    $presetData = getPresetById($preset);
    
    if ($presetData) {
        // Extract questions and settings from the preset
        $tags = $presetData['tags'];
        $numQuestions = $presetData['num_questions'];
        error_log("Using preset data: " . print_r($presetData, true));
    } else {
        // Fallback if preset not found
        error_log("Preset not found: " . $preset);
        redirect('select.php?username=' . urlencode($username) . '&error=preset_not_found');
    }
} else {
    // Use custom settings
    $tags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];
    $tags = array_map('sanitizeInput', $tags);
    error_log("Using custom tags: " . implode(", ", $tags));
    
    // Save the requested number before modification for debugging
    $requestedNum = isset($_GET['num']) ? $_GET['num'] : '5';
    $randomGenerated = null;

    // Check if random questions count is requested
    if (isset($_GET['num']) && $_GET['num'] === 'random') {
        // Generate a random number between 5 and 20
        $randomGenerated = rand(5, 20);
        $numQuestions = $randomGenerated;
        // Log the random generation
        error_log("Random questions count generated: " . $numQuestions);
    } else {
        // Use the specified number or default to 5
        $numQuestions = isset($_GET['num']) ? (int)$_GET['num'] : 5;
        if ($numQuestions <= 0 || $numQuestions > MAX_QUESTIONS) {
            $numQuestions = 5;
        }
    }
}

// Redirect if no tags are selected (only if no preset is selected)
if (empty($tags) && !$preset) {
    error_log("No tags selected and no preset chosen");
    redirect('select.php?username=' . urlencode($username) . '&error=no_tags');
}

// Get questions based on selected tags
$questions = getQuestionsByTags($tags, $numQuestions);
error_log("Found " . count($questions) . " questions");

// Redirect if no questions found
if (empty($questions)) {
    error_log("No questions found for tags: " . implode(", ", $tags));
    redirect('select.php?username=' . urlencode($username) . '&error=no_questions');
}

// No need to randomize options anymore - they will stay in their original order
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/quiz.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MCQ based Engineering Quiz</h1>
        </header>
        
        <main>
            <form id="quiz-form" action="results.php" method="POST">
                <div id="quiz-container" class="quiz-container">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card fade-in" id="question-<?php echo $index; ?>" <?php echo $index === 0 ? '' : 'style="display:none;"'; ?>>
                            <h2 class="question-text" data-number="<?php echo $index + 1; ?>"><?php echo $question['question']; ?></h2>
                            
                            <div class="options-container">
                                <?php foreach ($question['options'] as $optIndex => $option): ?>
                                    <div class="option">
                                        <input type="radio" 
                                               id="q<?php echo $question['id']; ?>-<?php echo $optIndex; ?>" 
                                               name="answers[<?php echo $question['id']; ?>]" 
                                               value="<?php echo $optIndex; ?>">
                                        <label for="q<?php echo $question['id']; ?>-<?php echo $optIndex; ?>"><?php echo $option; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="question-nav">
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-secondary prev-btn" data-question="<?php echo $index - 1; ?>">Previous</button>
                                <?php else: ?>
                                    <div></div> <!-- Empty div for spacing -->
                                <?php endif; ?>
                                
                                <?php if ($index < count($questions) - 1): ?>
                                    <button type="button" class="btn btn-primary next-btn" data-question="<?php echo $index + 1; ?>">Next</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-success pulse-animation">Finish Quiz</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pass quiz metadata to results page -->
                <input type="hidden" name="username" value="<?php echo $username; ?>">
                <?php foreach ($tags as $tag): ?>
                    <input type="hidden" name="tags[]" value="<?php echo $tag; ?>">
                <?php endforeach; ?>
                <?php if ($preset): ?>
                    <input type="hidden" name="preset_name" value="<?php echo $presetData['name']; ?>">
                <?php endif; ?>
                <?php if ($timeLimit): ?>
                    <input type="hidden" name="time_limit" value="<?php echo $timeLimit; ?>">
                <?php endif; ?>
            </form>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle</p>
        </footer>
    </div>

    <script>
        // Pass quiz data to JavaScript
        const quizData = {
            timeLimit: <?php echo $timeLimit ? $timeLimit : 'null'; ?>,
            questionCount: <?php echo count($questions); ?>
        };
        
        // Debug information
        console.log('Quiz Configuration:');
        console.log('Requested num value:', <?php echo json_encode($requestedNum); ?>);
        console.log('Generated random value:', <?php echo json_encode($randomGenerated); ?>);
        console.log('Final question count:', <?php echo $numQuestions; ?>);
    </script>
    <script src="../assets/js/quiz.js"></script>
</body>
</html> 