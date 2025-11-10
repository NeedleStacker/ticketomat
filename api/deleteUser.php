<?php
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['error' => 'ID korisnika je obavezan.']);
    exit();
}

// Prevent deleting the main admin user (if needed, based on ID or username)
// For example, if admin user has ID 1
if ($id == 1) {
    echo json_encode(['error' => 'Nije moguće obrisati glavnog administratora.']);
    exit();
}


$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Korisnik nije pronađen.']);
    }
} else {
    echo json_encode(['error' => 'Greška pri brisanju korisnika: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
