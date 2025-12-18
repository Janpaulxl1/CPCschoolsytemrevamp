<?php
session_start();
header('Content-Type: application/json');

// Show all errors (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

// --- Step 1. Check if session exists ---
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    echo json_encode([
        "unread_count" => 0,
        "notifications" => [],
        "debug" => "No student_id in session"
    ]);
    exit;
}

// --- Step 2. Get numeric ID of student (fallback to student_number if not in DB) ---
$student_numeric_id = null;
$stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $student = $res->fetch_assoc();
    $stmt->close();
    if ($student) {
        $student_numeric_id = (int)$student['id'];
    }
}

// If student record not found, still allow lookup using the student_number itself
$student_number_as_int = is_numeric($student_id) ? (int)$student_id : 0;
if (!$student_numeric_id) {
    $student_numeric_id = $student_number_as_int;
}

// --- Step 3. Fetch notifications for this student ---
$stmt = $conn->prepare("SELECT id, message, created_at, is_read, appointment_id, reschedule_status
                        FROM student_notifications
                        WHERE student_id IN (?, ?)
                        ORDER BY created_at DESC
                        LIMIT 20");
if (!$stmt) {
    echo json_encode([
        "unread_count" => 0,
        "notifications" => [],
        "debug" => "Prepare failed on notifications: " . $conn->error
    ]);
    exit;
}
$stmt->bind_param("ii", $student_numeric_id, $student_number_as_int);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // ✅ Force numeric values
    $row['id'] = (int)$row['id'];
    $row['is_read'] = (int)$row['is_read'];
    $notifications[] = $row;
}
$stmt->close();

// --- Step 4. Count unread ---
$unread_count = 0;
foreach ($notifications as $n) {
    if ($n['is_read'] === 0) {
        $unread_count++;
    }
}

// --- Step 5. Send JSON response ---
echo json_encode([
    "unread_count" => $unread_count,
    "notifications" => $notifications
]);

$conn->close();
?>