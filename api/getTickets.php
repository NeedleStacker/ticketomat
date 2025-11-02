<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$role = isset($_GET["role"]) ? $_GET["role"] : "client";

if ($role === "admin") {
    $status = isset($_GET["status"]) ? clean($_GET["status"], $conn) : '';
    $search = isset($_GET["search"]) ? $_GET["search"] : '';

    $sql = "SELECT t.id, t.title, t.status, t.created_at, t.device_name, t.serial_number, t.priority, u.username FROM tickets t LEFT JOIN users u ON u.id = t.user_id";
    $where = [];
    $params = [];
    $types = '';

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

    if (!empty($search)) {
        $search_term = "%" . $search . "%";
        $where[] = "(t.title LIKE ? OR t.device_name LIKE ? OR t.serial_number LIKE ? OR t.description LIKE ? OR t.request_creator LIKE ?)";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $search_term;
            $types .= 's';
        }
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
    $q = $conn->prepare("SELECT id, title, status, created_at, attachment_name, device_name, serial_number FROM tickets WHERE user_id = ? ORDER BY id DESC");
    $q->bind_param("i", $user_id);
    $q->execute();
    $result = $q->get_result();
}

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
echo json_encode($tickets);
