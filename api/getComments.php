<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$ticket_id = intval($_GET["ticket_id"]);
$q = $conn->prepare("SELECT c.*, u.username FROM ticket_comments c LEFT JOIN users u ON c.user_id=u.id WHERE c.ticket_id=? ORDER BY c.created_at ASC");
$q->bind_param("i", $ticket_id);
$q->execute();
$res = $q->get_result();
$comments = array();
while ($row = $res->fetch_assoc()) $comments[] = $row;
echo json_encode($comments);
