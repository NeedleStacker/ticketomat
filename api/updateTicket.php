<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data["id"]) ? intval($data["id"]) : 0;
$status = isset($data["status"]) ? clean($data["status"], $conn) : "";
$priority = isset($data["priority"]) ? clean($data["priority"], $conn) : "";
$description = isset($data["description"]) ? clean($data["description"], $conn) : "";

if ($id <= 0) {
    echo json_encode(array("error" => "Neispravan ID ticketa."));
    exit;
}

// ✅ dozvoljene vrijednosti
$allowed_status = array("open", "in_progress", "resolved", "closed");
$allowed_priority = array("low", "medium", "high");

if (!in_array($status, $allowed_status)) $status = "open";
if (!in_array($priority, $allowed_priority)) $priority = "medium";

// provjera postoji li ticket
$check = $conn->prepare("SELECT id FROM tickets WHERE id=? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();
if ($check->num_rows == 0) {
    echo json_encode(array("error" => "Ticket ne postoji."));
    exit;
}

// ažuriranje
$q = $conn->prepare("UPDATE tickets SET status=?, priority=?, description=? WHERE id=?");
$q->bind_param("sssi", $status, $priority, $description, $id);

if ($q->execute()) {
    echo json_encode(array("success" => true, "message" => "Ticket ažuriran."));
} else {
    echo json_encode(array("error" => "Neuspjelo ažuriranje", "sql_error" => $conn->error));
}
