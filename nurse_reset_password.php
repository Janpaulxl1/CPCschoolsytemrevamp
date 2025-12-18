<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    header("Location: login.html");
    exit();
}
require_once 'db.php';

// Fetch all students for dropdown
$students = [];
$sql = "SELECT id, student_id, first_name, last_name FROM students ORDER BY first_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nurse Dashboard - Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="images/favicon.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Poltawski+Nowy:wght@400;700&display=swap" rel="stylesheet">

  <style>
    /* =========================
       UNSCROLLABLE ON DESKTOP ONLY
       ========================= */
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: auto; /* default scroll enabled for mobile */
    }

    /* Desktop: remove scroll */
    @media (min-width: 769px) {
      html, body {
        overflow: hidden; /* unscrollable on desktop */
      }
    }

    body {
      font-family: 'Poltawski Nowy', serif;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1.5rem;
      position: relative;
      color: white;
      background: white;
    }

    /* BACKGROUND SHAPES */
    .bg-white-tilt {
      position: absolute;
      top: -50%;
      left: -50%;
      width: 170%;
      height: 160%;
      background: #ffffff;
      transform: rotate(25deg);
      z-index: -3;
    }
    .bg-white-tilt-bottom {
      position: absolute;
      bottom: -50%;
      right: -50%;
      width: 170%;
      height: 160%;
      background: #ffffff;
      transform: rotate(25deg);
      z-index: -3;
    }
    .bg-red-tilt-middle {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 170%;
      height: 130%;
      background: #b71c1c;
      transform: translate(-50%, -50%) rotate(-25deg);
      z-index: -2;
    }
    .bg-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.15);
      z-index: -1;
    }

    @keyframes fadeUp {
      0% { opacity: 0; transform: translateY(25px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes shake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      50% { transform: translateX(4px); }
      75% { transform: translateX(-4px); }
      100% { transform: translateX(0); }
    }
    .shake { animation: shake 0.25s linear; }
    .animated { animation: fadeUp 0.7s ease-out forwards; }
    .animated-delay-1 { animation-delay: 0.1s; }
    .animated-delay-2 { animation-delay: 0.25s; }
    .animated-delay-3 { animation-delay: 0.45s; }

    .main-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      z-index: 5;
    }

    .content {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      gap: 2.5rem;
      flex-wrap: wrap;
      max-width: 1100px;
      width: 100%;
    }

    .logo-area { text-align: center; opacity: 0; }
    .logo-area img { width: 260px; height: auto; margin-bottom: 1rem; }

    .logo-text p {
      font-size: 2.8rem;
      line-height: 1.3;
      font-weight: bold;
      margin: 0;
    }

    .reset-card {
      background-color: rgba(255, 255, 255, 0.18);
      border-radius: 1rem;
      padding: 3rem 2.7rem;
      box-shadow: 0 6px 14px rgba(0,0,0,0.35);
      backdrop-filter: blur(12px);
      width: 100%;
      max-width: 480px;
      min-height: 460px;
      opacity: 0;
      border: 1px solid rgba(255,255,255,0.3);
    }

    .reset-card h3 {
      font-size: 1.95rem;
      font-weight: bold;
      margin-bottom: 1.5rem;
      text-align: center;
      color: white;
    }

    .form-group { margin-bottom: 1.2rem; width: 100%; }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: #f8f8f8;
      font-size: 1rem;
    }
    .form-group input {
      width: 100%;
      padding: 0.9rem;
      border: none;
      border-radius: 0.5rem;
      color: #333;
      font-size: 1rem;
      background: #f7f7f7;
    }

    .reset-button {
      background-color: #fff;
      color: #b71c1c;
      width: 100%;
      padding: 1rem;
      border: none;
      border-radius: 0.5rem;
      font-weight: bold;
      cursor: pointer;
      font-size: 1.1rem;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
    }
    .reset-button:hover {
      background-color: #f2f2f2;
      transform: translateY(-1px);
    }

    .spinner {
      width: 18px;
      height: 18px;
      border: 3px solid rgba(0,0,0,0.3);
      border-top-color: #000;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
      .content { flex-direction: column; text-align: center; }
      .reset-card { max-width: 90%; }
    }

  </style>

  <script>
    window.onload = () => {
      document.querySelector('.logo-area').classList.add('animated', 'animated-delay-1');
      document.querySelector('.logo-text').classList.add('animated', 'animated-delay-2');
      document.querySelector('.reset-card').classList.add('animated', 'animated-delay-3');
    };

    function handleReset(e) {
      const studentId = document.getElementById("student_id");
      const newPassword = document.getElementById("new_password");
      const confirmPassword = document.getElementById("confirm_password");
      const btn = document.getElementById("resetBtn");

      if (studentId.value.trim() === "" || newPassword.value.trim() === "" || confirmPassword.value.trim() === "") {
        studentId.classList.add("shake");
        newPassword.classList.add("shake");
        confirmPassword.classList.add("shake");
        setTimeout(() => {
          studentId.classList.remove("shake");
          newPassword.classList.remove("shake");
          confirmPassword.classList.remove("shake");
        }, 300);
        e.preventDefault();
        return false;
      }

      if (newPassword.value !== confirmPassword.value) {
        alert("New passwords do not match!");
        e.preventDefault();
        return false;
      }

      btn.innerHTML = '<div class="spinner"></div> Resetting...';
      btn.disabled = true;
    }
  </script>

</head>
<body>

  <!-- BACKGROUND SHAPES -->
  <div class="bg-white-tilt"></div>
  <div class="bg-white-tilt-bottom"></div>
  <div class="bg-red-tilt-middle"></div>
  <div class="bg-overlay"></div>

  <!-- CONTENT -->
  <div class="main-wrapper">
    <div class="content">

      <div class="logo-area">
        <img src="images/logo.png" alt="School Clinic Logo">
        <div class="logo-text">
          <p>School Health</p>
          <p>Clinic</p>
          <p>Management</p>
          <p>System</p>
        </div>
      </div>

      <div class="reset-card">
        <h3>RESET STUDENT PASSWORD</h3>

        <form action="nurse_reset_process.php" method="POST" onsubmit="handleReset(event)">
          <div class="form-group">
            <label for="student_id">Search Student</label>
            <input type="text" id="student_id" name="student_id" list="students" placeholder="Type student name or ID" required autocomplete="off">
            <datalist id="students">
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo htmlspecialchars($student['student_id']); ?>" label="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>">
                <?php endforeach; ?>
            </datalist>
          </div>

          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
          </div>

          <button id="resetBtn" type="submit" class="reset-button">Reset Password</button>
        </form>

        <a href="nurse.php" class="text-center block mt-4 text-white underline">Back to Dashboard</a>
      </div>
    </div>
  </div>

</body>
</html>
