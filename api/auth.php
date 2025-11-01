<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$data = json_decode(file_get_contents("php://input"), true);
$username = isset($data["username"]) ? clean($data["username"], $conn) : "";
$password = isset($data["password"]) ? $data["password"] : "";

if ($username == "" || $password == "") {
    echo json_encode(array("error" => "Missing credentials"));
    exit;
}

$q = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username=? AND is_active=1 LIMIT 1");
$q->bind_param("s", $username);
$q->execute();
$res = $q->get_result();
if ($res->num_rows == 0) {
    echo json_encode(array("error" => "Invalid username"));
    exit;
}
$user = $res->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(array("error" => "Wrong password"));
    exit;
}
unset($user['password_hash']);
echo json_encode(array("success" => true, "user" => $user));
