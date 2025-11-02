<?php
require_once("config.php");
require_once("functions.php");
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$sql = "SELECT id, username FROM users WHERE role = 'client' ORDER BY username";
$result = $conn->query($sql);

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
echo json_encode($clients);
