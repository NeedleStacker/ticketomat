<?php
include 'config.php';
include 'functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$response = [];

if ($id <= 0 || empty($name)) {
    $response['error'] = 'Invalid input provided.';
    echo json_encode($response);
    exit();
}

$uploadDir = '../img/devices/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileName = basename($_FILES['image']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png'];

    if (in_array($fileExtension, $allowedExtensions)) {
        // Create a unique filename to prevent overwriting existing files
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        // Before moving, get the old image path to delete it after successful update
        $stmt = $conn->prepare("SELECT image_path FROM devices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $oldImagePath = $result->fetch_assoc()['image_path'];
        }
        $stmt->close();

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'img/devices/' . $newFileName;
            // Delete old image if it exists
            if (!empty($oldImagePath) && file_exists('../' . $oldImagePath)) {
                unlink('../' . $oldImagePath);
            }
        } else {
            $response['error'] = 'Failed to upload image.';
            echo json_encode($response);
            exit();
        }
    } else {
        $response['error'] = 'Invalid file type. Only JPG, JPEG, and PNG are allowed.';
        echo json_encode($response);
        exit();
    }
}


if ($imagePath) {
    $stmt = $conn->prepare("UPDATE devices SET name = ?, image_path = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $imagePath, $id);
} else {
    $stmt = $conn->prepare("UPDATE devices SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
}

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['error'] = "Database error: " . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>