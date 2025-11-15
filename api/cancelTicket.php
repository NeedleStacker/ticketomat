<?php
require_once("config.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$id     = isset($input["id"]) ? intval($input["id"]) : 0;
$reason = isset($input["reason"]) ? trim($input["reason"]) : "";

if (!$id) {
  echo json_encode(array("error" => "Nedostaje ID ticketa."));
  exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$sql = "
  UPDATE tickets
     SET status='otkazan',
         is_locked=1,
         cancel_reason=?,
         canceled_at=NOW()
   WHERE id=?
     AND status NOT IN ('zatvoren','riješen','otkazan')";

if ($user_role != 'admin') {
    $sql .= " AND user_id=?";
}

$q = $conn->prepare($sql);

if ($user_role != 'admin') {
    $q->bind_param("sii", $reason, $id, $user_id);
} else {
    $q->bind_param("si", $reason, $id);
}

$ok = $q->execute();

if ($ok && $q->affected_rows > 0) {
  echo json_encode(array("success" => true, "message" => "Ticket je otkazan."));
} else {
  echo json_encode(array("error" => "Ticket nije moguće otkazati (možda je već zatvoren ili ne postoji)."));
}
?>
