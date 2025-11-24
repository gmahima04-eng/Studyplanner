<?php
include "db.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  // ---------- GET: Fetch all tasks ----------
  case 'GET':
    $res = $conn->query("SELECT t.*, s.title AS subject_name 
          FROM tasks t 
          LEFT JOIN subjects s ON t.subject_id = s.id 
          ORDER BY t.due_date ASC");
    $tasks = [];
    while ($row = $res->fetch_assoc()) $tasks[] = $row;
    echo json_encode($tasks);
    break;

  // ---------- POST: Add new task ----------
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $title = $conn->real_escape_string($data['title']);
    $priority = $conn->real_escape_string($data['priority'] ?? 'medium');
    $due_date = $conn->real_escape_string($data['due_date'] ?? date("Y-m-d"));
    $subject_id = isset($data['subject_id']) ? (int)$data['subject_id'] : "NULL";
    $user_id = 1;
    $conn->query("INSERT INTO tasks (user_id, subject_id, title, priority, due_date, done)
                  VALUES ($user_id, $subject_id, '$title', '$priority', '$due_date', 0)");
    echo json_encode(["success" => true]);
    break;

  // ---------- PUT: Mark as done / update ----------
  case 'PUT':
    $data = json_decode(file_get_contents("php://input"), true);
    $id = (int)$data['id'];
    $done = (int)$data['done'];
    $conn->query("UPDATE tasks SET done=$done WHERE id=$id");
    echo json_encode(["success" => true]);
    break;

  // ---------- DELETE: Remove task ----------
  case 'DELETE':
    $id = $_GET['id'] ?? 0;
    if (!$id) { echo json_encode(["error" => "Missing id"]); exit; }
    $conn->query("DELETE FROM tasks WHERE id=$id");
    echo json_encode(["success" => true]);
    break;
}
?>
