<?php
session_start();
// Temporarily bypassed for testing - restore after
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
//     header("Location: index.php");
//     exit;
// }
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'db.php';

if (isset($_GET['action']) || isset($_POST['action'])) {
    $action = $_GET['action'] ?? $_POST['action'];

    if ($action === 'fetch_history') {
        header('Content-Type: application/json');
        $appointments = [];
        $sql = "SELECT a.id, a.appointment_time, a.reason, a.status,
                s.first_name, s.last_name, s.section, s.semester, s.year_level
                FROM appointments a
                LEFT JOIN students s ON a.student_id = s.id
                ORDER BY a.appointment_time DESC";
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $appointments[] = [
                    'id' => $row['id'],
                    'student_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                    'reason' => $row['reason'],
                    'status' => $row['status'],
                    'date' => date('Y-m-d H:i', strtotime($row['appointment_time'])),
                    'section' => $row['section'] ?? '',
                    'semester' => $row['semester'] ?? '',
                    'year_level' => $row['year_level'] ?? ''
                ];
            }
        }
        echo json_encode($appointments);
        exit;
    }

    // Appointment activity (summarized for the small widget)
    if ($action === 'appt_activity') {
        header('Content-Type: application/json');
        $rows = [];
        $sql = "SELECT a.id, a.appointment_time, a.reason, a.status,
                s.first_name, s.last_name
                FROM appointments a
                LEFT JOIN students s ON a.student_id = s.id
                WHERE a.status IN ('Pending','Confirmed')
                ORDER BY a.appointment_time DESC LIMIT 20";
        if ($res = $conn->query($sql)) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id' => $r['id'],
                    'date' => date('Y-m-d', strtotime($r['appointment_time'])),
                    'time' => date('h:i A', strtotime($r['appointment_time'])),
                    'student_name' => trim($r['first_name'] . ' ' . $r['last_name']),
                    'reason' => $r['reason'],
                    'status' => $r['status'] ?: 'Pending'
                ];
            }
        }
        echo json_encode($rows);
        exit;
    }

    // Get appointment details for modal
    if ($action === 'get_appointment_details') {
        header('Content-Type: application/json');
        $appointment_id = intval($_GET['id'] ?? 0);
        $sql = "SELECT a.*, s.first_name, s.last_name, s.student_id as student_id_string, s.course, s.year_level, s.section
                FROM appointments a
                LEFT JOIN students s ON a.student_id = s.id
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();

        if ($appointment) {
            echo json_encode($appointment);
        } else {
            echo json_encode(['error' => 'Appointment not found']);
        }
        exit;
    }

    // Save medicine entry for appointment
    if ($action === 'save_medicine_entry') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $action_taken = trim($_POST['action_taken'] ?? '');

        if (empty($medicine_name) || empty($dosage) || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Subtract from stock
        $stmt = $conn->prepare("UPDATE medications SET quantity = quantity - ? WHERE name = ?");
        $stmt->bind_param("is", $quantity, $medicine_name);
        $stmt->execute();
        $stmt->close();

        // Insert into appointment_medications
        $stmt = $conn->prepare("INSERT INTO appointment_medications (appointment_id, medicine_name, dosage, quantity, action_taken, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issis", $appointment_id, $medicine_name, $dosage, $quantity, $action_taken);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medicine entry saved']);
        } else {
            // Add back to stock on failure
            $rollback = $conn->prepare("UPDATE medications SET quantity = quantity + ? WHERE name = ?");
            $rollback->bind_param("is", $quantity, $medicine_name);
            $rollback->execute();
            $rollback->close();
            echo json_encode(['success' => false, 'message' => 'Failed to save medicine entry']);
        }
        $stmt->close();
        exit;
    }

    // Update medicine entry for appointment
    if ($action === 'update_medicine_entry') {
        header('Content-Type: application/json');
        $entry_id = intval($_POST['id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $action_taken = trim($_POST['action_taken'] ?? '');

        if (empty($medicine_name) || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Fetch old data
        $old_stmt = $conn->prepare("SELECT medicine_name, quantity FROM appointment_medications WHERE id = ?");
        $old_stmt->bind_param("i", $entry_id);
        $old_stmt->execute();
        $old_result = $old_stmt->get_result();
        $old_data = $old_result->fetch_assoc();
        $old_stmt->close();

        if (!$old_data) {
            echo json_encode(['success' => false, 'message' => 'Entry not found']);
            exit;
        }

        $old_medicine = $old_data['medicine_name'];
        $old_quantity = $old_data['quantity'];

        // Add back old quantity to stock
        if ($old_medicine !== $medicine_name) {
            $add_back = $conn->prepare("UPDATE medications SET quantity = quantity + ? WHERE name = ?");
            $add_back->bind_param("is", $old_quantity, $old_medicine);
            $add_back->execute();
            $add_back->close();
        }

        // Subtract new quantity from stock
        $subtract = $conn->prepare("UPDATE medications SET quantity = quantity - ? WHERE name = ?");
        $subtract->bind_param("is", $quantity, $medicine_name);
        $subtract->execute();
        $subtract->close();

        // Update appointment_medications
        $stmt = $conn->prepare("UPDATE appointment_medications SET medicine_name = ?, dosage = ?, quantity = ?, action_taken = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $medicine_name, $dosage, $quantity, $action_taken, $entry_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medicine entry updated']);
        } else {
            // Rollback stock on failure
            $rollback = $conn->prepare("UPDATE medications SET quantity = quantity + ? WHERE name = ?");
            $rollback->bind_param("is", $quantity, $medicine_name);
            $rollback->execute();
            if ($old_medicine !== $medicine_name) {
                $add_old = $conn->prepare("UPDATE medications SET quantity = quantity - ? WHERE name = ?");
                $add_old->bind_param("is", $old_quantity, $old_medicine);
                $add_old->execute();
                $add_old->close();
            }
            $rollback->close();
            echo json_encode(['success' => false, 'message' => 'Failed to update medicine entry']);
        }
        $stmt->close();
        exit;
    }

    // Get medicine entries for appointment
    if ($action === 'get_medicine_entries') {
        header('Content-Type: application/json');
        $appointment_id = intval($_GET['id'] ?? 0);
        $sql = "SELECT * FROM appointment_medications WHERE appointment_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entries[] = $row;
        }
        echo json_encode($entries);
        $stmt->close();
        exit;
    }

    // Fetch medications for dropdown
    if ($action === 'fetch_medications') {
        header('Content-Type: application/json');
        $medications = [];
        $sql = "SELECT name, dosage FROM medications ORDER BY name ASC";
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $medications[] = [
                    'name' => $row['name'],
                    'dosage' => $row['dosage'] ?? '500mg' // Default if not set
                ];
            }
        }
        echo json_encode($medications);
        exit;
    }

    // Delete medicine entry
    if ($action === 'delete_medicine_entry') {
        header('Content-Type: application/json');
        $entry_id = intval($_POST['id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);

        if ($entry_id <= 0 || empty($medicine_name) || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        // Add back to stock
        $stmt = $conn->prepare("UPDATE medications SET quantity = quantity + ? WHERE name = ?");
        $stmt->bind_param("is", $quantity, $medicine_name);
        $stmt->execute();
        $stmt->close();

        // Delete entry
        $stmt = $conn->prepare("DELETE FROM appointment_medications WHERE id = ?");
        $stmt->bind_param("i", $entry_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medicine entry deleted']);
        } else {
            // Rollback stock
            $rollback = $conn->prepare("UPDATE medications SET quantity = quantity - ? WHERE name = ?");
            $rollback->bind_param("is", $quantity, $medicine_name);
            $rollback->execute();
            $rollback->close();
            echo json_encode(['success' => false, 'message' => 'Failed to delete medicine entry']);
        }
        $stmt->close();
        exit;
    }

    // Save remarks for appointment
    if ($action === 'save_remarks') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE appointments SET remarks = ? WHERE id = ?");
        $stmt->bind_param("si", $remarks, $appointment_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Remarks saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save remarks']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_remarks') {
        header('Content-Type: application/json');
        $appointment_id = intval($_GET['appointment_id'] ?? 0);

        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }

        $stmt = $conn->prepare("SELECT remarks FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();

        if ($appointment) {
            echo json_encode(['success' => true, 'remarks' => $appointment['remarks'] ?? '']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
        exit;
    }

    if ($action === 'update_appointment') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($appointment_id <= 0 || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE appointments SET reason = ? WHERE id = ?");
        $stmt->bind_param("si", $reason, $appointment_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'mark_done') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['id'] ?? 0);

        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error']);
            exit;
        }
        $stmt->bind_param("i", $appointment_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment marked as done']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark as done: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    // Notification checker (new appointment notifications)
    if ($action === 'notification_checker') {
        header('Content-Type: application/json');
        $items = [];
        $sql = "SELECT a.id, a.appointment_time, a.reason, a.is_emergency,
                s.first_name, s.last_name, s.section, s.year_level, s.course
                FROM appointments a
                LEFT JOIN students s ON (a.student_id = s.id OR a.student_id = s.student_id)
                WHERE a.status = 'Pending'
                ORDER BY a.appointment_time DESC LIMIT 50";
        if ($res = $conn->query($sql)) {
            while ($r = $res->fetch_assoc()) {
                $student_name = trim($r['first_name'] . ' ' . $r['last_name']);
                if (empty($student_name)) {
                    $student_name = $r['student_id'] ?? 'N/A';
                }
                $items[] = [
                    'id' => $r['id'],
                    'appointment_time' => $r['appointment_time'],
                    'reason' => $r['reason'] ?? 'No reason specified',
                    'student_name' => $student_name,
                    'is_emergency' => (int)($r['is_emergency'] ?? 0),
                    'section' => $r['section'] ?? '',
                    'year_level' => $r['year_level'] ?? '',
                    'course' => $r['course'] ?? ''
                ];
            }
        }
        echo json_encode($items);
        exit;
    }

    // General notifications (notifications table)
    if ($action === 'nurse_general_notifications') {
        header('Content-Type: application/json');
        $notes = [];
        $sql = "SELECT id, message, created_at FROM notifications WHERE message LIKE '%Emergency%' ORDER BY created_at DESC LIMIT 50";
        if ($res = $conn->query($sql)) {
            while ($r = $res->fetch_assoc()) {
                $notes[] = $r;
            }
        }
        echo json_encode($notes);
        exit;
    }

    if ($action === 'accept_appointment') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['id'] ?? 0);
        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Confirmed' WHERE id = ? AND (status IS NULL OR status = 'Pending')");
        $stmt->bind_param("i", $appointment_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment accepted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to accept appointment']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'reject_appointment') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['id'] ?? 0);
        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Rejected' WHERE id = ? AND (status IS NULL OR status = 'Pending')");
        $stmt->bind_param("i", $appointment_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject appointment']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'reschedule_appointment') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['id'] ?? 0);
        $new_time = trim($_POST['new_time'] ?? '');
        if ($appointment_id <= 0 || empty($new_time)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE appointments SET appointment_time = ? WHERE id = ? AND (status IS NULL OR status = 'Pending')");
        $stmt->bind_param("si", $new_time, $appointment_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment rescheduled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reschedule appointment']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'mark_notification_read') {
        header('Content-Type: application/json');
        $notification_id = intval($_POST['id'] ?? 0);
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'appt_activity_extended') {
        header('Content-Type: application/json');
        $rows = [];
        $sql = "SELECT a.id,
                a.appointment_time,
                COALESCE(NULLIF(TRIM(CONCAT(s.first_name,' ',s.last_name)), ''), a.student_id) AS student_name,
                a.reason,
                a.status,
                a.remarks,
                s.section,
                s.year_level,
                (SELECT medicine_name FROM appointment_medications am WHERE am.appointment_id = a.id ORDER BY am.created_at DESC LIMIT 1) AS medicine_name
                FROM appointments a
                LEFT JOIN students s ON (s.id = a.student_id OR s.student_id = a.student_id)
                WHERE (a.status IS NULL OR TRIM(a.status) NOT IN ('Rejected','Declined','Cancelled','Completed'))
                ORDER BY a.appointment_time DESC
                LIMIT 300";
        if ($res = $conn->query($sql)) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id' => $r['id'],
                    'appointment_time' => $r['appointment_time'],
                    'student_name' => $r['student_name'],
                    'reason' => $r['reason'],
                    'status' => $r['status'] ?: 'Pending',
                    'remarks' => $r['remarks'] ?? '',
                    'section' => $r['section'] ?? '',
                    'year_level' => $r['year_level'] ?? '',
                    'medicine_name' => $r['medicine_name'] ?? ''
                ];
            }
        }
        echo json_encode($rows);
        exit;
    }
}

// First, update any responders who have been inactive for more than 10 minutes to 'Off Duty'
$inactive_threshold = "DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
$cleanup_sql = "UPDATE emergency_responders SET status = 'Off Duty' WHERE last_active < $inactive_threshold AND status = 'Active'";
$conn->query($cleanup_sql);

// Update responder status to 'Active' if logged in as responder
if (isset($_SESSION['role']) && $_SESSION['role'] === 'responder' && isset($_SESSION['username'])) {
    $responder_name = $_SESSION['username'];
    $update_sql = "UPDATE emergency_responders SET status = 'Active', last_active = NOW() WHERE name = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("s", $responder_name);
    $stmt->execute();
    $stmt->close();
}

// Update nurse last_active on page load
if (isset($_SESSION['user_id'])) {
    $update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

$result_appt = false;
$result_resp = false;
$result_emerg = false;
$calendarEvents = [];

if ($conn) {
    // Show latest appointments that are actionable/accepted for the activity list
    $sql_appt = "SELECT a.id,
                 a.appointment_time,
                 COALESCE(NULLIF(TRIM(CONCAT(s.first_name,' ',s.last_name)), ''), a.student_id) AS student_name,
                 a.reason,
                 a.status,
                 a.remarks,
                 s.section,
                 s.year_level,
                 (SELECT medicine_name FROM appointment_medications am WHERE am.appointment_id = a.id ORDER BY am.created_at DESC LIMIT 1) AS medicine_name
                 FROM appointments a
                 LEFT JOIN students s ON (s.id = a.student_id OR s.student_id = a.student_id)
                 WHERE (a.status IS NULL OR TRIM(a.status) NOT IN ('Rejected','Declined','Cancelled','Completed'))
                 ORDER BY a.appointment_time DESC
                 LIMIT 300";
    $result_appt = $conn->query($sql_appt);

    // Build calendar events from appointments
    if ($result_appt && $result_appt->num_rows > 0) {
        $result_appt->data_seek(0); // Reset pointer
        while ($row = $result_appt->fetch_assoc()) {
            $title = htmlspecialchars($row['student_name']) . ' - ' . htmlspecialchars(substr($row['reason'], 0, 20)) . (strlen($row['reason']) > 20 ? '...' : '');
            $calendarEvents[] = [
                'id' => $row['id'],
                'title' => $title,
                'start' => $row['appointment_time'],
                'extendedProps' => [
                    'student_name' => htmlspecialchars($row['student_name']),
                    'reason' => htmlspecialchars($row['reason']),
                    'status' => $row['status'] ?: 'Pending'
                ]
            ];
        }
    }

    $sql_resp = "SELECT name, status FROM emergency_responders ORDER BY name ASC LIMIT 20";
    $result_resp = $conn->query($sql_resp);

    $sql_emerg = "SELECT message, created_at FROM notifications WHERE message LIKE 'Emergency%' ORDER BY created_at DESC LIMIT 20";
    $result_emerg = $conn->query($sql_emerg);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nurse Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .fc-day-today { background: #fee2e2 !important; font-weight: bold; border: 2px solid #dc2626 !important; border-radius: 4px; }
        .fc-event.appointment { background-color: #dc2626 !important; border-color: #dc2626 !important; color: white !important; font-weight: bold !important; }
        #calendar { min-height: 300px; }
        .fc-event { font-size: 10px; line-height: 1.1; padding: 1px 2px; max-width: 100%; }
        .fc-event-title { font-size: 10px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 100%; }
        .fc-event-time { white-space: nowrap; max-width: 20px; overflow: hidden; display: inline-block; vertical-align: middle; }
        #skipBtn:disabled { opacity: 0.5; cursor: not-allowed; }
        /* Professional styling: Modern, clean design with subtle shadows and improved spacing */
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; color: #374151; }
        header { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); box-shadow: 0 4px 20px rgba(220, 38, 38, 0.15); width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; padding: 15px 30px; color: white; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .header-left { display: flex; align-items: center; gap: 20px; flex: 1; justify-content: center; }
        .header-title { font-size: 28px; font-weight: 700; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-right { display: flex; align-items: center; gap: 20px; justify-content: flex-end; margin-right: 20px; }
        .profile-avatar { width: 50px; height: 50px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.8); object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease; }
        .profile-avatar:hover { transform: scale(1.05); }
        .profile-name { font-size: 18px; font-weight: 600; margin: 0; }
        .notification-bell { position: relative; cursor: pointer; padding: 8px; border-radius: 50%; transition: background 0.3s ease; }
        .notification-bell:hover { background: rgba(255,255,255,0.1); }
        .notification-badge { position: absolute; top: -2px; right: -2px; background: #fbbf24; color: #92400e; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid white; display: none; }
        #mainShell { max-width: 1600px; margin: 30px auto; padding: 0 30px; height: calc(100vh - 120px); display: flex; gap: 30px; flex-wrap: wrap; }
        .main-left { flex: 1; min-width: 1000px; max-width: calc(100vw - 400px); height: 100%; overflow-y: auto; padding: 0; background: transparent; border-radius: 0; box-shadow: none; }
        .main-right { flex: 0 0 380px; min-width: 380px; height: 100%; }
        #appointmentCard { background: white; border: none; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); transition: all 0.4s ease; width: 100%; overflow: hidden; }
        #appointmentCard:hover { box-shadow: 0 12px 48px rgba(0,0,0,0.12); transform: translateY(-2px); }
        #appointmentCard h3 { color: white; font-size: 22px; font-weight: 700; margin: 0; padding: 20px 30px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border-bottom: none; border-radius: 16px 16px 0 0; }
        /* Professional Calendar Design */
        #calendar { font-size: 0.85rem !important; height: 450px !important; border-radius: 12px !important; background: white !important; box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important; overflow: hidden !important; }
        #calendar .fc-toolbar { margin-bottom: 10px !important; padding: 15px 20px !important; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 2px solid #dc2626; }
        #calendar .fc-toolbar-title { font-size: 18px !important; color: #dc2626 !important; font-weight: 700 !important; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        #calendar .fc-button { font-size: 0.8rem !important; padding: 0.4rem 0.8rem !important; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important; border: none !important; border-radius: 8px !important; color: white !important; transition: all 0.3s ease !important; font-weight: 600 !important; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2) !important; }
        #calendar .fc-button:hover { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%) !important; transform: translateY(-1px) !important; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3) !important; }
        #calendar .fc-button:active { transform: translateY(0) !important; }
        #calendar .fc-header-toolbar { margin-bottom: 5px !important; }
        #calendar .fc-daygrid-body { max-height: none !important; overflow-y: visible !important; }
        #calendar .fc-daygrid-day { height: 3rem !important; min-height: 3rem !important; position: relative !important; }
        #calendar .fc-daygrid-day-number { font-size: 0.9rem !important; color: #374151; font-weight: 600; padding: 4px 6px !important; }
        #calendar .fc-event { font-size: 0.75rem !important; padding: 2px 4px !important; margin: 1px 2px !important; border-radius: 6px !important; border: none !important; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important; color: white !important; font-weight: 500 !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important; transition: all 0.2s ease !important; cursor: pointer !important; }
        #calendar .fc-event:hover { transform: translateY(-1px) !important; box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important; }
        #calendar .fc-day-today { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important; border: 2px solid #dc2626 !important; border-radius: 8px !important; }
        #calendar .fc-day-today .fc-daygrid-day-number { color: #92400e !important; font-weight: 700 !important; }
        #calendar .fc-col-header-cell { padding: 0.5rem !important; font-size: 0.9rem !important; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important; color: white !important; border: 1px solid rgba(255,255,255,0.2) !important; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        #calendar .fc-daygrid-day-frame { min-height: 3rem !important; border: 1px solid #e5e7eb !important; transition: background 0.2s ease !important; }
        #calendar .fc-daygrid-day-frame:hover { background: #f8fafc !important; }
        #calendar .fc-daygrid-day-top { padding: 2px !important; }
        #calendar .fc-more-link { font-size: 0.75rem !important; color: #dc2626 !important; font-weight: 600 !important; text-decoration: none !important; padding: 1px 3px !important; background: rgba(220, 38, 38, 0.1) !important; border-radius: 4px !important; transition: all 0.2s ease !important; }
        #calendar .fc-more-link:hover { background: rgba(220, 38, 38, 0.2) !important; color: #b91c1c !important; }
        /* Event tooltip */
        .fc-event-tooltip { position: absolute; background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; font-size: 0.8rem; max-width: 200px; display: none; }
        .fc-event-tooltip .tooltip-title { font-weight: 600; color: #dc2626; margin-bottom: 4px; }
        .fc-event-tooltip .tooltip-time { color: #6b7280; font-size: 0.75rem; }
        
        /* Day popover styling for better overlay and readability */
        .fc-popover {
            position: absolute !important;
            z-index: 9999 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
            max-width: 300px !important;
            font-size: 0.85rem !important;
            overflow: visible !important;
            top: 0 !important;
            left: 0 !important;
            transform: translate(0, 0) !important;
        }
        .fc-popover .fc-popover-header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
            padding: 12px 16px !important;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
        }
        .fc-popover .fc-popover-body {
            padding: 12px !important;
            max-height: 300px !important;
            overflow-y: auto !important;
        }
        .fc-popover .fc-event {
            margin-bottom: 8px !important;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        .fc-popover .fc-event-title {
            color: white !important;
            font-weight: 500 !important;
        }
        .fc-popover .fc-event-time {
            color: rgba(255,255,255,0.8) !important;
            font-size: 0.75rem !important;
        }
        /* Ensure calendar container allows absolute positioning */
        #calendar {
            position: relative !important;
        }
        /* Hide unnecessary elements for compactness */
        #calendar .fc-toolbar-chunk:last-child { display: none !important; }
        #calendar .fc-prev-button, #calendar .fc-next-button { font-size: 0.8rem !important; }
        /* Table styling to match image: clean, professional */
        #appointmentTable { width: 100%; border-collapse: collapse; font-size: 14px; border: none; border-radius: 0; overflow: hidden; box-shadow: none; table-layout: fixed; }
        #appointmentTable th { background: #dc2626; color: white; font-weight: 700; padding: 12px 8px; text-align: left; border-bottom: 1px solid #dc2626; border-right: 1px solid #dc2626; }
        #appointmentTable th:last-child { border-right: none; }
        #appointmentTable td:nth-child(1) { width: 8%; } /* Date/Time */
        #appointmentTable td:nth-child(2) { width: 12%; } /* Student Name */
        #appointmentTable td:nth-child(3) { width: 10%; } /* Course */
        #appointmentTable td:nth-child(4) { width: 18%; white-space: normal; word-wrap: break-word; } /* Reason */
        #appointmentTable td:nth-child(5) { width: 8%; } /* Medicine */
        #appointmentTable td:nth-child(6) { width: 6%; } /* Status */
        #appointmentTable td:nth-child(7) { width: 14%; white-space: normal; word-wrap: break-word; } /* Remarks */
        #appointmentTable td:nth-child(8) { width: 16%; display: flex; justify-content: center; align-items: center; gap: 8px; min-width: 150px; white-space: nowrap; }
        #appointmentTable td { padding: 12px 8px; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; vertical-align: top; overflow: hidden; text-overflow: ellipsis; word-wrap: break-word; }
        #appointmentTable td:last-child { border-right: none; }
        #appointmentTable tr:nth-child(even) { background: #f3f4f6; }
        #appointmentTable tr:hover { background: #e5e7eb; cursor: pointer; }
        #appointmentTable .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #d1fae5; color: #059669; }
        .status-completed { background: #dbeafe; color: #2563eb; }
        .remarks-input { width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 6px 8px; font-size: 13px; resize: vertical; }
        .course-cell { font-style: italic; color: #6b7280; }

        th i, h3 i { margin-right: 0.5rem; }

        #remarksModal .modal-content {
            max-width: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: none;
            padding: 0 30px 30px 30px;
            position: relative;
        }
        #remarksModal h2 {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 20px 30px;
            margin: -20px -30px 20px -30px;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            font-size: 22px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        #remarksModal .close {
            color: white;
            font-size: 28px;
            position: absolute;
            right: 15px;
            top: 15px;
            cursor: pointer;
            z-index: 1;
        }
        #remarksModal .close:hover {
            color: rgba(255,255,255,0.8);
        }
        #remarksModal .remarks-input {
            margin-bottom: 20px;
            font-size: 14px;
        }
        #remarksModal #saveRemarksBtn {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        #remarksModal #saveRemarksBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        #remarksModal.view-mode .remarks-input {
            background-color: #f9fafb;
            border-color: #e5e7eb;
            color: #374151;
        }
        /* Calendar title dynamic update */
        .calendar-title { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 16px 20px; border-bottom: 2px solid #dc2626; font-size: 18px; font-weight: 700; color: #dc2626; margin: 0; border-radius: 12px 12px 0 0; }
        /* Responsive: stack on mobile */
        @media (max-width: 768px) { #mainShell { flex-direction: column; } .main-left, .main-right { min-width: 100%; max-width: 100%; } }
        /* Preserve existing modals and sidebar styles */
        #emergencyModal { z-index: 60; }
        .emergency-content { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        /* Sidebar hamburger menu */
        #sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: #ffffff;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            z-index: 1000;
            padding: 1rem;
            overflow-y: auto;
            border-right: 1px solid #e5e7eb;
        }
        #sidebar.open {
            left: 0;
        }
        #sidebar h2 {
            color: #b71c1c;
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        #sidebar a {
            display: block;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        #sidebar a:hover {
            background: #dc2626;
            color: white;
            transform: translateX(5px);
            font-weight: bold;
        }
        #sidebar summary {
            color: #374151;
            font-weight: 600;
            cursor: pointer;
        }
        #sidebar details {
            margin-bottom: 1rem;
        }
        #sidebar ul {
            list-style: none;
            padding-left: 1rem;
            margin-top: 0.5rem;
        }
        #sidebar li {
            margin-bottom: 0.25rem;
        }
        #sidebar a i {
            margin-right: 0.5rem;
            width: 20px;
        }
        #notificationDrawer > div:first-child { background-color: #dc2626 !important; }
        /* Overlay for sidebar */
        #sidebarOverlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); opacity: 0; visibility: hidden; transition: all 0.3s ease; z-index: 999; }
        #sidebarOverlay.open { opacity: 1; visibility: visible; }
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        #detailsModal .modal-content { max-width: 800px; }
        #appointmentDetailsModal .modal-content {
            max-width: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: none;
        }
        #appointmentDetailsModal h2 {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 20px 30px;
            margin: -20px -20px 20px -20px;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            font-size: 22px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        #appointmentDetailsModal .close {
            color: white;
            font-size: 28px;
            position: center;
           
        }
        #appointmentDetailsModal .close:hover {
            color: rgba(255,255,255,0.8);
        }
        #medicineEntriesContainer { margin-top: 20px; }
        .medicine-entry { background: #f3f4f6; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .medicine-form { background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<!-- Header - Professional Design -->
<header>
<div class="header-content">
<button id="menuBtn" class="text-2xl font-bold cursor-pointer hover:bg-white hover:bg-opacity-10 rounded p-2 transition absolute left-5 top-1/2 -translate-y-1/2 z-10">☰</button>
<div class="header-left">
<h1 class="header-title"> School Clinic Management System</h1>
</div>
<div class="header-right">
<div class="notification-bell" onclick="openDrawer()">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
<span id="notifBadge" class="notification-badge"></span>
</div>
<img src="images/nurse.jpg" alt="Nurse Profile" class="profile-avatar">
<div>
<span class="profile-name">Mrs. Lorefe Verallo</span>
<p style="margin: 0; font-size: 12px; opacity: 0.8;">Nurse Administrator</p>
</div>
</div>
</div>

<!-- Sidebar -->
<nav id="sidebar">
<h2>File Clinic Explorer</h2>
<details>
    <summary>📁 Documents</summary>
    <ul>
    <li><a href="physical_assessment.php"><i class="fas fa-file-medical"></i> Student Physical Assessment Form</a></li>
    <li><a href="health_service_report.php"><i class="fas fa-chart-bar"></i> Health Service Utilization Report</a></li>
    <li><a href="first_aid.php"><i class="fas fa-first-aid"></i> First Aid Procedure</a></li>
    <li><a href="emergency_plan.html"><i class="fas fa-exclamation-triangle"></i> Emergency Respond Plan</a></li>
    </ul>
</details>
<a href="medication_dashboard.php"><i class="fas fa-pills"></i> Medical Supplies</a>
<a href="studentfile_dashboard.php"><i class="fas fa-folder-open"></i> Student File Dashboard</a>
<a href="Student_visitlogs.php"><i class="fas fa-history"></i> Student Visit Logs</a>
<a href="appointment_history.php"><i class="fas fa-calendar-alt"></i> Appointment History</a>
<a href="emergency_reports.php"><i class="fas fa-bell"></i> Emergency Reports</a>
<a href="convert_responder.php"><i class="fas fa-user-shield"></i> Emergency Responder</a>
<a href="responder_status.php"><i class="fas fa-user-shield"></i> Responder Status</a>
<a href="nurse_reset_password.php"><i class="fas fa-key"></i> Reset User Password</a>
<a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>
</header>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay"></div>

<div id="mainShell">
<div class="main-left">
<div id="appointmentCard">
<h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-calendar-check"></i>Appointment Activity</h3>
<div id="appointments" class="overflow-x-auto bg-white">
<table id="appointmentTable">
<thead>
<tr>
<th class="text-left"><i class="fas fa-calendar-alt"></i> Date/Time</th>
<th class="text-left"><i class="fas fa-user"></i> Student Name</th>
<th class="text-left"><i class="fas fa-graduation-cap"></i> Course</th>
<th class="text-left"><i class="fas fa-clipboard-list"></i> Reason</th>
<th class="text-left"><i class="fas fa-pills"></i> Medicine</th>
<th class="text-left"><i class="fas fa-chart-bar"></i> Status</th>
<th class="text-left"><i class="fas fa-sticky-note"></i> Remarks</th>
<th class="text-left"><i class="fas fa-cogs"></i> Actions</th>
</tr>
</thead>
<tbody id="appointmentTableBody">
<?php if ($result_appt && $result_appt->num_rows > 0): ?>
<?php $result_appt->data_seek(0); // Reset pointer ?>
<?php while($row = $result_appt->fetch_assoc()): ?>
<?php
$datetime = date('M d, Y h:i A', strtotime($row['appointment_time']));
$course = !empty($row['section']) ? $row['section'] : 'N/A';
$statusClass = !empty($row['status']) ? (strpos($row['status'], 'Confirmed') !== false ? 'status-confirmed' : (strpos($row['status'], 'Completed') !== false ? 'status-completed' : 'status-pending')) : 'status-pending';
$statusText = !empty($row['status']) ? htmlspecialchars($row['status']) : 'Pending';
$medicine = !empty($row['medicine_name']) ? htmlspecialchars($row['medicine_name']) : 'N/A';
$remarksText = !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : 'No remarks';
$hasRemarks = !empty($row['remarks']);
?>
<tr id="row-<?php echo $row['id']; ?>" class="hover:bg-gray-50">
<td><?php echo $datetime; ?></td>
<td><?php echo htmlspecialchars($row['student_name']); ?></td>
<td class="course-cell"><?php echo $course; ?></td>
<td><?php echo htmlspecialchars($row['reason']); ?></td>
<td><?php echo $medicine; ?></td>
<td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
<td>
<?php if (!$hasRemarks): ?>
<button class="add-remarks-btn bg-green-500 text-white px-2 py-1 rounded text-sm mt-1" onclick="openRemarksModal(<?php echo $row['id']; ?>, false, true)" title="Add Remarks"><i class="fas fa-plus"></i></button>
<?php else: ?>
<button class="edit-remarks-btn bg-blue-500 text-white px-2 py-1 rounded text-sm mt-1" onclick="openRemarksModal(<?php echo $row['id']; ?>, false, false)" title="Edit Remarks"><i class="fas fa-edit"></i></button>
<button class="view-remarks-btn bg-gray-500 text-white px-2 py-1 rounded text-sm mt-1 ml-2" onclick="openRemarksModal(<?php echo $row['id']; ?>, true, false)" title="View Remarks"><i class="fas fa-eye"></i></button>
<?php endif; ?>
</td>
<td>
<button class="edit-details-btn bg-blue-500 text-white px-2 py-1 rounded text-sm mr-1" onclick="openDetailsModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['student_name']); ?>', '<?php echo htmlspecialchars($row['reason']); ?>', '<?php echo $row['appointment_time']; ?>')" title="Edit Details"><i class="fas fa-edit"></i></button>
<button class="done-btn bg-green-500 text-white px-2 py-1 rounded text-sm" onclick="markAsDone(<?php echo $row['id']; ?>)" title="Mark Done"><i class="fas fa-check"></i></button>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500 text-lg">No appointments found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="main-right">
<div class="calendar-title" id="calendarTitle">Mon 17 Aug</div>
<div id="calendar"></div>
</div>
</div>


<div id="notificationDrawer" class="fixed top-0 right-0 w-full md:w-96 h-full bg-white shadow-lg z-50 transform translate-x-full transition-transform duration-300 rounded-l-3xl border-l-4 border-red-500 overflow-auto">
<div class="bg-red-500 text-white flex items-center p-4">
<button onclick="closeDrawer()" class="mr-2 text-xl font-bold">&larr;</button>
<h2 class="text-lg font-semibold">Notifications </h2>
</div>
<div id="drawerContent" class="p-4 space-y-4"></div>
</div>

<!-- Emergency Alert Modal -->
<div id="emergencyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-60">
<div class="bg-red-600 text-white p-8 rounded-lg shadow-xl text-center max-w-md emergency-content">
<h2 class="text-4xl font-bold mb-4">EMERGENCY!!</h2>
<p class="text-lg mb-4">An emergency has been reported. Please check notifications immediately.</p>
<button onclick="document.getElementById('emergencyModal').classList.add('hidden');" class="bg-white text-red-600 px-6 py-3 rounded font-bold hover:bg-gray-100">View Emergencies</button>
</div>
</div>


<audio id="bellSound" preload="auto">
<source src="bell.mp3" type="audio/mpeg">
</audio>
<audio id="alarmSound" preload="auto">
<source src="emergency.mp3" type="audio/mpeg">
</audio>


<!-- Remarks Modal -->
<div id="remarksModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeRemarksModal()">&times;</span>
<h2 id="remarksModalTitle">Edit Remarks</h2>
<textarea id="remarksTextarea" class="remarks-input" rows="4" placeholder="Enter remarks here..."></textarea>
<button id="saveRemarksBtn" class="bg-blue-500 text-white px-4 py-2 rounded mt-2">Save Remarks</button>
</div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeDetailsModal()">&times;</span>
<h2>Edit Appointment Details</h2>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Student Name</label>
<input type="text" id="studentNameInput" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" readonly>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Appointment Time</label>
<input type="datetime-local" id="appointmentTimeInput" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" readonly>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Reason</label>
<textarea id="reasonTextarea" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3"></textarea>
</div>
<div id="medicineEntriesContainer"></div>
<div class="medicine-form">
<h3 class="text-lg font-semibold mb-2">Add Medicine</h3>
<div class="grid grid-cols-2 gap-4">
<select id="medicineSelect" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
<option value="">Select Medicine</option>
</select>
<input type="text" id="dosageInput" placeholder="Dosage (e.g., 500mg)" class="border border-gray-300 rounded px-3 py-2" readonly>
<input type="number" id="quantityInput" placeholder="Quantity" class="border border-gray-300 rounded px-3 py-2" min="1">
<input type="text" id="actionTakenInput" placeholder="Action Taken" class="border border-gray-300 rounded px-3 py-2">
</div>
<button id="addMedicineBtn" class="bg-green-500 text-white px-4 py-2 rounded mt-2">Add Medicine</button>
</div>
<button id="saveChangesBtn" class="bg-blue-500 text-white px-4 py-2 rounded mt-4">Save Changes</button>
</div>
</div>

<!-- Appointment Details Modal for Calendar Events -->
<div id="appointmentDetailsModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeAppointmentDetailsModal()">&times;</span>
<h2>Appointment Details</h2>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Student Name</label>
<p id="detailsStudentName" class="mt-1 text-gray-900"></p>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Date & Time</label>
<p id="detailsDateTime" class="mt-1 text-gray-900"></p>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Reason</label>
<p id="detailsReason" class="mt-1 text-gray-900"></p>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700">Status</label>
<p id="detailsStatus" class="mt-1 text-gray-900"></p>
</div>
</div>
</div>

<script>
let calendar;
let currentAppointmentId = null;
let currentRemarksAppointmentId = null;
let isViewOnlyRemarks = false;
let alarmInterval = null;
let isAlarmLooping = false;

// Audio unlock on first interaction
document.addEventListener("click", () => {
    const bell = document.getElementById('bellSound');
    if (bell) {
        bell.play().then(() => {
            bell.pause();
            bell.currentTime = 0;
            console.log("🔓 Audio unlocked");
        }).catch(() => {
            console.log("⚠️ Audio unlock failed, will use fallback beep");
        });
    }
}, { once: true });

document.addEventListener('DOMContentLoaded', function() {
    // Initialize FullCalendar
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: <?php echo json_encode($calendarEvents); ?>,
        dayMaxEvents: true, // Show "+X more" when multiple events in a day
        eventMaxStack: 2, // Stack up to 2 events before showing more link
        dayPopoverFormat: { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' },
        moreLinkClick: 'popover', // Use popover for +X more
        eventDidMount: function(info) {
            // Make events focusable for keyboard accessibility
            info.el.tabIndex = 0;
            info.el.setAttribute('aria-label', `Appointment: ${info.event.title} on ${info.event.start.toLocaleString()}`);
        },
        eventClick: function(info) {
            // Show appointment details in modal
            showAppointmentDetailsModal(info.event);
        },
        datesSet: function(dateInfo) {
            // Update calendar title
            const title = dateInfo.view.title;
            document.getElementById('calendarTitle').textContent = title;
        }
    });
    calendar.render();

    // Sidebar toggle
    document.getElementById('menuBtn').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });

    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });

    // Load notifications on page load
    loadNotifications();

    // Check for emergencies
    checkEmergencies();

    // Load medicine dropdown
    loadMedicines();
});

