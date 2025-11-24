<?php
// calendar.php - CRUD tasks used by calendar page
session_start();
include "db.php";
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

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
    $stmt = $conn->prepare("SELECT t.*, s.title AS subject_name
                           FROM tasks t
                           LEFT JOIN subjects s ON t.subject_id = s.id
                           WHERE t.user_id = ?
                           ORDER BY COALESCE(t.due_date, '9999-12-31') ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res) { http_response_code(500); echo json_encode(['error'=>$conn->error]); exit; }
    $tasks = [];
    while ($row = $res->fetch_assoc()) $tasks[] = $row;
    echo json_encode($tasks);
    break;

  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }
    $title = $data['title'] ?? '';
    $priority = $data['priority'] ?? 'medium';
    $due_date = isset($data['due_date']) && $data['due_date'] !== '' ? $data['due_date'] : null;
    
    if (empty($title)) { http_response_code(400); echo json_encode(['error'=>'Title required']); exit; }
    
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, subject_id, title, priority, due_date, done) VALUES (?, NULL, ?, ?, ?, 0)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("isss", $user_id, $title, $priority, $due_date);
    
    if ($stmt->execute()) {
        echo json_encode(["success"=>true, "id"=>$conn->insert_id, "message"=>"Task created successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error"=>"Execute failed: " . $stmt->error]);
    }
    break;

  case 'PUT':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    
    $id = (int)$data['id'];
    $done = isset($data['done']) ? (int)$data['done'] : null;
    $title = $data['title'] ?? null;
    $due_date = $data['due_date'] ?? null;
    
    if ($done !== null) {
        $stmt = $conn->prepare("UPDATE tasks SET done=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $done, $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            http_response_code(500);
            echo json_encode(['error'=>$conn->error]);
        }
    } else if ($title !== null) {
        $stmt = $conn->prepare("UPDATE tasks SET title=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $title, $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            http_response_code(500);
            echo json_encode(['error'=>$conn->error]);
        }
    } else {
        echo json_encode(['success'=>false, 'message'=>'Nothing to update']);
    }
    break;

  case 'DELETE':
    $id = $_GET['id'] ?? 0;
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
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
    break;
}
?>