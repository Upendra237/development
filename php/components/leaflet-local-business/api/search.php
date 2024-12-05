<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = isset($_GET['q']) ? strtolower($_GET['q']) : '';
$data = getJsonData('businesses.json');

if ($query) {
    $data['businesses'] = array_filter($data['businesses'], function($business) use ($query) {
        return strpos(strtolower($business['name']), $query) !== false ||
               strpos(strtolower($business['description']), $query) !== false ||
               strpos(strtolower($business['address']), $query) !== false;
    });
}

sendJsonResponse($data);