<?php
// public/api.php
$request_uri = $_SERVER['REQUEST_URI'];
$api_script = basename($request_uri);
$api_path = __DIR__ . '/../api/' . $api_script;

if (file_exists($api_path)) {
    require_once $api_path;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
