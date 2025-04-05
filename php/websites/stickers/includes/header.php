<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="site-brand">
                <a href="index.php" class="site-logo"><?php echo SITE_NAME; ?> Admin</a>
            </div>
            
            <nav class="admin-nav">
                <ul class="nav-links">
                    <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                    <li><a href="sticker-management.php" <?php echo basename($_SERVER['PHP_SELF']) === 'sticker-management.php' ? 'class="active"' : ''; ?>>Stickers</a></li>
                    <li><a href="order-management.php" <?php echo basename($_SERVER['PHP_SELF']) === 'order-management.php' ? 'class="active"' : ''; ?>>Orders</a></li>
                </ul>
                
                <div class="user-menu">
                    <button class="user-button" id="user-dropdown-toggle">
                        <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="index.php" class="dropdown-link">Dashboard</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-link logout-link">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    
    <main class="admin-content">
        <div class="container">