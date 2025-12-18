<?php
session_start();
require_once 'db.php';

$action = $_POST['action'] ?? 'update';

$student_id = (int)($_POST['student_id'] ?? 0);
$student_name = $_POST['student_name'] ?? '';
$course = $_POST['course'] ?? '';
$reason = $_POST['reason'] ?? '';
$action_taken = $_POST['action_taken'] ?? '';
$medicine_name = $_POST['medicine_name'] ?? '';
$dosage = $_POST['dosage'] ?? '';
$quantity = $_POST['quantity'] ?? '';
$visit_date = $_POST['visit_date'] ?? '';

// Convert visit_date to MySQL datetime format if set
if (!empty($visit_date)) {
    $visit_date = date('Y-m-d H:i:s', strtotime($visit_date));
}

// Deduct medicine quantity if medicine is selected and quantity is provided
if (!empty($medicine_name) && !empty($quantity) && is_numeric($quantity)) {
    $stmt_check = $conn->prepare("SELECT quantity FROM medications WHERE name = ?");
    $stmt_check->bind_param("s", $medicine_name);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $med_row = $result_check->fetch_assoc();
        $current_qty = $med_row['quantity'];
        if ($current_qty >= $quantity) {
            $new_qty = $current_qty - $quantity;
            $stmt_update_med = $conn->prepare("UPDATE medications SET quantity = ? WHERE name = ?");
            $stmt_update_med->bind_param("is", $new_qty, $medicine_name);
            $stmt_update_med->execute();
        } else {
            // Not enough medicine, but still allow the log (you can add error handling here if needed)
        }
    }
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    // Update existing record
    $stmt = $conn->prepare("UPDATE student_visits SET student_name=?, course=?, reason=?, action_taken=?, med_id=?, dosage=?, quantity=?, visit_date=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $student_name, $course, $reason, $action_taken, $medicine_name, $dosage, $quantity, $visit_date, $id);
    $stmt->execute();
} else {
    // Get the next available id to prevent duplicate primary key error
    $result = $conn->query("SELECT MAX(id) as max_id FROM student_visits");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    // Insert new record with explicit id
    $stmt = $conn->prepare("INSERT INTO student_visits (id, student_name, course, reason, action_taken, med_id, dosage, quantity, visit_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("issssssis", $next_id, $student_name, $course, $reason, $action_taken, $medicine_name, $dosage, $quantity, $visit_date);
    $stmt->execute();
}

header("Location: Student_visitlogs.php");
exit;
?>
