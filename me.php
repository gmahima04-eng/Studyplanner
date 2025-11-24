<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "loggedIn" => false]);
    exit();
}

echo json_encode([
    "success" => true,
    "loggedIn" => true,
    "user_id" => $_SESSION["user_id"],
    "email" => $_SESSION["user_email"] ?? null,
    "name" => $_SESSION["user_name"] ?? null
]);
?>
