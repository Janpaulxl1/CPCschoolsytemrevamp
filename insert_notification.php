<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $user_id = $_POST['user_id']; // Or get from param

    if ($user_id === 'nurse_id') {
        // Get nurse user_id
        $sql = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'nurse' LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];
        } else {
            echo json_encode(['success' => false]);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
    $stmt->bind_param("is", $user_id, $message);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false]);
}
?>