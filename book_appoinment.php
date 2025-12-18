<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['student_id'])) {
    die("❌ Please log in as a student first.");
}

$student_id_string = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id_string);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("❌ Student not found.");
}
$student = $result->fetch_assoc();
$student_id_int = $student['id'];

$nurse_status = "Offline";
$sql_nurse = "SELECT u.name, last_active, last_logout
              FROM users u
              JOIN roles r ON u.role_id = r.id
              WHERE r.name = 'nurse'
              ORDER BY last_active DESC
              LIMIT 1";
$result_nurse = $conn->query($sql_nurse);

if ($result_nurse && $result_nurse->num_rows > 0) {
    $nurse = $result_nurse->fetch_assoc();
    if ($nurse['last_logout'] == NULL || $nurse['last_logout'] == '' || $nurse['last_logout'] == "0000-00-00 00:00:00") {
        $nurse_status = "Online";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE appointment_time = ?");
    $stmt->bind_param("s", $appointment_time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $status = ($result['cnt'] > 0) ? "Conflict" : "Pending";

    $stmt = $conn->prepare("INSERT INTO appointments (appointment_time, reason, status, student_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $appointment_time, $reason, $status, $student_id_int);
    $stmt->execute();
    $stmt->close();

    echo "success";
}
if ($conn) $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Appointment</title>
  <link href="index.main.min.css" rel="stylesheet" />
  <script src="index.main.min.js"></script>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 20px; }
    .status { margin-top: 20px; background: #f5f5f5; padding: 10px; border-radius: 6px; }
    #calendar { max-width: 900px; margin: 20px auto; }
    input, button {
      width: 100%;
      box-sizing: border-box;
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      background-color: #4CAF50;
      color: white;
      cursor: pointer;
    }
    button:hover {
      background-color: #45a049;
    }
    @media (min-width: 601px) {
      input, button { width: auto; max-width: 300px; }
    }
  </style>
</head>
<body>
  <h2>Book Appointment</h2>
  <form method="POST">
    <input type="datetime-local" name="appointment_time" required><br><br>
    <input type="text" name="reason" placeholder="Reason" required><br><br>
    <button type="submit">Book</button>
  </form>

  <div class="status">
    Nurse status: <strong><?php echo $nurse_status; ?></strong>
  </div>

  <h3>Calendar</h3>
  <div id='calendar'></div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      const calendarEl = document.getElementById('calendar');
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 650,
        selectable: true,
        select: function(info) {
          const selectedDate = new Date(info.start);
          selectedDate.setHours(0,0,0,0);
          if (selectedDate < today) {
            alert('❌ Cannot select past dates!');
            calendar.unselect();
          }
        },
        dayCellDidMount: function(info) {
          const cellDate = new Date(info.date);
          cellDate.setHours(0, 0, 0, 0);
          if (cellDate < today) {
            info.el.style.backgroundColor = '#f0f0f0';
            info.el.style.color = '#ccc';
            info.el.style.pointerEvents = 'none';
          }
        }
      });
      calendar.render();
    });
  </script>
</body>
</html>
