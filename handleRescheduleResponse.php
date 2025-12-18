<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

// Basic validation
if (!isset($_POST['appointment_id'], $_POST['response'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$appointment_id = (int) $_POST['appointment_id'];
$response = strtolower(trim($_POST['response'])); // expected 'accept' or 'reject'

// Map response to DB values
if ($response === 'accept') {
    $reschedule_status = 'accepted';
    $appt_status = 'Accepted';
} else {
    // treat anything else as decline/reject
    $reschedule_status = 'declined';
    $appt_status = 'Declined';
}

// 1) Update student_notifications (set reschedule_status and mark read)
// We update notifications for this appointment that are in 'pending' state.
// If you want to update all, remove the reschedule_status='pending' condition.
$stmt = $conn->prepare("
    UPDATE student_notifications
    SET reschedule_status = ?, is_read = 1
    WHERE appointment_id = ? AND reschedule_status = 'pending'
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: '.$conn->error]);
    exit;
}
$stmt->bind_param("si", $reschedule_status, $appointment_id);
$ok1 = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

// 2) Update appointments status (optional but helpful for nurse)
$stmt2 = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
if ($stmt2) {
    $stmt2->bind_param("si", $appt_status, $appointment_id);
    $ok2 = $stmt2->execute();
    $stmt2->close();
} else {
    // ignore but log
    $ok2 = false;
}

// If no notification row was updated, we can still try to update without reschedule_status condition
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
    echo json_encode(['success' => true, 'message' => 'Response saved.']);
} else {
    // no notification found for appointment_id
    echo json_encode(['success' => false, 'message' => 'No notification found for that appointment.']);
}

$conn->close();
?>
