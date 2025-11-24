<?php
session_start();

require "db.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(["success" => false, "error" => "All fields required"]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "error" => "Password must be at least 6 characters"]);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Email already registered"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $name, $email, $hashed);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    $_SESSION["user_id"] = $user_id;
    $_SESSION["user_email"] = $email;
    $_SESSION["user_name"] = $name;
    echo json_encode(["success" => true, "message" => "Account created successfully"]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
