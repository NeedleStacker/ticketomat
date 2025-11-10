<?php
require_once __DIR__ . '/../includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$title = isset($_POST["title"]) ? $_POST["title"] : "";
$description = isset($_POST["description"]) ? $_POST["description"] : "";
$device_name = isset($_POST["device_name"]) ? $_POST["device_name"] : "";
$serial_number = isset($_POST["serial_number"]) ? $_POST["serial_number"] : "";
$user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;
$request_creator = isset($_POST["request_creator"]) ? $_POST["request_creator"] : "";
$creator_contact = isset($_POST["creator_contact"]) ? $_POST["creator_contact"] : "";
$status = isset($_POST["status"]) ? $_POST["status"] : "Otvoren";

if (empty($title) || empty($device_name) || empty($serial_number) || $user_id <= 0) {
  http_response_code(400);
  echo json_encode(["error" => "Nedostaju obavezni podaci."]);
  exit;
}

// 1. Insert the ticket without attachment info
$q = $conn->prepare("
  INSERT INTO tickets (title, description, device_name, serial_number, user_id, status, priority, created_at, request_creator, creator_contact)
  VALUES (?, ?, ?, ?, ?, ?, 'medium', NOW(), ?, ?)
");
$q->bind_param("ssssisss", $title, $description, $device_name, $serial_number, $user_id, $status, $request_creator, $creator_contact);

if ($q->execute()) {
    $ticket_id = $q->insert_id;

    // 2. If there is an attachment, insert it into the new table
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) { // 5MB limit
            // Note: The ticket is already created, but the attachment is rejected. This is an acceptable trade-off.
            http_response_code(400);
            echo json_encode(["error" => "Datoteka je prevelika. Maksimalna veličina je 5MB.", "ticket_id" => $ticket_id]);
            exit;
        }

        $attachment = file_get_contents($_FILES['attachment']['tmp_name']);
        if ($attachment === false) {
            http_response_code(500);
            echo json_encode(["error" => "Nije uspjelo čitanje datoteke.", "ticket_id" => $ticket_id]);
            exit;
        }

        $attachment_name = $_FILES['attachment']['name'];
        $attachment_type = $_FILES['attachment']['type'];
        $null = NULL;

        $stmt = $conn->prepare("INSERT INTO ticket_attachments (ticket_id, attachment_name, attachment_type, attachment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issb", $ticket_id, $attachment_name, $attachment_type, $null);
        $stmt->send_long_data(3, $attachment);

        if (!$stmt->execute()) {
            // Again, ticket is created but attachment failed. Inform the user.
            http_response_code(500);
            echo json_encode(["error" => "Greška prilikom spremanja datoteke.", "sql_error" => $stmt->error, "ticket_id" => $ticket_id]);
            exit;
        }
        $stmt->close();
    }

    echo json_encode(["success" => true, "id" => $ticket_id]);
} else {
  http_response_code(500);
  echo json_encode(["error" => "Neuspjelo dodavanje ticketa", "sql_error" => $conn->error]);
}

$q->close();
