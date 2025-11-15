<?php
require_once("config.php");
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$sql = "SELECT
            ta.id,
            ta.attachment_name,
            ta.ticket_id,
            t.title AS ticket_title,
            u.username AS added_by,
            ta.created_at
        FROM ticket_attachments ta
        LEFT JOIN tickets t ON ta.ticket_id = t.id
        LEFT JOIN users u ON ta.user_id = u.id
        ORDER BY ta.created_at DESC";

$result = $conn->query($sql);
$files = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
}

echo json_encode($files);
