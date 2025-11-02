<?php
function getApiKeyHeader() {
    // kompatibilnost za Apache/PHP 5.6
    $key = null;
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['X-API-KEY'])) $key = $headers['X-API-KEY'];
        elseif (isset($headers['x-api-key'])) $key = $headers['x-api-key'];
    }
    // fallback – koristi $_SERVER varijable
    if (!$key && isset($_SERVER['HTTP_X_API_KEY'])) {
        $key = $_SERVER['HTTP_X_API_KEY'];
    }
    return $key;
}

function checkApiKey() {
    $key = getApiKeyHeader();
    if (!$key) {
        http_response_code(401);
        echo json_encode(array("error" => "API key required", "debug" => $_SERVER));
        exit;
    }
    if ($key !== VALID_API_KEY) {
        http_response_code(403);
        echo json_encode(array("error" => "Invalid API key", "received" => $key));
        exit;
    }
}

function clean($str) {
    return trim($str);
}
