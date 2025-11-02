<?php
require_once("config.php");
require_once("functions.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$id       = isset($input["id"]) ? intval($input["id"]) : 0;
$reason   = isset($input["reason"]) ? trim($input["reason"]) : "";
$user_id  = isset($input["user_id"]) ? intval($input["user_id"]) : 0;

if (!$id || !$user_id) {
  echo json_encode(array("error" => "Nedostaju podaci."));
  exit;
}

$q = $conn->prepare("
  UPDATE tickets 
     SET status='otkazan',
         cancel_reason=?,
         canceled_at=NOW()
   WHERE id=? 
     AND user_id=? 
     AND status NOT IN ('zatvoren','riješen','otkazan')
");
$q->bind_param("sii", $reason, $id, $user_id);
$ok = $q->execute();

if ($ok && $q->affected_rows > 0) {
  echo json_encode(array("success" => true, "message" => "Ticket je otkazan."));
} else {
  echo json_encode(array("error" => "Ticket nije moguće otkazati (možda je već zatvoren ili ne postoji)."));
}
?>
