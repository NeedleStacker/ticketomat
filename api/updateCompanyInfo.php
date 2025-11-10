<?php
require_once '../includes/db.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$name = $_POST['company_name'] ?? '';
$address = $_POST['company_address'] ?? '';
$oib = $_POST['company_oib'] ?? '';
$phone = $_POST['company_phone'] ?? '';
$email = $_POST['company_email'] ?? '';
$logo = null;

if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
    if ($_FILES['company_logo']['type'] == 'image/jpeg') {
        $logo = file_get_contents($_FILES['company_logo']['tmp_name']);
    } else {
        echo json_encode(['error' => 'Logo must be in JPG format.']);
        exit();
    }
}

// Check if there is any data to update
$conn->query("DELETE FROM company_info");

$stmt = $conn->prepare("INSERT INTO company_info (name, address, oib, phone, email, logo) VALUES (?, ?, ?, ?, ?, ?)");
$null = NULL;
$stmt->bind_param("sssssb", $name, $address, $oib, $phone, $email, $null);

if ($logo) {
    $stmt->send_long_data(5, $logo);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
