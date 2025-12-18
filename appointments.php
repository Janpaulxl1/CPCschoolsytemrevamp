<?php
session_start();
require_once 'db.php'; 

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die("❌ Please log in first.");
}

$student_id = $_SESSION['student_id'];

$message = "";
$nurse_status = "Offline";

// Get nurse status
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_time = $_POST['datetime'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($appointment_time) || empty($reason)) {
        $message = "<div class='alert error'>⚠ All fields are required.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments 
            (student_id, appointment_time, reason, status, is_emergency) 
            VALUES (?, ?, ?, 'Pending', 0)");
        $stmt->bind_param("iss", $student_id, $appointment_time, $reason);

        if ($stmt->execute()) {
            $new_appt_id = $stmt->insert_id;
            // Insert notification for nurse
            $nurse_query = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'nurse' LIMIT 1";
            $nurse_result = $conn->query($nurse_query);
            if ($nurse_result && $nurse_result->num_rows > 0) {
                $nurse = $nurse_result->fetch_assoc();
                $nurse_id = $nurse['id'];
                $notification_message = "New appointment #{$new_appt_id} booked by student for " . date('Y-m-d H:i', strtotime($appointment_time)) . ". Reason: " . $reason;
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $notif_stmt->bind_param("is", $nurse_id, $notification_message);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
            $_SESSION['appointment_time'] = $appointment_time;
            header("Location: success_appointment.php");
            exit();
        } else {
            $message = "<div class='alert error'>❌ Database error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CPC Clinic</title>
<link rel="icon" type="image" href="images/favicon.jpg">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(to bottom, #f7f9faff, #eaebecff);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: white;
}   
.container {
    background:linear-gradient(to bottom,#bb1d1d,#ddd1d1);
    padding: 25px;
    border-radius: 16px;
    width: 95%;
    max-width: 800px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    position: relative;
    box-sizing: border-box;
}
.back-btn {
    position: absolute;
    top: 15px;
    left: 15px;
    color: white;
    text-decoration: none;
    font-size: 20px;
    font-weight: bold;
}
.container img {
    max-width: 70%;
    height: auto;
    margin-bottom: 12px;
}
h2 {
    margin-bottom: 15px;
    font-size: 20px;
    text-transform: uppercase;
}
.status {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    color: black;
    font-size: 14px;
    padding: 8px;
    border-radius: 8px;
    margin: 10px 0 20px;
}
.status img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    margin-right: 5px;
}
.status span {
    color: green;
    margin-left: 6px;
    font-weight: bold;
}
input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    box-sizing: border-box;
}
button {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    cursor: pointer;
    font-weight: bold;
    display: block;
}
button.submit {
    background: #007BFF;
    color: white;
}
button.cancel {
    background: red;
    color: white;
}
.alert {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
    font-size: 14px;
    text-align: center;
}
.alert.error {
    background: #ffdddd;
    color: #d8000c;
    border: 1px solid #d8000c;
}
/* MOBILE */
@media screen and (max-width: 480px) {
    .container { padding: 18px; border-radius: 12px; }
    .back-btn { font-size: 18px; top: 10px; left: 10px; }
    h2 { font-size: 18px; }
    input, button { font-size: 14px; padding: 10px; }
    .status { font-size: 13px; }
    .status img { width: 24px; height: 24px; }
}
/* Larger Screens */
@media screen and (min-width: 600px) {
    .container { max-width: 420px; }
}
</style>
</head>
<body>
<div class="container">
    <a href="student_profile.php" class="back-btn">←</a>
    <img src="images/logo.png" alt="School Logo">
    <h2>APPOINTMENT BOOKING:</h2>
    <div class="status">
        <img src="images/nurse.jpg" alt="Nurse">
        Nurse status: <span><?php echo $nurse_status; ?></span>
    </div>

    <?php if (!empty($message)) echo $message; ?>

    <form method="POST" id="appointmentForm">
        <input type="datetime-local" id="datetime" name="datetime" required>
        <input type="text" name="reason" placeholder="Reason" required>
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
            <button type="submit" class="submit">Submit request</button>
            <button type="reset" class="cancel">Cancel</button>
        </div>
    </form>
</div>

<script>
// Prevent selecting past dates
const datetimeInput = document.getElementById('datetime');
const now = new Date();
datetimeInput.min = now.toISOString().slice(0,16);

// Optional: set default value to now
datetimeInput.value = now.toISOString().slice(0,16);
</script>
</body>
</html>
