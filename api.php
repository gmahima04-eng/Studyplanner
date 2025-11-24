<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$method = $_SERVER['REQUEST_METHOD'];
$table = $_GET['table'] ?? null;

if (!$table) {
    echo json_encode(["error" => "No table specified"]);
    exit;
}

// Handle requests
switch ($method) {
    case 'GET':
        $result = $conn->query("SELECT * FROM $table ORDER BY id DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $columns = implode(",", array_keys($data));
        $values = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_values($data))) . "'";
        $conn->query("INSERT INTO $table ($columns) VALUES ($values)");
        echo json_encode(["success" => $conn->insert_id > 0]);
        break;
// code for insert the subject
    case 'PUT':
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$id) { echo json_encode(["error" => "No ID provided"]); exit; }

        $set = [];
        foreach ($data as $key => $val) {
            $set[] = "$key='" . $conn->real_escape_string($val) . "'";
        }
        $conn->query("UPDATE $table SET " . implode(",", $set) . " WHERE id=$id");
        echo json_encode(["success" => true]);
        break;
// to delete the subject here
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(["error" => "No ID provided"]); exit; }

        $conn->query("DELETE FROM $table WHERE id=$id");
        echo json_encode(["success" => true]);
        break;

    default:
        echo json_encode(["error" => "Invalid request method"]);
}
?>