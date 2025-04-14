<?php
// Application configuration

// Database paths
define('DATA_DIR', __DIR__ . '/../data/');
define('QUESTIONS_FILE', DATA_DIR . 'questions.json');
define('RESULTS_FILE', DATA_DIR . 'results.json');
define('PRESETS_FILE', DATA_DIR . 'presets.json');

// Application settings
define('APP_NAME', 'Engineering Knowledge Hub');
define('DEBUG_MODE', false);
define('MAX_QUESTIONS', 20);
define('ADMIN_PASSWORD', 'KhEC237');

// Error handling
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
} 