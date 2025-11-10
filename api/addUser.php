<?php
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'client';

if (empty($username) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(['error' => 'Sva polja su obavezna.']);
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $password_hash, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Greška pri dodavanju korisnika: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