function openRemarksModal(appointmentId, viewOnly = false, isAdd = false) {
    currentRemarksAppointmentId = appointmentId;
    isViewOnlyRemarks = viewOnly;
    const modal = document.getElementById('remarksModal');
    const title = document.getElementById('remarksModalTitle');
    const textarea = document.getElementById('remarksTextarea');
    const saveBtn = document.getElementById('saveRemarksBtn');

    if (viewOnly) {
        title.textContent = 'View Remarks';
        textarea.readOnly = true;
        saveBtn.style.display = 'none';
        modal.classList.add('view-mode');
    } else {
        title.textContent = isAdd ? 'Add Remarks' : 'Edit Remarks';
        textarea.readOnly = false;
        saveBtn.style.display = 'block';
        modal.classList.remove('view-mode');
    }

    // Fetch current remarks
    $.ajax({
        url: 'nurse.php?action=get_remarks&appointment_id=' + appointmentId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                textarea.value = response.remarks;
            } else {
                textarea.value = '';
            }
        },
        error: function() {
            textarea.value = '';
        }
    });

    modal.style.display = 'block';
}

// Function to close remarks modal
function closeRemarksModal() {
    const modal = document.getElementById('remarksModal');
    modal.style.display = 'none';
    modal.classList.remove('view-mode');
    currentRemarksAppointmentId = null;
}

