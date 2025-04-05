<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear admin session
unset($_SESSION['admin_authenticated']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');