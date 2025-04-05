<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ based Engineering Quiz</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MCQ based Engineering Quiz</h1>
        </header>
        
        <main>
            <div class="quiz-setup">
                <div class="decorative-element element-1"></div>
                <div class="decorative-element element-2"></div>
                
                <h2>Welcome</h2>
                <p class="intro-text">Test your engineering knowledge with our MCQ quizzes.</p>
                
                <form id="username-form" action="pages/select.php" method="GET">
                    <div class="form-group">
                        <label for="username">Enter your name to begin</label>
                        <input type="text" id="username" name="username" placeholder="Your name">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Start</button>
                </form>
            </div>
        </main>
        
        <footer>
            <p>Copyright &copy; <?php echo date('Y'); ?> Knowledge Sharing Circle</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html> 