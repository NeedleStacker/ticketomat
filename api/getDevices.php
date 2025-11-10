<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$sql = "SELECT * FROM devices ORDER BY id";
$result = $conn->query($sql);

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}
echo json_encode($devices);
