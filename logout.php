<?php
session_start();
require_once 'db.php'; // Added to define $conn

// Update responder status to 'Off Duty' if logged out and was a responder
if (isset($_SESSION['role']) && $_SESSION['role'] === 'responder' && isset($_SESSION['username'])) {
    $responder_name = $_SESSION['username'];
    $update_sql = "UPDATE emergency_responders SET status = 'Off Duty', last_active = NULL WHERE name = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("s", $responder_name);
    $stmt->execute();
    $stmt->close();
}

// Set last_active to NULL and last_logout to NOW for nurses on logout
if (isset($_SESSION['user_id'])) {
    $update_sql = "UPDATE users SET last_active = NULL, last_logout = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

session_destroy();
header('Location: index.html');
exit();

