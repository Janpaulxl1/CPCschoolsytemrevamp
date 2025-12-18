<?php
require 'db.php';

$student_id_param = (int)($_GET['student_id'] ?? 0);

if ($student_id_param) {
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, student_id FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id_param);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student) {
        die("Student not found.");
    }

    $student_db_id = $student['id'];
    $student_number = $student['student_id'];

    $stmt2 = $conn->prepare("
      SELECT DATE(a.appointment_time) as date, TIME(a.appointment_time) as time, a.reason, a.status, CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) AS student_name,
             am.medicine_name, am.dosage, am.quantity, am.action_taken
      FROM appointments a
      LEFT JOIN students s ON a.student_id = s.student_id
      LEFT JOIN appointment_medications am ON a.id = am.appointment_id
      WHERE a.student_id = ? AND a.status IN ('Confirmed', 'Completed', 'Declined')
      ORDER BY a.appointment_time DESC
    ");
    $stmt2->bind_param("s", $student_number);
    $stmt2->execute();
    $result = $stmt2->get_result();
} else {
    // Show all appointment logs if no student_id provided
    $stmt2 = $conn->prepare("
      SELECT DATE(a.appointment_time) as date, TIME(a.appointment_time) as time, a.reason, a.status, CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) AS student_name,
             am.medicine_name, am.dosage, am.quantity, am.action_taken
      FROM appointments a
      LEFT JOIN students s ON a.student_id = s.student_id
      LEFT JOIN appointment_medications am ON a.id = am.appointment_id
      WHERE a.status IN ('Confirmed', 'Completed', 'Declined')
      ORDER BY a.appointment_time DESC
    ");
    $stmt2->execute();
    $result = $stmt2->get_result();
    $student = null; // No specific student
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointment History</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href='index.main.min.css' rel='stylesheet' />
  <script src='index.main.min.js'></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    .fc-day-today { background: orange !important; font-weight: bold; border-radius: 4px; }
    .fc-event.appointment { background-color: #007bff !important; border-color: #007bff !important; color: white !important; font-weight: bold !important; }
    #calendar { min-height: 500px; }
    .fc-event { font-size: 12px; line-height: 1.2; padding: 2px 4px; max-width: 100%; }
    .fc-event-title { font-size: 12px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 100%; }
    .fc-event-time { white-space: nowrap; max-width: 30px; overflow: hidden; display: inline-block; vertical-align: middle; }
    #skipBtn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    /* Professional styling with white background and #b71c1c */
    body {
      background: #ffffff;
      min-height: 100vh;
      font-family: 'Inter', sans-serif;
    }
    header {
      background: #b71c1c !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      width: 100vw;
      position: relative;
      left: 50%;
      right: 50%;
      margin-left: -50vw;
      margin-right: -50vw;
      overflow: visible;
    }
    header img {
      position: absolute;
      top: -10px;
      right: 20px;
      height: 80px;
      z-index: 10;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    #mainShell {
      max-width: 1800px;
      margin: 0 auto;
      padding: 20px;
    }
    #appointmentCard {
      background: #ffffff;
      border: 1px solid #b71c1c;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(183, 28, 28, 0.1);
      transition: all 0.3s ease;
      max-width: none;
      width: 100%;
    }
    #appointmentCard:hover {
      box-shadow: 0 8px 24px rgba(183, 28, 28, 0.15);
    }
    #appointmentCard h3 {
      color: #1a1a1a;
      font-size: 22px;
      font-weight: 700;
    }
    #appointmentCard table {
      border-collapse: collapse;
      width: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      font-size: 16px;
    }
    #appointmentCard thead th {
      background: #b71c1c;
      color: #ffffff;
      font-weight: 500;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      border: 1px solid #b71c1c;
      text-align: left;
      padding: 12px 16px;
    }
    #appointmentCard tbody td {
      border: 1px solid #e5e7eb;
      text-align: left;
      padding: 12px 16px;
      font-size: 14px;
      color: #374151;
    }
    #appointmentCard tbody tr:nth-child(even) {
      background: #f9fafb;
    }
    #appointmentCard .mark-done-btn {
      background: #10b981;
      color: #fff;
      padding: 8px 12px;
      font-size: 12px;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    #appointmentCard .mark-done-btn:hover {
      background: #059669;
    }
    #appointmentCard .view-details-btn {
      background: #f59e0b;
      color: #fff;
      padding: 8px 12px;
      font-size: 12px;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    #appointmentCard .view-details-btn:hover {
      background: #d97706;
    }
    /* Calendar card */
    #calendar {
      border: 1px solid #e5e7eb;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      border-radius: 12px;
      transition: all 0.3s ease;
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      overflow: hidden;
      max-width: 400px;
      width: 100%;
    }
    #calendar:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }
    /* Custom calendar styling */
    .fc-theme-standard .fc-scrollgrid {
      border: none;
    }
    .fc-theme-standard th {
      background: #b71c1c !important;
      color: white !important;
      font-weight: 600;
      border: none !important;
    }
    .fc-theme-standard td {
      border: 1px solid #e5e7eb !important;
    }
    .fc-day-today {
      background: rgba(183, 28, 28, 0.1) !important;
      border-radius: 8px;
    }
    .fc-event {
      border-radius: 6px;
      border: none !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .fc-button {
      background: #b71c1c !important;
      border: none !important;
      border-radius: 6px !important;
      transition: all 0.3s ease;
    }
    .fc-button:hover {
      background: #a01717 !important;
      transform: translateY(-1px);
    }
    .fc-toolbar-title {
      color: #b71c1c;
      font-weight: 700;
      font-size: 1.5rem;
    }
    /* Nurse profile card */
    #nurseProfileCard {
      background: #ffffff;
      border: 1px solid #b71c1c;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(183, 28, 28, 0.1);
      transition: all 0.3s ease;
    }
    #nurseProfileCard:hover {
      box-shadow: 0 8px 24px rgba(183, 28, 28, 0.15);
    }
    #nurseProfileCard h2 {
      color: #1f2937;
      font-weight: 700;
    }
    #nurseProfileCard .profile-avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      border: 4px solid #b71c1c;
      box-shadow: 0 4px 12px rgba(183, 28, 28, 0.2);
      object-fit: cover;
      background: #f8fafc;
    }
    /* Sidebar dropdown menu */
    #sidebar {
      position: absolute;
      top: 100%;
      left: 0;
      width: 280px;
      background: #ffffff;
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
      transform: translateX(-100%) translateY(-10px);
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      z-index: 1000;
      padding: 20px;
      border-radius: 0 0 12px 12px;
      border: 1px solid #e5e7eb;
      border-top: none;
      max-height: 80vh;
      overflow-y: auto;
    }
    #sidebar.open {
      transform: translateX(0) translateY(0);
      opacity: 1;
    }
    #sidebar h2 {
      color: #007bff;
      font-weight: 700;
      margin-bottom: 20px;
    }
    #sidebar a {
      display: block;
      padding: 12px 16px;
      margin-bottom: 8px;
      color: #374151;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    #sidebar a:hover {
      background: #007bff;
      color: black;
    }
    #sidebar summary {
      color: black;
    }
    #sidebar summary:hover {
      background: #007bff;
      color: black;
      font-weight: bold;
    }
    /* Overlay for sidebar */
    #sidebarOverlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.5);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 999;
    }
    #sidebarOverlay.open {
      opacity: 1;
      visibility: visible;
    }
  </style>
