<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$role = isset($_GET["role"]) ? $_GET["role"] : "client";

if ($role === "admin") {
    $show_canceled = isset($_GET["show_canceled"]) && $_GET["show_canceled"] === 'true';
    $status = isset($_GET["status"]) ? clean($_GET["status"], $conn) : '';

    $sql = "SELECT t.*, u.username FROM tickets t LEFT JOIN users u ON u.id = t.user_id";
    $where = [];

    if (!$show_canceled) {
        $where[] = "t.status != 'Otkazan'";
    }
    if (!empty($status)) {
        $where[] = "t.status = '$status'";
    }
    if ($user_id > 0) {
        $where[] = "t.user_id = $user_id";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY t.id DESC";

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
