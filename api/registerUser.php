<?php
require_once("config.php");
require_once("functions.php");
checkApiKey();

$data = json_decode(file_get_contents("php://input"), true);

$first_name  = clean($data["first_name"], $conn);
$last_name   = clean($data["last_name"], $conn);
$company     = clean($data["company"], $conn);
$company_oib = clean($data["company_oib"], $conn);
$city        = clean($data["city"], $conn);
$address     = clean($data["address"], $conn);
$postal_code = clean($data["postal_code"], $conn);
$email       = clean($data["email"], $conn);
$phone       = clean($data["phone"], $conn);
$note        = clean($data["note"], $conn);
$username    = clean($data["username"], $conn);
$password    = $data["password"];

if ($username == "" || $password == "" || $email == "") {
    echo json_encode(["error" => "Korisničko ime, lozinka i email su obavezni."]);
    exit;
}

// Provjeri postoji li korisnik s istim imenom ili emailom
$check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["error" => "Korisničko ime ili email već postoji."]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$q = $conn->prepare("INSERT INTO users 
(first_name, last_name, company, company_oib, city, address, postal_code, email, phone, note, username, password_hash, role, is_active)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'client', 1)");

$q->bind_param("ssssssssssss",
  $first_name, $last_name, $company, $company_oib,
  $city, $address, $postal_code, $email, $phone,
  $note, $username, $password_hash
);

if ($q->execute()) {
    echo json_encode(["success" => true, "user_id" => $q->insert_id]);
} else {
    echo json_encode(["error" => "Registracija nije uspjela", "sql_error" => $conn->error]);
}
