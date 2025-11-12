<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_log("loginUser.php: Script started");

require_once __DIR__ . '/../includes/db.php';
error_log("loginUser.php: db.php included successfully");

session_start();

$data = json_decode(file_get_contents("php://input"), true);
$username = $data["username"];
$password = $data["password"];

$stmt = $conn->prepare("SELECT id, username, password_hash, role, first_name, last_name FROM users WHERE username=? AND is_active=1 LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["error" => "Korisnik ne postoji"]);
    exit;
}

$user = $result->fetch_assoc();
if (!password_verify($password, $user["password_hash"])) {
    echo json_encode(["error" => "Pogrešna lozinka"]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];

unset($user["password_hash"]);
echo json_encode(["success" => true, "user" => $user]);
