<?php
// Include database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_or_id = $_POST['email'];

    // Check if email or ID exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR id = ?");
    $stmt->bind_param("ss", $email_or_id, $email_or_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, send reset link (for simplicity, just redirect to success page)
        // In a real application, you'd generate a token and send an email
        header("Location: reset_success.php");
        exit();
    } else {
        // User not found
        header("Location: reset_password.php?error=User not found");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
