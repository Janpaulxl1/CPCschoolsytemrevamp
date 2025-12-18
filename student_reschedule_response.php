<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (
    !isset($input['appointmentId'], $input['action']) || 
    !in_array(strtolower($input['action']), ['accepted', 'declined'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid parameters.']);
    exit;
}

$appointment_id = (int) $input['appointmentId'];
$action = strtolower($input['action']);

if ($action === 'accepted') {
    $reschedule_status = 'accepted';
    $appt_status = 'Pending Nurse Confirmation'; // Updated to reflect nurse confirmation needed
} else {
    $reschedule_status = 'declined';
    $appt_status = 'Declined';
}

// Update student_notifications
$stmt = $conn->prepare("
    UPDATE student_notifications
    SET reschedule_status = ?, is_read = 1
    WHERE appointment_id = ? AND reschedule_status = 'pending'
");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: '.$conn->error]);
    exit;
}
$stmt->bind_param("si", $reschedule_status, $appointment_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

// Update appointments status
$stmt2 = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
if ($stmt2) {
    $stmt2->bind_param("si", $appt_status, $appointment_id);
    $stmt2->execute();
    $stmt2->close();
}

// Notify the nurse based on action
if ($action === 'accepted') {
    // Get nurse_id assigned to appointment
    $stmtNurse = $conn->prepare("SELECT nurse_id FROM appointments WHERE id = ?");
    $stmtNurse->bind_param("i", $appointment_id);
    $stmtNurse->execute();
    $resultNurse = $stmtNurse->get_result();
    $nurse_user_id = 2022001; // default nurse user_id fallback

    if ($resultNurse && $resultNurse->num_rows > 0) {
        $row = $resultNurse->fetch_assoc();
        if (!empty($row['nurse_id'])) {
            $nurse_user_id = (int) $row['nurse_id'];
        }
    }
    $stmtNurse->close();

    error_log("student_reschedule_response.php: nurse_user_id = $nurse_user_id");

    // Insert notification for nurse
    $message = "Student accepted the reschedule for appointment #$appointment_id. Please confirm.";
    $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    if (!$stmtNotif) {
        error_log("Prepare notification insert failed: " . $conn->error);
    } else {
        $stmtNotif->bind_param("is", $nurse_user_id, $message);
        if (!$stmtNotif->execute()) {
            error_log("Execute notification insert failed: " . $stmtNotif->error);
        }
        $stmtNotif->close();
    }
} elseif ($action === 'declined') {
    // Get nurse_id assigned to appointment
    $stmtNurse = $conn->prepare("SELECT nurse_id FROM appointments WHERE id = ?");
    $stmtNurse->bind_param("i", $appointment_id);
    $stmtNurse->execute();
    $resultNurse = $stmtNurse->get_result();
    $nurse_user_id = 2022001; // default nurse user_id fallback

    if ($resultNurse && $resultNurse->num_rows > 0) {
        $row = $resultNurse->fetch_assoc();
        if (!empty($row['nurse_id'])) {
            $nurse_user_id = (int) $row['nurse_id'];
        }
    }
    $stmtNurse->close();

    error_log("student_reschedule_response.php: nurse_user_id = $nurse_user_id");

    // Insert notification for nurse
    $message = "Student declined the reschedule for appointment #$appointment_id.";
    $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    if (!$stmtNotif) {
        error_log("Prepare notification insert failed: " . $conn->error);
    } else {
        $stmtNotif->bind_param("is", $nurse_user_id, $message);
        if (!$stmtNotif->execute()) {
            error_log("Execute notification insert failed: " . $stmtNotif->error);
        }
        $stmtNotif->close();
    }
}



// Fallback if no rows updated above
if ($affected === 0) {
    $stmt3 = $conn->prepare("
        UPDATE student_notifications
        SET reschedule_status = ?, is_read = 1
        WHERE appointment_id = ?
        LIMIT 1
    ");
    if ($stmt3) {
        $stmt3->bind_param("si", $reschedule_status, $appointment_id);
        $stmt3->execute();
        $affected = $stmt3->affected_rows;
        $stmt3->close();
    }
}

if ($affected > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Response saved and nurse notified.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Response saved.']);
}

$conn->close();
?>
