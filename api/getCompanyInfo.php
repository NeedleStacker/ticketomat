<?php
require_once("config.php");
require_once("functions.php");

header('Content-Type: application/json');

$sql = "SELECT name, address, oib, phone, email, logo FROM company_info ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $company_info = $result->fetch_assoc();
    if ($company_info['logo']) {
        $company_info['logo'] = base64_encode($company_info['logo']);
    }
    echo json_encode($company_info);
} else {
    echo json_encode(["error" => "No company information found."]);
}

$conn->close();
