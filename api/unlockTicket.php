<?php
require_once("config.php");
require_once("functions.php");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
// Check if user is an admin
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = $data['ticket_id'] ?? 0;
$password = $data['password'] ?? '';
$unlock_reason = $data['unlock_reason'] ?? '';

if (empty($password) || $ticket_id <= 0 || empty($unlock_reason)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ticket ID, password, and reason are required.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// First, verify the admin's password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Admin user not found.']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$hashed_password = $user['password'];
$stmt->close();

if (!password_verify($password, $hashed_password)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
    $conn->close();
    exit();
}

// Password is correct, now unlock the ticket
$stmt_unlock = $conn->prepare("UPDATE tickets SET is_locked = 0, unlock_reason = ? WHERE id = ?");
$stmt_unlock->bind_param("si", $unlock_reason, $ticket_id);

if ($stmt_unlock->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ticket unlocked successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to unlock the ticket.']);
}

$stmt_unlock->close();
$conn->close();
?>