// Save remarks
document.getElementById('saveRemarksBtn').addEventListener('click', function() {
    const remarks = document.getElementById('remarksTextarea').value.trim();
    if (currentRemarksAppointmentId) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            data: {
                action: 'save_remarks',
                appointment_id: currentRemarksAppointmentId,
                remarks: remarks
            },
            success: function(response) {
                if (response.success) {
                    alert('Remarks saved successfully');
                    closeRemarksModal();
                    // Refresh table if needed
                    location.reload();
                } else {
                    alert('Error saving remarks: ' + response.message);
                }
            },
            error: function() {
                alert('Error saving remarks');
            }
        });
    }
});

// Function to open details modal
function openDetailsModal(appointmentId, studentName, reason, appointmentTime) {
    currentAppointmentId = appointmentId;
    const modal = document.getElementById('detailsModal');
    document.getElementById('studentNameInput').value = studentName;
    document.getElementById('appointmentTimeInput').value = new Date(appointmentTime).toISOString().slice(0, 16);
    document.getElementById('reasonTextarea').value = reason;

    // Load medicine entries
    loadMedicineEntries(appointmentId);

    // Ensure medicines are loaded for the dropdown
    loadMedicines();

    modal.style.display = 'block';
}

// Function to close details modal
function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
    currentAppointmentId = null;
}

