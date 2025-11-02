<?php
session_start();
require_once("config.php");
require_once("functions.php");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo "Invalid ticket ID.";
    exit;
}

$q = $conn->prepare("SELECT attachment, attachment_name, attachment_type FROM tickets WHERE id = ?");
$q->bind_param("i", $ticket_id);
$q->execute();
$q->store_result();
$q->bind_result($attachment, $attachment_name, $attachment_type);

if ($q->fetch()) {
    if ($attachment) {
        header("Content-Type: " . $attachment_type);
        header("Content-Disposition: attachment; filename=\"" . $attachment_name . "\"");
        header("Content-Length: " . strlen($attachment));
        echo $attachment;
        exit;
    } else {
        http_response_code(404);
        echo "No attachment found for this ticket.";
    }
} else {
    http_response_code(404);
    echo "Ticket not found.";
}
