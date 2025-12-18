<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visit_id'])) {
    $visit_id = intval($_POST['visit_id']);

    // Update status to Completed
    $update_sql = "UPDATE student_visits SET status = 'Completed' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $visit_id);
    if ($update_stmt->execute()) {
        $update_stmt->close();

        // Fetch visit details to insert into appointments and appointment_medications
        $fetch_sql = "SELECT sv.*, s.student_id as student_id_string, CONCAT(s.first_name, ' ', s.last_name) as student_name, m.name as medicine_name FROM student_visits sv LEFT JOIN students s ON sv.student_id = s.id LEFT JOIN medicines m ON sv.med_id = m.name WHERE sv.id = ?";
        $fetch_stmt = $conn->prepare($fetch_sql);
        $fetch_stmt->bind_param("i", $visit_id);
        $fetch_stmt->execute();
        $visit = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();

        if ($visit) {
            // Insert into appointments table
            $insert_appointment_sql = "INSERT INTO appointments (student_id, appointment_time, reason, status) VALUES (?, ?, ?, 'Completed')";
            $insert_appointment_stmt = $conn->prepare($insert_appointment_sql);
            $insert_appointment_stmt->bind_param("iss", $visit['student_id'], $visit['visit_date'], $visit['reason']);
            if ($insert_appointment_stmt->execute()) {
                $appointment_id = $conn->insert_id;
                $insert_appointment_stmt->close();

                // If medicine is provided, insert into appointment_medications
                if (!empty($visit['med_id'])) {
                    $medicine_name = !empty($visit['medicine_name']) ? $visit['medicine_name'] : $visit['med_id'];
                    $insert_med_sql = "INSERT INTO appointment_medications (appointment_id, medicine_name, dosage, quantity, action_taken) VALUES (?, ?, ?, ?, ?)";
                    $insert_med_stmt = $conn->prepare($insert_med_sql);
                    $insert_med_stmt->bind_param("issss", $appointment_id, $medicine_name, $visit['dosage'], $visit['quantity'], $visit['action_taken']);
                    $insert_med_stmt->execute();
                    $insert_med_stmt->close();
                }

                // Insert into appointment_logs for history
                $insert_log_sql = "INSERT INTO appointment_logs (appointment_id, date, time, reason, status, student_name) VALUES (?, ?, ?, ?, 'Completed', ?)";
                $insert_log_stmt = $conn->prepare($insert_log_sql);
                $date = date('Y-m-d', strtotime($visit['visit_date']));
                $time = date('H:i:s', strtotime($visit['visit_date']));
                $insert_log_stmt->bind_param("issss", $appointment_id, $date, $time, $visit['reason'], $visit['student_name']);
                $insert_log_stmt->execute();
                $insert_log_stmt->close();
            } else {
                $insert_appointment_stmt->close();
            }
        }

        // Redirect back to the visit logs page
        header("Location: Student_visitlogs.php");
        exit();
    } else {
        $update_stmt->close();
        // Redirect back with an error message
        header("Location: Student_visitlogs.php?message=Error marking visit as done");
        exit();
    }
} else {
    // Redirect back with an error message
    header("Location: Student_visitlogs.php?message=Invalid request");
    exit();
}
?>
