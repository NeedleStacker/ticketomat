<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$q = $conn->prepare("SELECT attachment, attachment_name, attachment_type FROM tickets WHERE id = ?");
$q->bind_param("i", $id);
$q->execute();
$q->store_result();
$q->bind_result($attachment, $attachment_name, $attachment_type);
$q->fetch();

if ($q->num_rows > 0 && !empty($attachment_name)) {
    header("Content-Type: " . $attachment_type);
    header("Content-Disposition: attachment; filename=\"" . $attachment_name . "\"");
    echo $attachment;
} else {
    header("HTTP/1.0 404 Not Found");
}
