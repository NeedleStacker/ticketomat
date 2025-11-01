<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$username = clean($data["username"], $conn);
$password = $data["password"];

$q = $conn->prepare("SELECT id, username, password_hash, role, first_name, last_name FROM users WHERE username=? AND is_active=1 LIMIT 1");
$q->bind_param("s", $username);
$q->execute();
$res = $q->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["error" => "Korisnik ne postoji"]);
    exit;
}

$user = $res->fetch_assoc();
if (!password_verify($password, $user["password_hash"])) {
    echo json_encode(["error" => "Pogrešna lozinka"]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];

unset($user["password_hash"]);
echo json_encode(["success" => true, "user" => $user]);
