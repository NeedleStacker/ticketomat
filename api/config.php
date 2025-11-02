<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); // log u istom folderu

header("Content-Type: application/json; charset=utf-8");
// MySQL konfiguracija
$db_host = "localhost";
$db_user = "liveinsb_tickets";
$db_pass = "Tickets!00";
$db_name = "liveinsb_tickets_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(array("error" => "DB connection failed: " . $conn->connect_error)));
}
mysqli_set_charset($conn, "utf8");

header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set('Europe/Zagreb');
