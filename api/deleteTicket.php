<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id == 0) {
    echo json_encode(array("error" => "id required"));
    exit;
}
$q = $conn->prepare("DELETE FROM tickets WHERE id=?");
$q->bind_param("i", $id);
if ($q->execute()) echo json_encode(array("success" => true));
else echo json_encode(array("error" => "Delete failed"));