// Function to show appointment details modal for calendar events
function showAppointmentDetailsModal(event) {
    const modal = document.getElementById('appointmentDetailsModal');
    document.getElementById('detailsStudentName').textContent = event.extendedProps.student_name || 'N/A';
    document.getElementById('detailsDateTime').textContent = event.start.toLocaleString();
    document.getElementById('detailsReason').textContent = event.extendedProps.reason || 'N/A';
    document.getElementById('detailsStatus').textContent = event.extendedProps.status || 'Pending';
    modal.style.display = 'block';
}

// Function to close appointment details modal
function closeAppointmentDetailsModal() {
    document.getElementById('appointmentDetailsModal').style.display = 'none';
}

// Load medicine entries
function loadMedicineEntries(appointmentId) {
    const container = document.getElementById('medicineEntriesContainer');
    container.innerHTML = '<p>Loading...</p>';

    $.ajax({
        url: 'nurse.php?action=get_medicine_entries&id=' + appointmentId,
        method: 'GET',
        success: function(entries) {
            container.innerHTML = '';
            if (entries.length > 0) {
                entries.forEach(function(entry) {
                    const entryDiv = document.createElement('div');
                    entryDiv.className = 'medicine-entry';
                    entryDiv.innerHTML = `
                        <p><strong>Medicine:</strong> ${entry.medicine_name}</p>
                        <p><strong>Dosage:</strong> ${entry.dosage}</p>
                        <p><strong>Quantity:</strong> ${entry.quantity}</p>
                        <p><strong>Action Taken:</strong> ${entry.action_taken}</p>
                        <p><strong>Created:</strong> ${new Date(entry.created_at).toLocaleString()}</p>
                        <button class="delete-medicine-btn bg-red-500 text-white px-2 py-1 rounded text-sm" onclick="deleteMedicineEntry(${entry.id}, '${entry.medicine_name}', ${entry.quantity})">Delete</button>
                    `;
                    container.appendChild(entryDiv);
                });
            } else {
                container.innerHTML = '<p>No medicine entries found.</p>';
            }
        },
        error: function() {
            container.innerHTML = '<p>Error loading medicine entries.</p>';
        }
    });
}

