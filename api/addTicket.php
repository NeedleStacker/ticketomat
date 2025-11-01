<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$data = json_decode(file_get_contents("php://input"), true);

$title = isset($data["title"]) ? clean(stripslashes($data["title"]), $conn) : "";
$description = isset($data["description"]) ? clean(stripslashes($data["description"]), $conn) : "";
$device_name = isset($data["device_name"]) ? clean(stripslashes($data["device_name"]), $conn) : "";
$serial_number = isset($data["serial_number"]) ? clean(stripslashes($data["serial_number"]), $conn) : "";
$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
$request_creator = isset($data["request_creator"]) ? clean(stripslashes($data["request_creator"]), $conn) : "";
$creator_contact = isset($data["creator_contact"]) ? clean(stripslashes($data["creator_contact"]), $conn) : "";
$status = isset($data["status"]) ? clean($data["status"], $conn) : "Otvoren";

if ($title == "" || $device_name == "" || $serial_number == "" || $user_id <= 0) {
  echo json_encode(array("error" => "Nedostaju obavezni podaci."));
  exit;
}

$q = $conn->prepare("
  INSERT INTO tickets (title, description, device_name, serial_number, user_id, status, priority, created_at, request_creator, creator_contact)
  VALUES (?, ?, ?, ?, ?, ?, 'medium', NOW(), ?, ?)
");
$q->bind_param("ssssisss", $title, $description, $device_name, $serial_number, $user_id, $status, $request_creator, $creator_contact);

if ($q->execute()) {
  echo json_encode(array("success" => true, "id" => $q->insert_id));
} else {
  echo json_encode(array("error" => "Neuspjelo dodavanje ticketa", "sql_error" => $conn->error));
}
