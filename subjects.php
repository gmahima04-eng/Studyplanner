<?php
// subjects.php - CRUD operations with proper session handling
session_start();
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include "db.php";

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
        $subjects = [];
        while ($row = $result->fetch_assoc()) $subjects[] = $row;
        echo json_encode($subjects);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['title'])) { http_response_code(400); echo json_encode(['error'=>'Title required']); exit; }
        $title = $data['title'];
        $color = $data['color'] ?? '#60a5fa';
        $planned_hours = (int)($data['planned_hours'] ?? 0);
        $completed_percent = (int)($data['completed_percent'] ?? 0);

        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            $stmt = $conn->prepare("UPDATE subjects SET title=?, color=?, planned_hours=?, completed_percent=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssiiii", $title, $color, $planned_hours, $completed_percent, $id, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success'=> true, 'id'=> $id]);
            } else {
                http_response_code(500);
                echo json_encode(['error'=>$conn->error]);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (user_id, title, color, planned_hours, completed_percent) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $user_id, $title, $color, $planned_hours, $completed_percent);
            if ($stmt->execute()) {
                echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error'=>$conn->error]);
            }
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            http_response_code(500);
            echo json_encode(['error'=>$conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Invalid request method"]);
}
?>