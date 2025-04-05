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
    // Add a new question
    if (isset($_POST['action']) && $_POST['action'] === 'add_question') {
        $newQuestion = [
            'id' => time(), // Use timestamp as ID
            'question' => sanitizeInput($_POST['question']),
            'options' => [
                sanitizeInput($_POST['option_0']),
                sanitizeInput($_POST['option_1']),
                sanitizeInput($_POST['option_2']),
                sanitizeInput($_POST['option_3'])
            ],
            'correct' => (int)$_POST['correct_option'],
            'explanation' => sanitizeInput($_POST['explanation']),
            'tags' => array_map('sanitizeInput', isset($_POST['tags']) ? $_POST['tags'] : [])
        ];
        
        // Add question to database
        $result = addQuestion($newQuestion);
        if ($result) {
            $successMessage = 'Question added successfully.';
        } else {
            $error = 'Failed to add question.';
        }
    }
    
    // Delete a question
    else if (isset($_POST['action']) && $_POST['action'] === 'delete_question') {
        $questionId = (int)$_POST['question_id'];
        $result = deleteQuestion($questionId);
        if ($result) {
            $successMessage = 'Question deleted successfully.';
        } else {
            $error = 'Failed to delete question.';
        }
    }
    
    // Update a question
    else if (isset($_POST['action']) && $_POST['action'] === 'update_question') {
        $questionId = (int)$_POST['question_id'];
        $updatedQuestion = [
            'id' => $questionId,
            'question' => sanitizeInput($_POST['question']),
            'options' => [
                sanitizeInput($_POST['option_0']),
                sanitizeInput($_POST['option_1']),
                sanitizeInput($_POST['option_2']),
                sanitizeInput($_POST['option_3'])
            ],
            'correct' => (int)$_POST['correct_option'],
            'explanation' => sanitizeInput($_POST['explanation']),
            'tags' => array_map('sanitizeInput', isset($_POST['tags']) ? $_POST['tags'] : [])
        ];
        
        $result = updateQuestion($questionId, $updatedQuestion);
        if ($result) {
            $successMessage = 'Question updated successfully.';
        } else {
            $error = 'Failed to update question.';
        }
    }
}

// Get all questions and tags if authorized
if ($authorized) {
    $questions = getAllQuestions();
    $allTags = getAllTags();
}

/**
 * Add a new question to the database
 */
