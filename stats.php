<?php
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

// Study hours: sum of planned_hours across subjects
$res = $conn->prepare("SELECT COALESCE(SUM(planned_hours),0) AS study_hours FROM subjects WHERE user_id = ?");
$res->bind_param("i", $user_id);
$res->execute();
$r = $res->get_result()->fetch_assoc();
$study_hours = (int)$r['study_hours'];

// Active subjects: count where completed_percent < 100
$res = $conn->prepare("SELECT COUNT(*) AS active_subjects FROM subjects WHERE user_id = ? AND completed_percent < 100");
$res->bind_param("i", $user_id);
$res->execute();
$r = $res->get_result()->fetch_assoc();
$active_subjects = (int)$r['active_subjects'];

// Goals completed: tasks done / total tasks
$res = $conn->prepare("SELECT SUM(done) AS done_tasks, COUNT(*) AS total_tasks FROM tasks WHERE user_id = ?");
$res->bind_param("i", $user_id);
$res->execute();
$r = $res->get_result()->fetch_assoc();
$done_tasks = (int)$r['done_tasks'];
$total_tasks = (int)$r['total_tasks'];

// Streak: compute consecutive days up to today with at least one completed task (by due_date)
$dates = [];
$stmt = $conn->prepare("SELECT DISTINCT due_date FROM tasks WHERE user_id = ? AND done = 1 AND due_date IS NOT NULL AND due_date <= CURDATE() ORDER BY due_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res2 = $stmt->get_result();
while ($row = $res2->fetch_assoc()) {
    $dates[] = $row['due_date'];
}

$streak = 0;
$today = new DateTimeImmutable();
$current = $today;
foreach ($dates as $d) {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
    if (!$dt) continue;
    if ($dt->format('Y-m-d') === $current->format('Y-m-d')) {
        $streak++;
        $current = $current->sub(new DateInterval('P1D'));
    } else if ($dt < $current) {
        // if date is older than current and not equal, streak stops
        break;
    }
}

echo json_encode([
    'study_hours' => $study_hours,
    'active_subjects' => $active_subjects,
    'done_tasks' => $done_tasks,
    'total_tasks' => $total_tasks,
    'streak' => $streak
]);

?>
