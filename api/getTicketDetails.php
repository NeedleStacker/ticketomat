<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if (!$id) {
  echo json_encode(array("error" => "Nedostaje ID ticketa."));
  exit;
}

$q = $conn->prepare("
  SELECT t.*,
         u.first_name, u.last_name, u.email, u.phone
    FROM tickets t
    LEFT JOIN users u ON u.id = t.user_id
   WHERE t.id = ?
   LIMIT 1
");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows == 0) {
  echo json_encode(array("error" => "Ticket nije pronađen."));
  exit;
}

$ticket = $res->fetch_assoc();

echo json_encode(array(
  "id"            => $ticket["id"],
  "title"         => $ticket["title"],
  "description"   => $ticket["description"],
  "device_name"   => $ticket["device_name"],
  "serial_number" => $ticket["serial_number"],
  "status"        => $ticket["status"],
  "priority"      => $ticket["priority"],
  "created_at"    => $ticket["created_at"],
  "canceled_at"   => $ticket["canceled_at"],
  "cancel_reason" => $ticket["cancel_reason"],
  "first_name"    => $ticket["first_name"],
  "last_name"     => $ticket["last_name"],
  "email"         => $ticket["email"],
  "phone"         => $ticket["phone"],
  "request_creator" => $ticket["request_creator"],
  "creator_contact" => $ticket["creator_contact"]
));
?>
