<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';
$data = getJsonData('categories.json');
sendJsonResponse($data);