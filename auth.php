<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$input = json_decode(file_get_contents("php://input"), true);

$email = $input["email"] ?? "";
$password = $input["password"] ?? "";

if (!$email || !$password) {
    echo json_encode(["status" => false, "message" => "Missing fields"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {
    echo json_encode(["status" => false, "message" => "Incorrect password"]);
    exit;
}

echo json_encode([
    "status" => true,
    "message" => "Login successful",
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"]
    ]
]);
?>
    