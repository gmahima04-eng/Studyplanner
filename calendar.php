<?php
// calendar.php (renamed from calender.php) - CRUD tasks used by calendar page
include "db.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $res = $conn->query("SELECT t.*, s.title AS subject_name
                         FROM tasks t
                         LEFT JOIN subjects s ON t.subject_id = s.id
                         ORDER BY COALESCE(t.due_date, '9999-12-31') ASC");
    if (!$res) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
    $tasks = [];
    while ($row = $res->fetch_assoc()) $tasks[] = $row;
    echo json_encode($tasks);
    break;

  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }
    $title = $conn->real_escape_string($data['title'] ?? '');
    $priority = $conn->real_escape_string($data['priority'] ?? 'medium');
    $due_date = isset($data['due_date']) && $data['due_date'] !== '' ? $conn->real_escape_string($data['due_date']) : null;
    $subject_id = isset($data['subject_id']) && $data['subject_id'] !== '' ? (int)$data['subject_id'] : "NULL";
    $user_id = 1;
    if (empty($title)) { http_response_code(400); echo json_encode(['error'=>'Title required']); exit; }
    $due_date_sql = $due_date ? "'$due_date'" : "NULL";
    $sql = "INSERT INTO tasks (user_id, subject_id, title, priority, due_date, done)
            VALUES ($user_id, $subject_id, '$title', '$priority', $due_date_sql, 0)";
    if ($conn->query($sql)) {
        echo json_encode(["success"=>true, "id"=>$conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error"=>$conn->error]);
    }
    break;

  case 'PUT':
    // expect JSON {"id":..., "done":0/1} or other fields
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = (int)$data['id'];
    $fields = [];
    if (isset($data['done'])) $fields[] = "done=" . ((int)$data['done']);
    if (isset($data['title'])) $fields[] = "title='" . $conn->real_escape_string($data['title']) . "'";
    if (isset($data['due_date'])) $fields[] = "due_date='" . $conn->real_escape_string($data['due_date']) . "'";
    if (empty($fields)) { echo json_encode(['success'=>false, 'message'=>'Nothing to update']); exit; }
    $sql = "UPDATE tasks SET " . implode(",", $fields) . " WHERE id=$id";
    if ($conn->query($sql)) echo json_encode(['success'=>true]); else { http_response_code(500); echo json_encode(['error'=>$conn->error]); }
    break;

  case 'DELETE':
    $id = $_GET['id'] ?? 0;
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    if ($conn->query("DELETE FROM tasks WHERE id=".(int)$id)) echo json_encode(['success'=>true]); else { http_response_code(500); echo json_encode(['error'=>$conn->error]); }
    break;

  default:
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method"]);
    break;
}
