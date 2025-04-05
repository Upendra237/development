<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if request is AJAX
if (!isAjaxRequest()) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Get all tags from questions
$tags = getAllTags();

// Return tags as JSON
jsonResponse(['tags' => $tags]);
?> 