function addQuestion($question) {
    $allQuestions = getAllQuestions();
    
    // Check if we need to create a new file
    if (empty($allQuestions)) {
        $data = ['questions' => [$question]];
    } else {
        $data = ['questions' => array_merge($allQuestions, [$question])];
    }
    
    return file_put_contents(QUESTIONS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Delete a question from the database
 */
function deleteQuestion($questionId) {
    $allQuestions = getAllQuestions();
    
    // Find and remove the question
    $updatedQuestions = array_filter($allQuestions, function($q) use ($questionId) {
        return $q['id'] !== $questionId;
    });
    
    $data = ['questions' => array_values($updatedQuestions)]; // Re-index array
    return file_put_contents(QUESTIONS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Update a question in the database
 */
function updateQuestion($questionId, $updatedQuestion) {
    $allQuestions = getAllQuestions();
    
    // Find and update the question
    foreach ($allQuestions as $key => $question) {
        if ($question['id'] == $questionId) {
            $allQuestions[$key] = $updatedQuestion;
            break;
        }
    }
    
    $data = ['questions' => $allQuestions];
    return file_put_contents(QUESTIONS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz Questions</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/analytics.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/manage_questions.css">
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
                    <p>Please enter the password to manage quiz questions.</p>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="password-form">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Access Question Manager</button>
                    </form>
                    
                    <div class="action-buttons">
                        <a href="../index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="analytics-container fade-in">
                    <div class="analytics-header">
                        <h2>Quiz Question Manager</h2>
                        <p>Create, update, and delete quiz questions</p>
                    </div>
                    
                    <?php if ($successMessage): ?>
                        <div class="success-message">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="navigation-links">
                        <div>
                            <a href="manage_presets.php" class="btn btn-secondary">Manage Presets</a>
                            <a href="analytics.php" class="btn btn-secondary">View Analytics</a>
                        </div>
                        <a href="../index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                    
                    <div class="question-form">
                        <h3>Add New Question</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_question">
                            
                            <div class="form-group">
                                <label for="question">Question:</label>
                                <input type="text" id="question" name="question" required class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Options (select the correct one):</label>
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="option-group">
                                        <input type="radio" name="correct_option" value="<?php echo $i; ?>" id="correct_<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                                        <label for="correct_<?php echo $i; ?>"><?php echo chr(65 + $i); ?></label>
                                        <input type="text" name="option_<?php echo $i; ?>" required class="form-control">
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="explanation">Explanation:</label>
                                <textarea id="explanation" name="explanation" class="form-control"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Tags:</label>
                                <div class="tag-selector">
                                    <?php foreach ($allTags as $tag): ?>
                                        <input type="checkbox" id="tag_add_<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" class="tag-checkbox">
                                        <label for="tag_add_<?php echo $tag; ?>"><?php echo $tag; ?></label>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top: 10px;">
                                    <label for="new_tag">Add new tag:</label>
                                    <input type="text" id="new_tag" name="tags[]" class="form-control" style="width: auto; display: inline-block;">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Question</button>
                        </form>
                    </div>
                    
                    <div class="question-list">
                        <h3>Question List</h3>
                        <p>Total: <?php echo count($questions); ?> questions</p>
                        
                        <?php foreach ($questions as $question): ?>
                            <div class="question-item slide-in">
                                <div class="question-header">
                                    <h4><?php echo $question['question']; ?></h4>
                                    <div class="question-controls">
                                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($question), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                            <input type="hidden" name="action" value="delete_question">
                                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <ol class="options-list" type="A">
                                    <?php foreach ($question['options'] as $index => $option): ?>
                                        <li class="<?php echo $index === $question['correct'] ? 'correct-option' : ''; ?>" data-letter="<?php echo chr(65 + $index); ?>">
                                            <?php echo $option; ?>
                                            <?php if ($index === $question['correct']): ?> ✓<?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                                
                                <?php if (isset($question['explanation'])): ?>
                                    <div class="explanation">
                                        <strong>Explanation:</strong> <?php echo $question['explanation']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="tag-list">
                                    <?php foreach ($question['tags'] as $tag): ?>
                                        <span class="tag-badge"><?php echo $tag; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Edit question modal -->
                    <div id="editModal" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="closeEditModal()">&times;</span>
                            <h3>Edit Question</h3>
                            <form method="POST" action="" id="editForm">
                                <input type="hidden" name="action" value="update_question">
                                <input type="hidden" name="question_id" id="edit_question_id">
                                
                                <div class="form-group">
                                    <label for="edit_question">Question:</label>
                                    <input type="text" id="edit_question" name="question" required class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>Options (select the correct one):</label>
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <div class="option-group">
                                            <input type="radio" name="correct_option" value="<?php echo $i; ?>" id="edit_correct_<?php echo $i; ?>">
                                            <label for="edit_correct_<?php echo $i; ?>"><?php echo chr(65 + $i); ?></label>
                                            <input type="text" name="option_<?php echo $i; ?>" id="edit_option_<?php echo $i; ?>" required class="form-control">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_explanation">Explanation:</label>
                                    <textarea id="edit_explanation" name="explanation" class="form-control"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tags:</label>
                                    <div class="tag-selector" id="edit_tags_selector">
                                        <?php foreach ($allTags as $tag): ?>
                                            <input type="checkbox" id="edit_tag_<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" class="tag-checkbox">
                                            <label for="edit_tag_<?php echo $tag; ?>"><?php echo $tag; ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <label for="edit_new_tag">Add new tag:</label>
                                        <input type="text" id="edit_new_tag" name="tags[]" class="form-control" style="width: auto; display: inline-block;">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Update Question</button>
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
    <script src="../assets/js/manage_questions.js"></script>
</body>
</html> 