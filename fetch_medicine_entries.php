<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_GET['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
    exit;
}

$appointment_id = intval($_GET['appointment_id']);

$stmt = $conn->prepare("SELECT id, medicine_name, dosage, quantity, action_taken, created_at FROM appointment_medications WHERE appointment_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode(['success' => true, 'entries' => $entries]);

$stmt->close();
$conn->close();
?>
