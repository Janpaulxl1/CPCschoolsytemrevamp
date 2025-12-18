<?php
require_once 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        exit;
    }

    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND (message LIKE 'Low stock alert:%' OR message LIKE 'Expiration alert:%') AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo json_encode(['success' => $affected > 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>