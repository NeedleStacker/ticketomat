<?php
require_once("config.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Nedostaje ID ticketa."]);
    exit;
}

// Check ticket status to ensure attachments can be added
$q = $conn->prepare("SELECT status FROM tickets WHERE id = ?");
$q->bind_param("i", $ticket_id);
$q->execute();
$q->bind_result($status);
$q->fetch();
$q->close();

if ($status !== 'Otvoren' && $status !== 'U tijeku') {
    http_response_code(403);
    echo json_encode(["error" => "Datoteku je moguće dodati samo na tickete sa statusom 'Otvoren' ili 'U tijeku'."]);
    exit;
}

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    if ($_FILES['attachment']['size'] > 10 * 1024 * 1024) { // 10MB limit
        http_response_code(400);
        echo json_encode(["error" => "Datoteka je prevelika. Maksimalna veličina je 10MB."]);
        exit;
    }

    $attachment = file_get_contents($_FILES['attachment']['tmp_name']);
    if ($attachment === false) {
        http_response_code(500);
        echo json_encode(["error" => "Nije uspjelo čitanje datoteke."]);
        exit;
    }

    $attachment_name = $_FILES['attachment']['name'];
    $attachment_type = $_FILES['attachment']['type'];
    $user_id = $_SESSION['user_id'];
    $null = NULL;

    $stmt = $conn->prepare("INSERT INTO ticket_attachments (ticket_id, user_id, attachment_name, attachment_type, attachment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iis sb", $ticket_id, $user_id, $attachment_name, $attachment_type, $null);
    $stmt->send_long_data(4, $attachment);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Datoteka uspješno dodana."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Greška prilikom spremanja datoteke.", "sql_error" => $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(400);
    $file_error = isset($_FILES['attachment']['error']) ? $_FILES['attachment']['error'] : 'Unknown error';
    echo json_encode(["error" => "Nije odabrana datoteka ili je došlo do greške prilikom prijenosa.", "file_error" => $file_error]);
}
