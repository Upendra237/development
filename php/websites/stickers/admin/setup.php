<?php
/**
 * Admin Setup Script
 * 
 * This script creates the initial admin user account.
 * For security, rename or delete this file after first use.
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Default admin credentials
$defaultUsername = 'admin';
$defaultPassword = 'adminpass'; // This should be changed immediately after first login

// Check if admin user already exists
$existingAdmin = dbQuery('SELECT COUNT(*) as count FROM admin_users');

if (empty($existingAdmin) || $existingAdmin[0]['count'] === 0) {
    // Initialize the admin_users table
    $db = getDb();
    
    // Ensure the admin_users table exists
    $db->exec('CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        last_login TIMESTAMP
    )');
    
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validate inputs
        if (empty($username)) {
            $errors[] = 'Username is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } else if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If validation passes, create the admin user
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $result = dbExecute(
                'INSERT INTO admin_users (username, password_hash, last_login) VALUES (?, ?, CURRENT_TIMESTAMP)',
                [$username, $passwordHash]
            );
            
            if ($result) {
                $success = true;
            } else {
                $errors[] = 'Failed to create admin user. Please try again.';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .setup-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-header h1 {
            margin-bottom: 10px;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        .error-list {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .error-list ul {
            margin: 10px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Admin Setup</h1>
            <p>Create your first admin account to manage the sticker shop</p>
        </div>
        
        <div class="warning">
            <strong>Security Warning:</strong> After creating your admin account, please rename or delete this setup file to prevent unauthorized access.
        </div>
        
        <?php if (isset($success)): ?>
        <div class="success-message">
            <h2>✅ Admin Created Successfully!</h2>
            <p>You can now <a href="login.php">log in</a> with your new credentials.</p>
            <p><strong>Important:</strong> Please delete or rename this setup file immediately.</p>
        </div>
        <?php elseif (!empty($errors)): ?>
        <div class="error-list">
            <strong>Please fix the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!isset($success)): ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo $username ?? $defaultUsername; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn primary-btn" style="width: 100%;">Create Admin Account</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
} else {
    // Admin users already exist, show error
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .setup-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Setup Not Required</h1>
        <div class="message message-error">
            <p>Admin users already exist in the database.</p>
            <p>If you've lost your credentials, you'll need to reset them directly in the database.</p>
        </div>
        <p><a href="login.php" class="btn primary-btn">Go to Login</a></p>
    </div>
</body>
</html>
<?php
}
?>