// Load medicines for dropdown
function loadMedicines() {
    $.ajax({
        url: 'nurse.php?action=fetch_medications',
        method: 'GET',
        success: function(medicines) {
            const select = document.getElementById('medicineSelect');
            select.innerHTML = '<option value="">Select Medicine</option>';
            medicines.forEach(function(medicine) {
                const option = document.createElement('option');
                option.value = medicine.name;
                option.textContent = medicine.name;
                option.setAttribute('data-dosage', medicine.dosage || '500mg');
                select.appendChild(option);
            });

            // Add change event to auto-fill dosage
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const dosage = selectedOption.getAttribute('data-dosage') || '';
                document.getElementById('dosageInput').value = dosage;
            });
        },
        error: function() {
            console.error('Error loading medicines');
        }
    });
}

// Add medicine
document.getElementById('addMedicineBtn').addEventListener('click', function() {
    const medicine = document.getElementById('medicineSelect').value;
    const dosage = document.getElementById('dosageInput').value.trim();
    const quantity = parseInt(document.getElementById('quantityInput').value);
    const actionTaken = document.getElementById('actionTakenInput').value.trim();

    if (!medicine || !quantity || !actionTaken) {
        alert('Medicine, quantity, and action taken are required');
        return;
    }

    $.ajax({
        url: 'nurse.php',
        method: 'POST',
        data: {
            action: 'save_medicine_entry',
            appointment_id: currentAppointmentId,
            medicine_name: medicine,
            dosage: dosage,
            quantity: quantity,
            action_taken: actionTaken
        },
        success: function(response) {
            if (response.success) {
                alert('Medicine added successfully');
                // Clear form
                document.getElementById('medicineSelect').value = '';
                document.getElementById('dosageInput').value = '';
                document.getElementById('quantityInput').value = '';
                document.getElementById('actionTakenInput').value = '';
                // Reload entries
                loadMedicineEntries(currentAppointmentId);
            } else {
                alert('Error adding medicine: ' + response.message);
            }
        },
        error: function() {
            alert('Error adding medicine');
        }
    });
});

