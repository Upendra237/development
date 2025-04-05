<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check for password authentication
$authorized = false;
$error = '';

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

// Only proceed with data loading if authorized
if ($authorized) {
    // Get all results
    $results = getAllResults();

    // Get time periods
    $today = strtotime('today');
    $week = strtotime('-1 week');
    $month = strtotime('-1 month');

    // Filter results by time periods
    $dailyResults = array_filter($results, function($result) use ($today) {
        return $result['timestamp'] >= $today;
    });

    $weeklyResults = array_filter($results, function($result) use ($week) {
        return $result['timestamp'] >= $week;
    });

    $monthlyResults = array_filter($results, function($result) use ($month) {
        return $result['timestamp'] >= $month;
    });

    // Calculate statistics
    $dailyStats = calculateStats($dailyResults);
    $weeklyStats = calculateStats($weeklyResults);
    $monthlyStats = calculateStats($monthlyResults);
    $allTimeStats = calculateStats($results);

    // Get popular tags
    $popularTags = getPopularTags($results);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/analytics.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                    <p>Please enter the password to view analytics data.</p>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="password-form">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Access Analytics</button>
                    </form>
                    
                    <div class="action-buttons">
                        <a href="../index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="analytics-container fade-in">
                    <div class="analytics-header">
                        <h2>Quiz Analytics Dashboard</h2>
                        <p>Performance and engagement metrics</p>
                    </div>
                    
                    <div class="stats-summary">
                        <div class="stats-card">
                            <h3>Daily Statistics</h3>
                            <div class="stats-content">
                                <p>Quizzes: <strong><?php echo count($dailyResults); ?></strong></p>
                                <p>Avg Score: <strong><?php echo $dailyStats['avgScore']; ?>%</strong></p>
                                <p>Top User: <strong><?php echo $dailyStats['mostActiveUser']; ?></strong></p>
                            </div>
                        </div>
                        
                        <div class="stats-card">
                            <h3>Weekly Statistics</h3>
                            <div class="stats-content">
                                <p>Quizzes: <strong><?php echo count($weeklyResults); ?></strong></p>
                                <p>Avg Score: <strong><?php echo $weeklyStats['avgScore']; ?>%</strong></p>
                                <p>Top User: <strong><?php echo $weeklyStats['mostActiveUser']; ?></strong></p>
                            </div>
                        </div>
                        
                        <div class="stats-card">
                            <h3>Monthly Statistics</h3>
                            <div class="stats-content">
                                <p>Quizzes: <strong><?php echo count($monthlyResults); ?></strong></p>
                                <p>Avg Score: <strong><?php echo $monthlyStats['avgScore']; ?>%</strong></p>
                                <p>Top User: <strong><?php echo $monthlyStats['mostActiveUser']; ?></strong></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="analytics-details">
                        <div class="popular-tags-section">
                            <h3>Popular Topics</h3>
                            <div class="popular-tags">
                                <?php foreach ($popularTags as $tag => $count): ?>
                                    <div class="tag-stat-item">
                                        <span class="tag-name"><?php echo $tag; ?></span>
                                        <span class="tag-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="all-time-stats">
                            <h3>All-Time Statistics</h3>
                            <div class="stats-content">
                                <p>Total Quizzes: <strong><?php echo count($results); ?></strong></p>
                                <p>Average Score: <strong><?php echo $allTimeStats['avgScore']; ?>%</strong></p>
                                <p>Perfect Scores: <strong><?php echo $allTimeStats['perfectScores']; ?></strong></p>
                                <p>Top Performers:</p>
                                <ul class="top-performers">
                                    <?php foreach ($allTimeStats['topPerformers'] as $user => $score): ?>
                                        <li><strong><?php echo $user; ?></strong> (<?php echo $score; ?>%)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <h3>Recent Activity</h3>
                        <div class="activity-list">
                            <?php 
                            // Get 5 most recent results
                            $recentResults = array_slice($results, 0, 5);
                            foreach ($recentResults as $result): 
                            ?>
                                <div class="activity-item slide-in">
                                    <div class="activity-user">
                                        <span class="user"><?php echo isset($result['username']) ? $result['username'] : 'Guest'; ?></span>
                                        <span class="date"><?php echo date('M j, g:i a', $result['timestamp']); ?></span>
                                    </div>
                                    <div class="activity-details">
                                        <p>Score: <strong><?php echo $result['percentage']; ?>%</strong> (<?php echo $result['score']; ?>/<?php echo $result['total']; ?>)</p>
                                        <p>Topics: <?php echo implode(', ', $result['tags']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <div class="dashboard-nav-links">
                            <a href="manage_quizes.php" class="btn btn-secondary">Manage Questions</a>
                            <a href="manage_presets.php" class="btn btn-secondary">Manage Presets</a>
                        </div>
                        <div>
                            <a href="../index.php" class="btn btn-primary">Take a Quiz</a>
                            <a href="javascript:window.print();" class="btn btn-secondary">Print Report</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle</p>
        </footer>
    </div>
</body>
</html>

<?php
/**
 * Calculate statistics from results
 */
function calculateStats($results) {
    $stats = [
        'avgScore' => 0,
        'perfectScores' => 0,
        'mostActiveUser' => 'None',
        'topPerformers' => []
    ];
    
    if (empty($results)) {
        return $stats;
    }
    
    // Calculate average score
    $totalPercentage = 0;
    $userCounts = [];
    $userScores = [];
    
    foreach ($results as $result) {
        $totalPercentage += $result['percentage'];
        
        // Track user activity
        $username = isset($result['username']) ? $result['username'] : 'Guest';
        if (!isset($userCounts[$username])) {
            $userCounts[$username] = 0;
            $userScores[$username] = 0;
        }
        $userCounts[$username]++;
        $userScores[$username] += $result['percentage'];
        
        // Count perfect scores
        if ($result['percentage'] == 100) {
            $stats['perfectScores']++;
        }
    }
    
    $stats['avgScore'] = round($totalPercentage / count($results));
    
    // Find most active user
    if (!empty($userCounts)) {
        $stats['mostActiveUser'] = array_keys($userCounts, max($userCounts))[0];
        
        // Calculate average scores per user
        $avgUserScores = [];
        foreach ($userScores as $user => $totalScore) {
            $avgUserScores[$user] = round($totalScore / $userCounts[$user]);
        }
        
        // Get top 3 performers
        arsort($avgUserScores);
        $stats['topPerformers'] = array_slice($avgUserScores, 0, 3, true);
    }
    
    return $stats;
}

/**
 * Get popular tags from results
 */
function getPopularTags($results) {
    $tagCounts = [];
    
    foreach ($results as $result) {
        foreach ($result['tags'] as $tag) {
            if (!isset($tagCounts[$tag])) {
                $tagCounts[$tag] = 0;
            }
            $tagCounts[$tag]++;
        }
    }
    
    arsort($tagCounts);
    return array_slice($tagCounts, 0, 5, true);
}
?> 