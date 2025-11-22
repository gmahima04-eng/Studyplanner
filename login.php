<?php
session_start();
session_regenerate_id(true);

header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["email"]) || !isset($data["password"])) {
    echo json_encode(["error" => "Missing email or password", "success" => false]);
    exit();
}

$email = $data["email"];
$password = $data["password"];

$stmt = $conn->prepare("SELECT id, email, password, name FROM users WHERE email = ?");
if (!$stmt) {
    echo json_encode(["error" => "Database error", "success" => false]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["user_name"] = $user["name"];

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user["id"],
                "email" => $user["email"],
                "name" => $user["name"]
            ]
        ]);
        exit();
    }
}

echo json_encode(["error" => "Invalid email or password", "success" => false]);
?>
