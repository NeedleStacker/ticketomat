<?php
require_once("config.php");
require_once("functions.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = $data['comment_id'] ?? 0;

if ($comment_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Comment ID']);
    exit;
}

$conn = get_db_connection();
$stmt = $conn->prepare("DELETE FROM ticket_comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Komentar je uspješno obrisan.']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Komentar nije pronađen.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Došlo je do greške prilikom brisanja komentara.']);
}

$stmt->close();
$conn->close();
?>
