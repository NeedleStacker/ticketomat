<?php
require_once("config.php");
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id > 0) {
    // Optional: First, delete the associated image file if it exists
    $stmt = $conn->prepare("SELECT image_path FROM devices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    if ($stmt->fetch() && $image_path && file_exists("../" . $image_path)) {
        unlink("../" . $image_path);
    }
    $stmt->close();

    // Now, delete the device from the database
    $stmt = $conn->prepare("DELETE FROM devices WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid ID"]);
}
