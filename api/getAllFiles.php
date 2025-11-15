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
            t.device_name,
            t.serial_number,
            u_added.username AS added_by,
            u_ticket.first_name AS ticket_user_first_name,
            u_ticket.last_name AS ticket_user_last_name,
            ta.created_at
        FROM ticket_attachments ta
        LEFT JOIN tickets t ON ta.ticket_id = t.id
        LEFT JOIN users u_added ON ta.user_id = u_added.id
        LEFT JOIN users u_ticket ON t.user_id = u_ticket.id
        ORDER BY ta.created_at DESC";

$result = $conn->query($sql);
$files = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
}

echo json_encode($files);
