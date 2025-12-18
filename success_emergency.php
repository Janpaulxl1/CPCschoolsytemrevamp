<?php
session_start();
if (!isset($_SESSION['emergency_success'])) {
    header("Location: emergency.php");
    exit();
}
unset($_SESSION['emergency_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Emergency Sent</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .alert {
      background: #ffdddd;
      border: 1px solid #d8000c;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      max-width: 400px;
    }
    .alert.success {
      background: #ffdddd;
      border: 2px solid green;
      color: black;
    }
    .alert.success h3 {
      color: green;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="alert success">
    <h3>✅ Emergency report sent successfully!</h3>
    <p>Your emergency report was sent. Please stay where you are—help is on the way.</p>
  </div>
</body>
</html>