<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    header("Location: login.html");
    exit();
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($student_id) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required.'); window.location.href='nurse_reset_password.php';</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.location.href='nurse_reset_password.php';</script>";
        exit();
    }

    // Check if student exists
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if ($student) {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in students table
        $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
        $update_stmt->bind_param("ss", $hashed_password, $student_id);

        if ($update_stmt->execute()) {
            echo "<script>alert('Password reset successfully.'); window.location.href='nurse.php';</script>";
        } else {
            echo "<script>alert('Failed to reset password.'); window.location.href='nurse_reset_password.php';</script>";
        }
        $update_stmt->close();
    } else {
        echo "<script>alert('Student not found.'); window.location.href='nurse_reset_password.php';</script>";
    }
} else {
    header("Location: nurse_reset_password.php");
    exit();
}
$conn->close();
?>
