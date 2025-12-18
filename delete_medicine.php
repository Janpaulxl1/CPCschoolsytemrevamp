<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $entry_id = intval($_POST['id'] ?? 0);

    if ($entry_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM appointment_medications WHERE id = ?");
    $stmt->bind_param("i", $entry_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medicine entry deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete medicine entry']);
    }
    $stmt->close();
    exit;
}
?>
