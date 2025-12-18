<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $expiration_date = $_POST['expiration_date'] ?? '';

    if ($id <= 0) {
        header('Location: medication_dashboard.php?error=invalid_id');
        exit;
    }

    if ($conn) {
        $sql = "UPDATE medications SET name = ?, dosage = ?, instructions = ?, quantity = ?, expiration_date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisi", $name, $dosage, $instructions, $quantity, $expiration_date, $id);

        if ($stmt->execute()) {
            header('Location: medication_dashboard.php?success=updated');
        } else {
            header('Location: medication_dashboard.php?error=update_failed');
        }
        $stmt->close();
    } else {
        header('Location: medication_dashboard.php?error=db_connection');
    }
    exit;
}
?>
