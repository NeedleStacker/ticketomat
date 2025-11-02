<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$sql = "SELECT * FROM devices ORDER BY id";
$result = $conn->query($sql);

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}
echo json_encode($devices);
