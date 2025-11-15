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
$ticket_id = $data['ticket_id'] ?? 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Ticket ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE tickets SET is_locked = 1 WHERE id = ?");
$stmt->bind_param("i", $ticket_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found or no change made.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
