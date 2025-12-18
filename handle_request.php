<?php
require_once "db.php";
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error'];

// Basic validation
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment id']);
    exit;
}

if ($action === 'accept') {
    $status = 'Confirmed';
    $message = 'Your appointment has been successfully confirmed!.';
} elseif ($action === 'reject') {
    $status = 'Rejected';
    $message = 'Your appointment has been rejected by the nurse.';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Update appointment status
$stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("si", $status, $id);
$updateOk = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$updateOk) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    exit;
}

// Even if affected_rows is 0 (status unchanged), continue to respond success for idempotency
$response = ['success' => true, 'status' => $status];

// Fetch student info for notifications/calendar; if missing, still return success
$stmt2 = $conn->prepare("
    SELECT a.appointment_time,
           a.reason,
           a.student_id,
           s.id   AS student_db_id,
           CONCAT(s.first_name, ' ', s.last_name) AS student_name
    FROM appointments a
    LEFT JOIN students s
      ON (s.id = a.student_id OR s.student_id = a.student_id)
    WHERE a.id = ?
");
if ($stmt2) {
    $stmt2->bind_param("i", $id);
    if ($stmt2->execute()) {
        $result = $stmt2->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            // Insert notification for student (ignore failure silently)
            // Resolve student id for notifications: prefer students.id, fallback to appointment student_id if numeric
            $student_target_id = $row['student_db_id'] ?? null;
            if (!$student_target_id && is_numeric($row['student_id'])) {
                $student_target_id = (int)$row['student_id'];
            }

            if ($student_target_id) {
                $stmt3 = $conn->prepare("
                    INSERT INTO student_notifications (student_id, appointment_id, message, reschedule_status, is_read, created_at)
                    VALUES (?, ?, ?, 'none', 0, NOW())
                ");
                if ($stmt3) {
                    $stmt3->bind_param("iis", $student_target_id, $id, $message);
                    $stmt3->execute();
                    $stmt3->close();
                }
            }

            // If accepting, add to calendar events (ignore failure silently)
            if ($action === 'accept') {
                $event_title = "Appointment: " . $row['student_name'] . " - " . $row['reason'];
                $event_start = date('Y-m-d H:i:s', strtotime($row['appointment_time']));
                $event_end = date('Y-m-d H:i:s', strtotime($row['appointment_time'] . ' +1 hour')); // Assume 1 hour duration
                $stmt4 = $conn->prepare("INSERT INTO events (appointment_id, title, start, end, note) VALUES (?, ?, ?, ?, ?)");
                if ($stmt4) {
                    $note = "Confirmed appointment";
                    $stmt4->bind_param("issss", $id, $event_title, $event_start, $event_end, $note);
                    $stmt4->execute();
                    $stmt4->close();
                }
            }

            // Enrich response
            $response = [
                'success' => true,
                'status' => $status,
                'date' => date('Y-m-d', strtotime($row['appointment_time'])),
                'time' => date('h:i A', strtotime($row['appointment_time'])),
                'name' => $row['student_name'],
                'reason' => $row['reason']
            ];
        } else {
            $response['message'] = 'Appointment updated but details not found';
        }
    } else {
        $response['message'] = 'Appointment updated, but failed to fetch details';
    }
    $stmt2->close();
} else {
    $response['message'] = 'Appointment updated, but failed to prepare details query';
}

if ($conn) $conn->close();

echo json_encode($response);
?>