// Delete medicine entry
function deleteMedicineEntry(entryId, medicineName, quantity) {
    if (confirm('Are you sure you want to delete this medicine entry?')) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            data: {
                action: 'delete_medicine_entry',
                id: entryId,
                medicine_name: medicineName,
                quantity: quantity
            },
            success: function(response) {
                if (response.success) {
                    alert('Medicine entry deleted successfully');
                    loadMedicineEntries(currentAppointmentId);
                } else {
                    alert('Error deleting medicine entry: ' + response.message);
                }
            },
            error: function() {
                alert('Error deleting medicine entry');
            }
        });
    }
}

// Save changes (reason update)
document.getElementById('saveChangesBtn').addEventListener('click', function() {
    const reason = document.getElementById('reasonTextarea').value.trim();

    if (!reason) {
        alert('Reason is required');
        return;
    }

    $.ajax({
        url: 'nurse.php',
        method: 'POST',
        data: {
            action: 'update_appointment',
            id: currentAppointmentId,
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert('Appointment updated successfully');
                closeDetailsModal();
                location.reload();
            } else {
                alert('Error updating appointment: ' + response.message);
            }
        },
        error: function() {
            alert('Error updating appointment');
        }
    });
});

// Mark as done
function markAsDone(appointmentId) {
    if (confirm('Are you sure you want to mark this appointment as done?')) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'mark_done',
                id: appointmentId
            },
            success: function(response) {
                if (response.success) {
                    alert('Appointment marked as done');
                    location.reload(); // Refresh to update the table and calendar
                } else {
                    alert('Error marking as done: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText, status, error);
                alert('Error marking as done: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Network or server error'));
            }
        });
    }
}

