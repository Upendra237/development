<?php
/**
 * Get Presets API
 * Returns JSON data of quiz presets for use by the UI
 */

require_once 'config.php';
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get presets from DB
$presets = getPresets();

// Output presets as JSON
echo json_encode($presets);
?> 