<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$student_id_str = $_SESSION['student_id'];

// Get numeric student id from students table
$stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("s", $student_id_str);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(["status" => "error", "message" => "Student not found"]);
    exit;
}

$student_id = $student['id'];

// Update all unread notifications for this student
$sql = "UPDATE student_notifications 
        SET is_read = 1 
        WHERE student_id = ? AND is_read = 0";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "affected" => $stmt->affected_rows]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
