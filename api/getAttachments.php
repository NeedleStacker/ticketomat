<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje ID ticketa."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, attachment_name, attachment_type FROM ticket_attachments WHERE ticket_id = ? ORDER BY id");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($attachments);
