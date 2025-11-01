<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$sql = "SELECT id, username FROM users WHERE role = 'client' ORDER BY username";
$result = $conn->query($sql);

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
echo json_encode($clients);
