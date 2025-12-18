<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (
    !isset($input['appointmentId'], $input['action']) || 
    !in_array(strtolower($input['action']), ['accepted', 'declined'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid parameters.']);
    exit;
}

$appointment_id = (int) $input['appointmentId'];
$action = strtolower($input['action']);
$reschedule_status = $action;
$appt_status = $action === 'accepted' ? 'Confirmed' : 'Declined';

// Update student_notifications
$stmt = $conn->prepare("UPDATE student_notifications SET reschedule_status = ?, is_read = 1 WHERE appointment_id = ? AND reschedule_status = 'pending'");
$stmt->bind_param("si", $reschedule_status, $appointment_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

// Update appointments status
$stmt2 = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$stmt2->bind_param("si", $appt_status, $appointment_id);
$stmt2->execute();
$stmt2->close();

// Send notification to default nurse if accepted
if ($action === 'accepted') {
    // Optional: Try to get nurse_id from appointments (if available)
    $stmtNurse = $conn->prepare("SELECT nurse_id FROM appointments WHERE id = ?");
    $stmtNurse->bind_param("i", $appointment_id);
    $stmtNurse->execute();
    $resultNurse = $stmtNurse->get_result();
    $nurse_user_id = 2022001; // fallback/default nurse user_id

    if ($resultNurse && $resultNurse->num_rows > 0) {
        $row = $resultNurse->fetch_assoc();
        if (!empty($row['nurse_id'])) {
            $nurse_user_id = (int) $row['nurse_id'];
        }
    }
    $stmtNurse->close();

    // Insert notification for nurse
    $message = "Student accepted the reschedule for appointment #$appointment_id.";
    $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $stmtNotif->bind_param("is", $nurse_user_id, $message);
    $stmtNotif->execute();
    $stmtNotif->close();
}

// Fallback update in case no student_notifications were marked
if ($affected === 0) {
    $stmt3 = $conn->prepare("UPDATE student_notifications SET reschedule_status = ?, is_read = 1 WHERE appointment_id = ? LIMIT 1");
    $stmt3->bind_param("si", $reschedule_status, $appointment_id);
    $stmt3->execute();
    $stmt3->close();
}

// Return final response
echo json_encode([
    'status' => 'success',
    'message' => 'Student response saved and nurse notified if accepted.'
]);

$conn->close();
?>
