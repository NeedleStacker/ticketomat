<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$data = json_decode(file_get_contents("php://input"), true);
$name = isset($data["name"]) ? clean(stripslashes($data["name"]), $conn) : "";

if (empty($name)) {
    echo json_encode(["error" => "Ime aparata ne smije biti prazno."]);
    exit;
}

$q = $conn->prepare("INSERT INTO devices (name) VALUES (?)");
$q->bind_param("s", $name);

if ($q->execute()) {
    echo json_encode(["success" => true, "id" => $q->insert_id]);
} else {
    echo json_encode(["error" => "Greška prilikom dodavanja aparata.", "sql_error" => $conn->error]);
}
