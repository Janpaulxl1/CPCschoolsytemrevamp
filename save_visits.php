<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST values safely
    $student_id          = $_POST['student_id'] ?? '';
    $student_name        = $_POST['student_name'] ?? '';
    $course              = $_POST['course'] ?? '';
    $reason              = $_POST['reason'] ?? '';
    $action_taken        = $_POST['action_taken'] ?? '';
    $med_id              = $_POST['med_id'] ?? '';
    $dosage              = $_POST['dosage'] ?? '';
    $quantity            = $_POST['quantity'] ?? '';
    $visit_date          = $_POST['visit_date'] ?? '';
    $location            = $_POST['location'] ?? '';

    // Basic validation — only save if name and date are filled
    if (!empty($student_name) && !empty($visit_date)) {
        $stmt = $conn->prepare("
            INSERT INTO student_visits
            (student_id, student_name, course, reason, action_taken, med_id, dosage, quantity, visit_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->bind_param(
            "sssssssss",
            $student_id,
            $student_name,
            $course,
            $reason,
            $action_taken,
            $med_id,
            $dosage,
            $quantity,
            $visit_date
        );
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
    header("Location: Student_visitlogs.php");
    exit;
}
?>
