<?php
session_start();
$appointment_time = $_SESSION['appointment_time'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointment Success</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .success-box {
      border: 2px solid #4CAF50;
      background: #f6fff6;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      max-width: 350px;
      width: 90%;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .checkmark {
      font-size: 40px;
      color: #4CAF50;
      margin-bottom: 15px;
    }
    h3 {
      color: #4CAF50;
      margin-bottom: 10px;
    }
    p {
      color: #333;
      font-size: 15px;
    }
    .ok-btn {
      margin-top: 20px;
      padding: 10px 20px;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: background 0.3s;
    }
    .ok-btn:hover {
      background: #45a049;
    }
  </style>
  <script>
    
    setTimeout(function() {
      window.location.href = "student_profile.php"; 
    }, 5000);
  </script>
</head>
<body>
  <div class="success-box">
    <div class="checkmark">✔</div>
    <h3>Appointment booked successfully!</h3>
    <?php if ($appointment_time): ?>
      <p>Your appointment has been scheduled for<br>
      <b><?php echo date("F d, Y \a\\t h:i A", strtotime($appointment_time)); ?></b></p>
    <?php else: ?>
      <p>No appointment found.</p>
    <?php endif; ?>
    <button class="ok-btn" onclick="window.location.href='student_profile.php'">OK</button>
  </div>
</body>
</html>
