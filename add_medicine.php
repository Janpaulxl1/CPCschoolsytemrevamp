<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['appointment_id'])) {
        // Add medicine to appointment_medications
        $appointment_id = intval($_POST['appointment_id']);
        $medicine_name = trim($_POST['medicine_name']);
        $dosage = trim($_POST['dosage']);
        $quantity = intval($_POST['quantity']);
        $action_taken = trim($_POST['action_taken']);

        $stmt = $conn->prepare("
            INSERT INTO appointment_medications (appointment_id, medicine_name, dosage, quantity, action_taken)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issis", $appointment_id, $medicine_name, $dosage, $quantity, $action_taken);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medicine added to appointment']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        // Original: Add to medications table
        $name = $_POST['name'];
        $dosage = $_POST['dosage'];
        $instructions = $_POST['instructions'];
        $quantity = $_POST['quantity'];
        $expiration_date = $_POST['expiration_date'];

        $stmt = $conn->prepare("
            INSERT INTO medications (name, dosage, instructions, quantity, expiration_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssds", $name, $dosage, $instructions, $quantity, $expiration_date);

        if ($stmt->execute()) {
            header("Location: medication_dashboard.php?success=1");
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>
