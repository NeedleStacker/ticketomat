<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$role = isset($_GET["role"]) ? $_GET["role"] : "client";

if ($role === "admin") {
    $sql = "SELECT t.*, u.username FROM tickets t LEFT JOIN users u ON u.id = t.user_id WHERE t.status != 'otkazan' ORDER BY t.id DESC";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT * FROM tickets WHERE user_id=$user_id ORDER BY id DESC";
    $result = $conn->query($sql);
}

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
echo json_encode($tickets);
