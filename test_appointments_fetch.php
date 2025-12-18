<?php
session_start();
require_once "db.php";

// Set a test student_id from the image
$_SESSION['student_id'] = '2022061';

// Fetch student info
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

echo "Student found: " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['id'] . ")\n";

// Fetch appointments
$stmt = $conn->prepare("SELECT a.id, a.appointment_time, s.student_id, a.reason, a.status,
                              am.medicine_name, am.dosage, am.quantity, am.action_taken, am.created_at
                        FROM appointments a
                        LEFT JOIN appointment_medications am ON a.id = am.appointment_id
                        JOIN students s ON a.student_id = s.id
                        WHERE a.student_id = ?
                        ORDER BY a.appointment_time DESC, am.created_at DESC");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$medical_history = $stmt->get_result();

echo "Number of appointments fetched: " . $medical_history->num_rows . "\n";

if ($medical_history->num_rows > 0) {
    echo "Appointments:\n";
    while ($row = $medical_history->fetch_assoc()) {
        echo "- ID: " . $row['id'] . ", Time: " . $row['appointment_time'] . ", Reason: " . $row['reason'] . ", Status: " . $row['status'] . "\n";
        if ($row['medicine_name']) {
            echo "  Medicine: " . $row['medicine_name'] . " (" . $row['dosage'] . ", Qty: " . $row['quantity'] . ")\n";
        }
    }
} else {
    echo "No appointments found for this student.\n";
}

$conn->close();
?>
