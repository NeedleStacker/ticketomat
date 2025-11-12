<?php
require_once("config.php");
require_once("functions.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// The ID is now the attachment ID, not the ticket ID.
$attachment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($attachment_id <= 0) {
    http_response_code(400);
    echo "Invalid attachment ID.";
    exit;
}

$stmt = $conn->prepare("SELECT attachment, attachment_name, attachment_type FROM ticket_attachments WHERE id = ?");
$stmt->bind_param("i", $attachment_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($attachment, $attachment_name, $attachment_type);

if ($stmt->fetch()) {
    if ($attachment) {
        header("Content-Type: " . $attachment_type);
        header("Content-Disposition: attachment; filename=\"" . $attachment_name . "\"");
        // Note: strlen() on a longblob might be memory-intensive for large files, but is okay for this use case.
        header("Content-Length: " . strlen($attachment));
        echo $attachment;
        exit;
    } else {
        http_response_code(404);
        echo "Attachment content not found.";
    }
} else {
    http_response_code(404);
    echo "Attachment not found.";
}

$stmt->close();
