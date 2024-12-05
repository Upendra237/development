<?php
define('BASE_URL', 'http://localhost/local-business-map');
define('DEFAULT_LAT', 40.7128);
define('DEFAULT_LNG', -74.0060);
define('DEFAULT_ZOOM', 13);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

function getJsonData($filename) {
    $filepath = __DIR__ . '/../data/' . $filename;
    if (!file_exists($filepath)) {
        sendJsonResponse(['error' => 'Data file not found'], 404);
    }
    return json_decode(file_get_contents($filepath), true);
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
