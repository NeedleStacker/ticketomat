<?php
require_once("config.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = $data['ticket_id'] ?? 0;
$comment_text = $data['comment_text'] ?? '';
$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// Fetch user details from the database
$stmt_user = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

$author_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($author_name)) {
    $author_name = 'Nepoznat';
}
$author_email = $user['email'] ?? 'nepoznat@example.com';

// Validation
if ($ticket_id <= 0 || empty($comment_text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Poruka ne može biti prazna.']);
    exit;
}

if (strlen($comment_text) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Poruka ne smije biti duža od 2000 znakova.']);
    exit;
}

// Rate Limiting (1 comment per 10 seconds per IP)
$rate_limit_seconds = 10;
$stmt = $conn->prepare("SELECT last_comment_timestamp FROM comment_rate_limits WHERE ip_address = ?");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_comment_time = strtotime($row['last_comment_timestamp']);
    if (time() - $last_comment_time < $rate_limit_seconds) {
        http_response_code(429); // Too Many Requests
        echo json_encode(['error' => 'Možete poslati samo jednu poruku svakih 10 sekundi.']);
        $stmt->close();
        $conn->close();
        exit;
    }
}
$stmt->close();

// Sanitize input
$author_name_sanitized = htmlspecialchars($author_name, ENT_QUOTES, 'UTF-8');
$comment_text_sanitized = htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');

// Insert new comment
$stmt = $conn->prepare("INSERT INTO ticket_comments (ticket_id, author_name, author_email, comment_text) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $ticket_id, $author_name_sanitized, $author_email, $comment_text_sanitized);

if ($stmt->execute()) {
    // Update rate limit timestamp
    $stmt_rate = $conn->prepare("INSERT INTO comment_rate_limits (ip_address, last_comment_timestamp) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_comment_timestamp = NOW()");
    $stmt_rate->bind_param("s", $ip_address);
    $stmt_rate->execute();
    $stmt_rate->close();

    echo json_encode(['success' => true, 'message' => 'Poruka je uspješno dodana.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Došlo je do greške prilikom spremanja poruke.']);
}

$stmt->close();
$conn->close();
?>
