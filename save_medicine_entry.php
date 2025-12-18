<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? '';
$medicine_name = trim($_POST['medicine_name'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$quantity = $_POST['quantity'] ?? '';
$action_taken = trim($_POST['action_taken'] ?? '');

if (empty($appointment_id) || empty($medicine_name) || empty($quantity)) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID, Medicine Name, and Quantity are required']);
    exit;
}

if (!is_numeric($quantity) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be a positive number']);
    exit;
}

$quantity = (int)$quantity;

// Check if appointment exists
$stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}
$stmt->close();

try {
    $stmt = $conn->prepare("INSERT INTO appointment_medications (appointment_id, medicine_name, dosage, quantity, action_taken, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issis", $appointment_id, $medicine_name, $dosage, $quantity, $action_taken);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        // Subtract quantity from medications table
        $stmt = $conn->prepare("UPDATE medications SET quantity = quantity - ? WHERE name = ?");
        $stmt->bind_param("is", $quantity, $medicine_name);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save medicine entry']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
