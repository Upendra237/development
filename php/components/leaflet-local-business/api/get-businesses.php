<?php
require_once '../includes/config.php';


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $data = getJsonData('businesses.json');

    if ($category) {
        $categories = explode(',', $category);
        $data['businesses'] = array_filter($data['businesses'], function($business) use ($categories) {
            return in_array($business['category'], $categories);
        });
    }

    sendJsonResponse($data);
} catch (Exception $e) {
    sendJsonResponse(['error' => $e->getMessage()], 500);
}