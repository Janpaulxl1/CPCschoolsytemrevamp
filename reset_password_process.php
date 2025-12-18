<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required.'); window.location.href='reset_password.php';</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.'); window.location.href='reset_password.php';</script>";
        exit();
    }

    // Check if student exists and old password matches
    $stmt = $conn->prepare("SELECT student_id, password FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if ($student) {
        if (password_verify($old_password, $student['password'])) {
            // Hash new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $update_stmt->bind_param("ss", $hashed_new_password, $student['student_id']);

            if ($update_stmt->execute()) {
                echo "<script>alert('Password reset successfully.'); window.location.href='index.html';</script>";
            } else {
                echo "<script>alert('Failed to reset password.'); window.location.href='reset_password.php';</script>";
            }
            $update_stmt->close();
        } else {
            echo "<script>alert('Old password is incorrect.'); window.location.href='reset_password.php';</script>";
        }
    } else {
        echo "<script>alert('Student not found.'); window.location.href='reset_password.php';</script>";
    }
} else {
    header("Location: reset_password.php");
    exit();
}
$conn->close();
?>