</head>
<body>
  <header>
    <div class="flex items-center justify-between px-6 py-4">
      <div class="flex items-center space-x-4">
        <h1 class="text-white text-xl font-bold">Appointment History</h1>
      </div>
      <img src="images/logo.png" alt="Logo" class="h-10">
    </div>
  </header>
  <div id="mainShell">
    <div id="appointmentCard" class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h3>Appointment History</h3>
        <a href="studentfile_dashboard.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">⬅ Back</a>
      </div>
      <?php if ($student): ?>
      <p class="mb-4 text-gray-700">
        Student: <span class="font-semibold">
          <?= htmlspecialchars($student['first_name'] . " " . $student['middle_name'] . " " . $student['last_name']) ?>
        </span> (<?= htmlspecialchars($student['student_id']) ?>)
      </p>
      <?php endif; ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <?php if (!$student): ?>
            <th>Student Name</th>
            <?php endif; ?>
            <th>Reason</th>
            <th>Status</th>
            <th>Medicine Name</th>
            <th>Dosage</th>
            <th>Quantity</th>
            <th>Action Taken</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['time']) ?></td>
                <?php if (!$student): ?>
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <?php endif; ?>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['medicine_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['dosage'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['quantity'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['action_taken'] ?? 'N/A') ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="<?= $student ? 9 : 10 ?>" class="text-center py-4">No appointments found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>


</body>
</html>
