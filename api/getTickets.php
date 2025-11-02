<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$role = isset($_GET["role"]) ? $_GET["role"] : "client";

if ($role === "admin") {
    $show_canceled = isset($_GET["show_canceled"]) && $_GET["show_canceled"] === 'true';
    $status = isset($_GET["status"]) ? clean($_GET["status"], $conn) : '';

    $sql = "SELECT t.id, t.title, t.status, t.created_at, u.username FROM tickets t LEFT JOIN users u ON u.id = t.user_id";
    $where = [];
    $params = [];
    $types = '';

    if (!$show_canceled) {
        $where[] = "t.status != ?";
        $params[] = 'Otkazan';
        $types .= 's';
    }
    if (!empty($status)) {
        $where[] = "t.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($user_id > 0) {
        $where[] = "t.user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY t.id DESC";

    $q = $conn->prepare($sql);
    if (!empty($params)) {
        $q->bind_param($types, ...$params);
    }
    $q->execute();
    $result = $q->get_result();
} else {
    $q = $conn->prepare("SELECT id, title, status, created_at, attachment_name FROM tickets WHERE user_id = ? ORDER BY id DESC");
    $q->bind_param("i", $user_id);
    $q->execute();
    $result = $q->get_result();
}

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
echo json_encode($tickets);
