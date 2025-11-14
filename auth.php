<?php
// api/auth.php - login endpoint
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password required"]);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => $conn->error]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    $stmt->close();
    exit;
}

$stmt->bind_result($id, $name, $hash);
$stmt->fetch();
$stmt->close();

if (!password_verify($password, $hash)) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

// success - create session
session_start();
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$id;
$_SESSION['user_name'] = $name;

echo json_encode([
    "loggedIn" => true,
    "user" => [
        "id" => (int)$id,
        "name" => $name,
        "email" => $email
    ]
]);
?>