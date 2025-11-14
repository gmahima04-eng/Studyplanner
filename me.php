<?php
// api/me.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

session_start();

if (!empty($_SESSION['user_id'])) {
  echo json_encode([
    "loggedIn" => true,
    "user" => [
      "id" => (int)$_SESSION['user_id'],
      "name" => $_SESSION['user_name'] ?? null
    ]
  ]);
} else {
  echo json_encode(["loggedIn" => false]);
}
?>
