<?php
require_once("config.php");
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

$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data["id"]) ? intval($data["id"]) : 0;
$status = isset($data["status"]) ? $data["status"] : "";
$priority = isset($data["priority"]) ? $data["priority"] : "";
$description = isset($data["description"]) ? $data["description"] : "";
$device_name = isset($data["device_name"]) ? $data["device_name"] : "";
$serial_number = isset($data["serial_number"]) ? $data["serial_number"] : "";
$cancel_reason = isset($data["cancel_reason"]) ? $data["cancel_reason"] : null;

if ($id <= 0) {
    echo json_encode(array("error" => "Neispravan ID ticketa."));
    exit;
}

// ✅ dozvoljene vrijednosti
$allowed_status = array("Otvoren", "U tijeku", "Riješen", "Zatvoren", "Otkazan");
$allowed_priority = array("low", "medium", "high");

if (!in_array($status, $allowed_status)) $status = "Otvoren";
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

// Lock ticket if status is 'Riješen' or 'Otkazan'
$is_locked = ($status === 'Riješen' || $status === 'Otkazan') ? 1 : 0;

// If status is 'Otkazan', also update cancel_reason and canceled_at
if ($status === 'Otkazan') {
    $q = $conn->prepare("UPDATE tickets SET status=?, priority=?, description=?, device_name=?, serial_number=?, is_locked=?, cancel_reason=?, canceled_at=NOW() WHERE id=?");
    $q->bind_param("sssssisi", $status, $priority, $description, $device_name, $serial_number, $is_locked, $cancel_reason, $id);
} else {
    // For other statuses, ensure cancel_reason and canceled_at are cleared and ticket is unlocked
    $q = $conn->prepare("UPDATE tickets SET status=?, priority=?, description=?, device_name=?, serial_number=?, is_locked=?, cancel_reason=NULL, canceled_at=NULL WHERE id=?");
    $q->bind_param("sssssii", $status, $priority, $description, $device_name, $serial_number, $is_locked, $id);
}

if ($q->execute()) {
    echo json_encode(array("success" => true, "message" => "Ticket ažuriran."));
} else {
    echo json_encode(array("error" => "Neuspjelo ažuriranje", "sql_error" => $conn->error));
}
