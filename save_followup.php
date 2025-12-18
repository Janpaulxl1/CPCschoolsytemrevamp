<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $followup_date = trim($_POST['followup_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (!$appointment_id || !$student_id || !$followup_date || !$reason) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Insert new follow-up appointment with status 'Follow-up'
    $insert_sql = "INSERT INTO appointments (student_id, appointment_time, reason, status, is_emergency) VALUES (?, ?, ?, 'Follow-up', 0)";
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param("iss", $student_id, $followup_date, $reason);
    if ($stmt->execute()) {
        $new_appointment_id = $conn->insert_id;
        $stmt->close();

        // Insert notification for the student to confirm
        $student_message = "A follow-up appointment has been scheduled for you. Please confirm.";
        $student_notif_sql = "INSERT INTO student_notifications (student_id, message, appointment_id, reschedule_status, is_read, created_at) VALUES (?, ?, ?, 'pending', 0, NOW())";
        $student_stmt = $conn->prepare($student_notif_sql);
        $student_stmt->bind_param("isi", $student_id, $student_message, $new_appointment_id);
        $student_stmt->execute();
        $student_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Follow-up appointment scheduled, awaiting student confirmation', 'new_appointment_id' => $new_appointment_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule follow-up']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
