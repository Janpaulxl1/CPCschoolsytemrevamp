<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $appointment_id = intval($_POST['id']);

    // Fetch appointment details (allow missing student row; fallback to student_id)
    $sql = "SELECT a.id,
                   a.student_id,
                   a.appointment_time,
                   a.reason,
                   a.status,
                   s.first_name,
                   s.last_name
            FROM appointments a
            LEFT JOIN students s ON (s.id = a.student_id OR s.student_id = a.student_id)
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();

    if ($appointment) {
        // Update status to Completed
        $update_sql = "UPDATE appointments SET status = 'Completed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement']);
            exit;
        }
        $update_stmt->bind_param("i", $appointment_id);
        if (!$update_stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update appointment status']);
            $update_stmt->close();
            exit;
        }
        $update_stmt->close();

        // Insert into appointment_logs (use fallback student name/id if missing)
        $student_name = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''));
        if ($student_name === '') {
            $student_name = (string)$appointment['student_id'];
        }
        $date = date('Y-m-d', strtotime($appointment['appointment_time']));
        $time = date('H:i:s', strtotime($appointment['appointment_time']));
        $reason = $appointment['reason'] ?? '';
        $status = 'Completed';

        $insert_sql = "INSERT INTO appointment_logs (appointment_id, student_name, date, time, reason, status) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare insert statement']);
            exit;
        }
        $insert_stmt->bind_param("isssss", $appointment_id, $student_name, $date, $time, $reason, $status);
        if (!$insert_stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to insert into logs']);
            $insert_stmt->close();
            exit;
        }
        $insert_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Appointment marked as done and moved to logs', 'show_followup_modal' => true, 'appointment_id' => $appointment_id, 'student_id' => $appointment['student_id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>