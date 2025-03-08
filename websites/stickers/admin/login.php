<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if already logged in
if (isAdminAuthenticated()) {
    redirect('index.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Check credentials
        $admin = dbQuery('SELECT id, username, password_hash FROM admin_users WHERE username = ?', [$username]);
        
        if (!empty($admin) && password_verify($password, $admin[0]['password_hash'])) {
            // Update last login
            dbExecute('UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?', [$admin[0]['id']]);
            
            // Set session
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_id'] = $admin[0]['id'];
            $_SESSION['admin_username'] = $admin[0]['username'];
            
            // Redirect to admin dashboard
            redirect('index.php');
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo SITE_NAME; ?> Admin</h1>
            <p>Enter your credentials to access the admin panel</p>
        </div>
        
        <?php if ($error): ?>
        <div class="message message-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn primary-btn">Log In</button>
        </form>
        
        <div class="login-footer">
            <a href="<?php echo SITE_URL; ?>">Back to main site</a>
        </div>
    </div>
</body>
</html>