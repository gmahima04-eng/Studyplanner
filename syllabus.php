<?php
// syllabus.php - CRUD syllabus items per subject
session_start();
include "db.php";
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Ensure table exists (simple migration)
$conn->query("CREATE TABLE IF NOT EXISTS syllabus_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  done TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)");

switch ($method) {
    case 'GET':
        $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        if (!$subject_id) { echo json_encode([]); exit; }
        $stmt = $conn->prepare("SELECT id, title, done FROM syllabus_items WHERE user_id=? AND subject_id=? ORDER BY id ASC");
        $stmt->bind_param("ii", $user_id, $subject_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = [];
        while ($row = $res->fetch_assoc()) $items[] = $row;
        echo json_encode($items);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['subject_id']) || empty($data['title'])) { http_response_code(400); echo json_encode(['error'=>'subject_id and title required']); exit; }
        $subject_id = (int)$data['subject_id'];
        $title = $data['title'];
        $stmt = $conn->prepare("INSERT INTO syllabus_items (user_id, subject_id, title, done) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("iis", $user_id, $subject_id, $title);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
        } else {
            http_response_code(500); echo json_encode(['error'=>$stmt->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['id'])) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $id = (int)$data['id'];
        $done = isset($data['done']) ? (int)$data['done'] : 0;
        $stmt = $conn->prepare("UPDATE syllabus_items SET done=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $done, $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            http_response_code(500); echo json_encode(['error'=>$stmt->error]);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $stmt = $conn->prepare("DELETE FROM syllabus_items WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            http_response_code(500); echo json_encode(['error'=>$stmt->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error'=>'Invalid method']);
}

?>
