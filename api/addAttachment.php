<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

if ($ticket_id <= 0) {
    echo json_encode(["error" => "Nedostaje ID ticketa."]);
    exit;
}

// Check ticket status
$q = $conn->prepare("SELECT status FROM tickets WHERE id = ?");
$q->bind_param("i", $ticket_id);
$q->execute();
$q->bind_result($status);
$q->fetch();
$q->close();

if ($status !== 'Otvoren' && $status !== 'U tijeku') {
    echo json_encode(["error" => "Datoteku je moguće dodati samo na tickete sa statusom 'Otvoren' ili 'U tijeku'."]);
    exit;
}

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
        echo json_encode(["error" => "Datoteka je prevelika. Maksimalna veličina je 5MB."]);
        exit;
    }
    $attachment = file_get_contents($_FILES['attachment']['tmp_name']);
    $attachment_name = $_FILES['attachment']['name'];
    $attachment_type = $_FILES['attachment']['type'];

    $update = $conn->prepare("UPDATE tickets SET attachment = ?, attachment_name = ?, attachment_type = ? WHERE id = ?");
    $update->bind_param("bssi", $attachment, $attachment_name, $attachment_type, $ticket_id);

    if ($update->execute()) {
        echo json_encode(["success" => true, "message" => "Datoteka uspješno dodana."]);
    } else {
        echo json_encode(["error" => "Greška prilikom spremanja datoteke."]);
    }
} else {
    echo json_encode(["error" => "Nije odabrana datoteka ili je došlo do greške prilikom prijenosa."]);
}
