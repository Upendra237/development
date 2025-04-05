<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

// Get username
$username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : 'Guest';

// Get preset name if used
$presetName = isset($_POST['preset_name']) ? sanitizeInput($_POST['preset_name']) : null;

// Get and sanitize user answers
$userAnswers = isset($_POST['answers']) ? $_POST['answers'] : [];
$userAnswers = array_map('intval', $userAnswers);

// Get selected tags
$tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
$tags = array_map('sanitizeInput', $tags);

// Get all questions
$allQuestions = getAllQuestions();

// Calculate score
$scoreData = calculateScore($userAnswers, $allQuestions);

// Save result
$resultData = [
    'username' => $username,
    'user_answers' => $userAnswers,
    'tags' => $tags,
    'score' => $scoreData['score'],
    'total' => $scoreData['total'],
    'percentage' => $scoreData['percentage'],
    'preset_name' => $presetName
];
saveResult($resultData);

// Get questions user answered
$answeredQuestions = [];
foreach ($allQuestions as $question) {
    if (array_key_exists($question['id'], $userAnswers)) {
        $answeredQuestions[] = $question;
    }
}

// Get user's previous results
$allResults = getAllResults();
$userResults = array_filter($allResults, function($result) use ($username) {
    return isset($result['username']) && $result['username'] === $username;
});

// Calculate user stats
$totalQuizzes = count($userResults);
$averageScore = 0;
$topScoreQuiz = null;

if ($totalQuizzes > 0) {
    $totalScore = 0;
    foreach ($userResults as $result) {
        $totalScore += $result['percentage'];
        
        // Find top score
        if (!$topScoreQuiz || $result['percentage'] > $topScoreQuiz['percentage']) {
            $topScoreQuiz = $result;
        }
    }
    $averageScore = round($totalScore / $totalQuizzes);
}

// Get most frequent tags
$userTags = [];
foreach ($userResults as $result) {
    foreach ($result['tags'] as $tag) {
        if (!isset($userTags[$tag])) {
            $userTags[$tag] = 0;
        }
        $userTags[$tag]++;
    }
}
arsort($userTags);
$topTags = array_slice($userTags, 0, 3, true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/results.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MCQ based Engineering Quiz</h1>
        </header>
        
        <main>
            <div class="results-container fade-in">
                <div class="user-result-header">
                    <h2>Results for <span class="username"><?php echo $username; ?></span></h2>
                    <?php if ($presetName): ?>
                    <p class="preset-info">Preset: <span class="preset-badge"><?php echo $presetName; ?></span></p>
                    <?php endif; ?>
                </div>
                
                <div class="score-summary">
                    <div class="score-circle <?php echo getScoreClass($scoreData['percentage']); ?>">
                        <span class="percentage"><?php echo $scoreData['percentage']; ?>%</span>
                    </div>
                    <div class="score-details">
                        <p>Score: <strong><?php echo $scoreData['score']; ?> out of <?php echo $scoreData['total']; ?></strong></p>
                        <p>Topics: <span class="tags-highlight"><?php echo implode(', ', $tags); ?></span></p>
                        <p>Date: <?php echo date('F j, Y, g:i a'); ?></p>
                    </div>
                </div>
                
                <div class="results-breakdown">
                    <h2>Question Breakdown</h2>
                    <div class="questions-list">
                        <?php foreach ($answeredQuestions as $question): ?>
                            <?php 
                                $userAnswer = $userAnswers[$question['id']];
                                $isCorrect = ($userAnswer == $question['correct']);
                                $resultClass = $isCorrect ? 'correct' : 'incorrect';
                            ?>
                            <div class="question-result <?php echo $resultClass; ?> slide-in">
                                <h3><?php echo $question['question']; ?></h3>
                                <div class="answer-details">
                                    <p>Your answer: <strong><?php echo $question['options'][$userAnswer]; ?></strong> 
                                    <?php if ($isCorrect): ?>
                                        <span class="correct-badge">✓ Correct</span>
                                    <?php else: ?>
                                        <span class="incorrect-badge">✗ Incorrect</span>
                                    <?php endif; ?>
                                    </p>
                                    <?php if (!$isCorrect): ?>
                                        <p>Correct answer: <strong><?php echo $question['options'][$question['correct']]; ?></strong></p>
                                    <?php endif; ?>
                                    <p class="explanation">
                                        <?php if (isset($question['explanation'])): ?>
                                            <?php echo $question['explanation']; ?>
                                        <?php else: ?>
                                            The correct answer is option <?php echo chr(65 + $question['correct']); ?>.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="user-analytics">
                    <h2>Your Performance</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $totalQuizzes; ?></div>
                            <div class="stat-label">Total Quizzes</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $averageScore; ?>%</div>
                            <div class="stat-label">Average Score</div>
                        </div>
                        
                        <?php if ($topScoreQuiz): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $topScoreQuiz['percentage']; ?>%</div>
                            <div class="stat-label">Highest Score</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($topTags)): ?>
                    <div class="favorite-topics">
                        <h3>Your Favorite Topics</h3>
                        <div class="topics-container">
                            <?php foreach ($topTags as $tag => $count): ?>
                            <div class="topic-badge">
                                <span class="topic-name"><?php echo $tag; ?></span>
                                <span class="topic-count"><?php echo $count; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="progress-chart">
                        <h3>Your Progress</h3>
                        <p class="progress-message">
                            <?php if ($totalQuizzes <= 1): ?>
                                This is your first quiz!
                            <?php elseif ($scoreData['percentage'] > $averageScore): ?>
                                Great job! Your score is above your average.
                            <?php else: ?>
                                Keep practicing!
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="../index.php" class="btn btn-primary">Take Another Quiz</a>
                    <a href="select.php?username=<?php echo urlencode($username); ?>" class="btn btn-secondary">Change Topics</a>
                </div>
            </div>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle | Developed by <a href="https://github.com/upendrahsi" target="_blank">Upendra Shahi - 780347</a></p>
        </footer>
    </div>
</body>
</html>

<?php
// Helper function to get score class
function getScoreClass($percentage) {
    if ($percentage >= 80) {
        return 'excellent';
    } elseif ($percentage >= 60) {
        return 'good';
    } elseif ($percentage >= 40) {
        return 'average';
    } else {
        return 'poor';
    }
}
?> 