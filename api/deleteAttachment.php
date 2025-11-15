<?php
require_once("config.php");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$attachment_id = $data['attachment_id'] ?? 0;

if ($attachment_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Attachment ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM ticket_attachments WHERE id = ?");
$stmt->bind_param("i", $attachment_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Attachment not found.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
