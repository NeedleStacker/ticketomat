<?php
require_once("config.php");
require_once("functions.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

header('Content-Type: application/json');

$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid Ticket ID"]);
    exit;
}

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT id, author_name, author_email, comment_text, created_at FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($comments);

$stmt->close();
$conn->close();
?>
