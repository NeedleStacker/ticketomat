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

$username = $_POST['username'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$company = $_POST['company'] ?? '';
$company_oib = $_POST['company_oib'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$note = $_POST['note'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'client';

if (empty($username) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(['error' => 'Korisničko ime, email, lozinka i uloga su obavezni.']);
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, phone, company, company_oib, address, city, postal_code, note, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssssss", $username, $first_name, $last_name, $email, $phone, $company, $company_oib, $address, $city, $postal_code, $note, $password_hash, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Greška pri dodavanju korisnika: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
