<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry_id = intval($_POST['id'] ?? 0);

    if ($entry_id <= 0) {
        // Redirect back with error
        header('Location: medication_dashboard.php?error=invalid_id');
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM medications WHERE id = ?");
    $stmt->bind_param("i", $entry_id);

    if ($stmt->execute()) {
        // Redirect back with success
        header('Location: medication_dashboard.php?success=deleted');
    } else {
        // Redirect back with error
        header('Location: medication_dashboard.php?error=delete_failed');
    }
    $stmt->close();
    exit;
}
?>
