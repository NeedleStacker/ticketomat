<?php
error_reporting(0);
require_once("config.php");
require_once("functions.php");
checkApiKey();

$title = isset($_POST["title"]) ? clean(stripslashes($_POST["title"]), $conn) : "";
$description = isset($_POST["description"]) ? clean(stripslashes($_POST["description"]), $conn) : "";
$device_name = isset($_POST["device_name"]) ? clean(stripslashes($_POST["device_name"]), $conn) : "";
$serial_number = isset($_POST["serial_number"]) ? clean(stripslashes($_POST["serial_number"]), $conn) : "";
$user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;
$request_creator = isset($_POST["request_creator"]) ? clean(stripslashes($_POST["request_creator"]), $conn) : "";
$creator_contact = isset($_POST["creator_contact"]) ? clean(stripslashes($_POST["creator_contact"]), $conn) : "";
$status = isset($_POST["status"]) ? clean($_POST["status"], $conn) : "Otvoren";

if ($title == "" || $device_name == "" || $serial_number == "" || $user_id <= 0) {
  echo json_encode(array("error" => "Nedostaju obavezni podaci."));
  exit;
}

$attachment = null;
$attachment_name = null;
$attachment_type = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
        echo json_encode(["error" => "Datoteka je prevelika. Maksimalna veličina je 5MB."]);
        exit;
    }
    $attachment = file_get_contents($_FILES['attachment']['tmp_name']);
    $attachment_name = $_FILES['attachment']['name'];
    $attachment_type = $_FILES['attachment']['type'];
}

$q = $conn->prepare("
  INSERT INTO tickets (title, description, device_name, serial_number, user_id, status, priority, created_at, request_creator, creator_contact, attachment, attachment_name, attachment_type)
  VALUES (?, ?, ?, ?, ?, ?, 'medium', NOW(), ?, ?, ?, ?, ?)
");
$q->bind_param("ssssisssbss", $title, $description, $device_name, $serial_number, $user_id, $status, $request_creator, $creator_contact, $attachment, $attachment_name, $attachment_type);

if ($q->execute()) {
  echo json_encode(array("success" => true, "id" => $q->insert_id));
} else {
  echo json_encode(array("error" => "Neuspjelo dodavanje ticketa", "sql_error" => $conn->error));
}
