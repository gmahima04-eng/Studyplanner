<?php
// subjects.php - improved error messages for debugging
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT * FROM subjects ORDER BY id DESC";
        $result = $conn->query($sql);
        if (!$result) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
        $subjects = [];
        while ($row = $result->fetch_assoc()) $subjects[] = $row;
        echo json_encode($subjects);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['title'])) { http_response_code(400); echo json_encode(['error'=>'Title required']); exit; }
        $title = $conn->real_escape_string($data['title']);
        $color = $conn->real_escape_string($data['color'] ?? '#60a5fa');
        $planned_hours = (int)($data['planned_hours'] ?? 0);
        $completed_percent = (int)($data['completed_percent'] ?? 0);
        // In a proper system use session user id
        $user_id = 1;

        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            $query = "UPDATE subjects SET title='$title', color='$color', planned_hours=$planned_hours, completed_percent=$completed_percent WHERE id=$id";
            if ($conn->query($query)) {
                echo json_encode(['success'=> true, 'id'=> $id]);
            } else {
                http_response_code(500);
                echo json_encode(['error'=>$conn->error]);
            }
        } else {
            $query = "INSERT INTO subjects (user_id, title, color, planned_hours, completed_percent) VALUES ($user_id, '$title', '$color', $planned_hours, $completed_percent)";
            if ($conn->query($query)) {
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
        if ($conn->query("DELETE FROM subjects WHERE id=".(int)$id)) echo json_encode(['success'=>true]); else { http_response_code(500); echo json_encode(['error'=>$conn->error]); }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Invalid request method"]);
}
?>