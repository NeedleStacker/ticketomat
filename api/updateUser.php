<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$id = $_POST['id'] ?? '';
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

if (empty($id) || empty($username) || empty($email) || empty($role)) {
    echo json_encode(['error' => 'Nedostaju obavezni podaci.']);
    exit();
}

if (!empty($password)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, company = ?, company_oib = ?, address = ?, city = ?, postal_code = ?, note = ?, password_hash = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssssssssssssi", $username, $first_name, $last_name, $email, $phone, $company, $company_oib, $address, $city, $postal_code, $note, $password_hash, $role, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, company = ?, company_oib = ?, address = ?, city = ?, postal_code = ?, note = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssssi", $username, $first_name, $last_name, $email, $phone, $company, $company_oib, $address, $city, $postal_code, $note, $role, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Greška pri ažuriranju korisnika: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
