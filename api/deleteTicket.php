<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id == 0) {
    echo json_encode(array("error" => "id required"));
    exit;
}
$q = $conn->prepare("DELETE FROM tickets WHERE id=?");
$q->bind_param("i", $id);
if ($q->execute()) echo json_encode(array("success" => true));
else echo json_encode(array("error" => "Delete failed"));