// Notification functions
function openDrawer() {
    const drawer = document.getElementById('notificationDrawer');
    drawer.classList.remove('translate-x-full');
    loadNotifications();
}

function closeDrawer() {
    const drawer = document.getElementById('notificationDrawer');
    drawer.classList.add('translate-x-full');
}

function loadNotifications() {
    const content = document.getElementById('drawerContent');
    const badge = document.getElementById('notifBadge');
    content.innerHTML = '<p>Loading...</p>';

    let totalCount = 0;

    // Load general notifications
    $.ajax({
        url: 'nurse.php?action=nurse_general_notifications',
        method: 'GET',
        success: function(notes) {
            let html = '<h3 class="font-semibold text-lg mb-2 border-b pb-2">General Notifications</h3>';
            const emergencyCount = notes.filter(note => note.message.includes('Emergency')).length;
            totalCount += notes.length;

            if (notes.length > 0) {
                notes.forEach(function(note) {
                    const isEmergency = note.message.includes('Emergency');
                    html += `
                        <div class="bg-${isEmergency ? 'red-50 border-red-200' : 'gray-100'} p-3 rounded border ${isEmergency ? 'border-red-300' : ''}">
                            <p class="text-sm ${isEmergency ? 'text-red-800 font-medium' : ''}">${note.message}</p>
                            <p class="text-xs text-gray-500">${new Date(note.created_at).toLocaleString()}</p>
                            ${isEmergency ? '<button class="mark-read-btn bg-blue-500 text-white px-3 py-1 rounded text-sm mt-2" onclick="markNotificationRead(' + note.id + ')">Mark as Read</button>' : ''}
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-gray-500 italic">No general notifications.</p>';
            }

            // Load pending appointments as notifications
            $.ajax({
                url: 'nurse.php?action=notification_checker',
                method: 'GET',
                success: function(pending) {
                    const emergencyPending = pending.filter(appt => appt.is_emergency).length;
                    totalCount += pending.length;

                    html += '<h3 class="font-semibold text-lg mb-2 border-b pb-2 mt-4">Pending Appointments</h3>';
                    if (pending.length > 0) {
                        pending.forEach(function(appt) {
                            const isEmergency = appt.is_emergency;
                            const yearSection = `${appt.year_level || 'N/A'} / ${appt.section || 'N/A'}`;
                            const course = appt.course || 'N/A';
                            html += `
                                <div class="bg-${isEmergency ? 'yellow-50 border-yellow-200' : 'gray-100'} p-3 rounded border ${isEmergency ? 'border-yellow-300' : ''}">
                                    <div class="font-medium ${isEmergency ? 'text-yellow-800' : ''}">Student: ${appt.student_name}</div>
                                    <div class="text-sm">Year/Section: ${yearSection}</div>
                                    <div class="text-sm">Course: ${course}</div>
                                    <div class="text-sm mt-1">Reason: ${appt.reason}</div>
                                    <div class="text-xs text-gray-500 mt-1">Time: ${new Date(appt.appointment_time).toLocaleString()}</div>
                                    <p class="text-xs text-gray-500">ID: ${appt.id}</p>
                                    <div class="mt-2 space-x-2">
                                        <button class="accept-btn bg-green-500 text-white px-3 py-1 rounded text-sm" onclick="acceptAppointment(${appt.id})">Accept</button>
                                        <button class="reject-btn bg-red-500 text-white px-3 py-1 rounded text-sm" onclick="rejectAppointment(${appt.id})">Reject</button>
                                        <button class="reschedule-btn bg-blue-500 text-white px-3 py-1 rounded text-sm" onclick="rescheduleAppointment(${appt.id})">Reschedule</button>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="text-gray-500 italic">No pending appointments.</p>';
                    }

                    content.innerHTML = html;

                    // Update badge if there are notifications
                    if (totalCount > 0) {
                        badge.textContent = totalCount;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                },
                error: function() {
                    content.innerHTML += '<p class="text-red-500">Error loading pending appointments.</p>';
                }
            });
        },
        error: function() {
            content.innerHTML = '<p class="text-red-500">Error loading notifications.</p>';
        }
    });
}

