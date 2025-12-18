<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$notifId = $_POST['notifId'] ?? null;
$message = $_POST['message'] ?? null;

if (!$notifId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Parse student_id from message
// Message format: "Emergency: ... Student: Name (ID: 123) ..."
preg_match('/Student: ([^(]+) \(ID: (\d+)\)/', $message, $matches);
if (count($matches) < 3) {
    echo json_encode(['success' => false, 'message' => 'Could not parse student ID']);
    exit;
}
$student_id = $matches[2];

// Get responder name from session
$responder_name = $_SESSION['username'] ?? null;
if (!$responder_name) {
    echo json_encode(['success' => false, 'message' => 'Responder not logged in']);
    exit;
}

// Get nurse user_id
$sql_nurse_id = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'nurse' LIMIT 1";
$result_nurse_id = $conn->query($sql_nurse_id);
if (!$result_nurse_id || $result_nurse_id->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Nurse not found']);
    exit;
}
$nurse_row = $result_nurse_id->fetch_assoc();
$nurse_id = $nurse_row['id'];

// Insert notification to nurse
$notif_message_nurse = "Responder $responder_name is on the way for emergency: $message";
$stmt_nurse = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
$stmt_nurse->bind_param("is", $nurse_id, $notif_message_nurse);
$nurse_success = $stmt_nurse->execute();
$stmt_nurse->close();

// Insert notification to student
$notif_message_student = "Responder $responder_name is on the way for your reported emergency: $message";
$stmt_student = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
$stmt_student->bind_param("is", $student_id, $notif_message_student);
$student_success = $stmt_student->execute();
$stmt_student->close();

// Mark the original notification as read
$stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$stmt_mark_read->bind_param("i", $notifId);
$mark_read_success = $stmt_mark_read->execute();
$stmt_mark_read->close();

if ($nurse_success && $student_success && $mark_read_success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to insert notifications']);
}
?>
