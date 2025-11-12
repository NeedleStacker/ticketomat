<?php
require_once("config.php");
require_once("functions.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$sql = "SELECT * FROM devices ORDER BY id";
$result = $conn->query($sql);

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}
echo json_encode($devices);