function checkEmergencies() {
    $.ajax({
        url: 'nurse.php?action=nurse_general_notifications',
        method: 'GET',
        success: function(notes) {
            const emergencies = notes.filter(note => note.message.includes('Emergency'));
            const modal = document.getElementById('emergencyModal');
            if (emergencies.length > 0) {
                if (modal.classList.contains('hidden')) {
                    modal.classList.remove('hidden');
                    playAlarmLoop();
                }
            } else {
                stopAlarm();
                modal.classList.add('hidden');
            }
        },
        error: function() {
            console.error('Error checking emergencies');
        }
    });
}

function closeEmergencyModal() {
    document.getElementById('emergencyModal').classList.add('hidden');
}

function viewEmergencies() {
    closeEmergencyModal();
    openDrawer();
}

// Alarm looping functions
function playAlarmLoop() {
    const alarm = document.getElementById('alarmSound');
    if (alarm && !isAlarmLooping) {
        isAlarmLooping = true;
        alarm.loop = true;
        alarm.play().catch(e => {
            console.log('Alarm play failed:', e);
            // Fallback beep using Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            oscillator.frequency.value = 800; // High pitch for alarm
            oscillator.type = 'square';
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1);
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 1);
        });
        // Loop the alarm every 3 seconds if audio doesn't loop
        alarmInterval = setInterval(() => {
            if (isAlarmLooping) {
                alarm.currentTime = 0;
                alarm.play().catch(e => console.log('Retry failed:', e));
            }
        }, 3000);
    }
}

function stopAlarm() {
    isAlarmLooping = false;
    if (alarmInterval) {
        clearInterval(alarmInterval);
        alarmInterval = null;
    }
    const alarm = document.getElementById('alarmSound');
    if (alarm) {
        alarm.pause();
        alarm.currentTime = 0;
        alarm.loop = false;
    }
}

// Notification and appointment actions
function markNotificationRead(notificationId) {
    if (confirm('Mark this notification as read?')) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'mark_notification_read',
                id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(); // Refresh drawer
                    // Check if any emergencies remain
                    checkEmergencies();
                } else {
                    alert('Error marking as read: ' + response.message);
                }
            },
            error: function() {
                alert('Error marking as read');
            }
        });
    }
}

function acceptAppointment(appointmentId) {
    if (confirm('Accept this appointment?')) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'accept_appointment',
                id: appointmentId
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(); // Refresh drawer
                    location.reload(); // Refresh main page
                } else {
                    alert('Error accepting appointment: ' + response.message);
                }
            },
            error: function() {
                alert('Error accepting appointment');
            }
        });
    }
}

function rejectAppointment(appointmentId) {
    if (confirm('Reject this appointment?')) {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'reject_appointment',
                id: appointmentId
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(); // Refresh drawer
                    location.reload(); // Refresh main page
                } else {
                    alert('Error rejecting appointment: ' + response.message);
                }
            },
            error: function() {
                alert('Error rejecting appointment');
            }
        });
    }
}

function rescheduleAppointment(appointmentId) {
    const newTime = prompt('Enter new appointment time (YYYY-MM-DD HH:MM):');
    if (newTime && newTime.trim() !== '') {
        $.ajax({
            url: 'nurse.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'reschedule_appointment',
                id: appointmentId,
                new_time: newTime.trim()
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications(); // Refresh drawer
                    location.reload(); // Refresh main page
                } else {
                    alert('Error rescheduling appointment: ' + response.message);
                }
            },
            error: function() {
                alert('Error rescheduling appointment');
            }
        });
    }
}
</script>

</body>
</html>
