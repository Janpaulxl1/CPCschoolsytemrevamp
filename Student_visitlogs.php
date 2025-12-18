          <?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
  header("Location: index.php");
  exit;
}
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'db.php';

// Fetch all logs with student details and medicine name, excluding completed visits
$logs = [];
$result = $conn->query("SELECT sv.*, m.name as medicine_name FROM student_visits sv LEFT JOIN medicines m ON sv.med_id = m.name WHERE sv.status != 'Completed' ORDER BY sv.id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Fetch medicines for dropdown
$medicines = [];
$result_med = $conn->query("SELECT name, dosage FROM medications ORDER BY name ASC");
if ($result_med) {
    while ($row = $result_med->fetch_assoc()) {
        $medicines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Health Clinic</title>
    <link rel="icon" type="image" href="images/favicon.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
    }
    #mainShell {
      max-width: 2000px;
      margin: 0 auto;
      padding: 40px;
      min-height: 90vh;
    }
    #visitLogsCard {
      background: #ffffff;
      border: 1px solid #b71c1c;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(183, 28, 28, 0.1);
      transition: all 0.3s ease;
      max-width: none;
      width: 100%;
    }
    #visitLogsCard:hover {
      box-shadow: 0 8px 24px rgba(183, 28, 28, 0.15);
    }
    #visitLogsCard h3 {
      color: #1a1a1a;
      font-size: 22px;
      font-weight: 700;
    }
    #visitLogsCard table {
      border-collapse: collapse;
      width: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      font-size: 20px;
    }
    #visitLogsCard thead th {
      background: #b71c1c;
      color: #ffffff;
      font-weight: 500;
      font-size: 17px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      border: 1px solid #b71c1c;
      text-align: left;
      padding: 20px 24px;
    }
    #visitLogsCard tbody td {
      border: 1px solid #e5e7eb;
      text-align: left;
      padding: 20px 24px;
      font-size: 18px;
      color: #374151;
    }
    #visitLogsCard tbody tr:nth-child(even) {
      background: #f9fafb;
    }
    #visitLogsCard .add-log-btn {
      background: #b71c1c;
      color: #fff;
      padding: 12px 24px;
      font-size: 14px;
      font-weight: 600;
      border: 2px solid #b71c1c;
      border-radius: 0;
      transition: all 0.3s ease;
      cursor: pointer;
      display: inline-block;
      text-decoration: none;
      box-shadow: 0 2px 4px rgba(183, 28, 28, 0.2);
    }
    #visitLogsCard .add-log-btn:hover {
      background: #a01717;
      border-color: #a01717;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(183, 28, 28, 0.3);
    }
    #visitLogsCard .edit-btn {
      background: #f59e0b;
      color: #fff;
      padding: 8px 12px;
      font-size: 12px;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    #visitLogsCard .edit-btn:hover {
      background: #d97706;
    }
    #visitLogsCard .done-btn {
      background: #10b981;
      color: #fff;
      padding: 8px 12px;
      font-size: 12px;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    #visitLogsCard .done-btn:hover {
      background: #059669;
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
      color: #b71c1c;
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
    #sidebar summary {
      color: black;
    }
    #sidebar a:hover {
      background: #b71c1c;
      color: white;
      font-weight: bold;
    }
    #sidebar a:last-child:hover {
      font-weight: normal;
    }
    #sidebar summary:hover {
      background: #b71c1c;
      color: white;
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
<body id="mainShell">

<!-- Header -->
<header class="p-4 text-white relative">
  <div class="flex items-center gap-4">
    <button id="menuBtn" class="text-2xl font-bold cursor-pointer hover:bg-white hover:bg-opacity-10 rounded p-2 transition">☰</button>
    <h1 class="text-2xl font-bold">Student Visit Logs</h1>
  </div>

  <!-- Sidebar -->
  <div id="sidebar">
    <a href="nurse.php" class="block mb-4 px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">← Back to Dashboard</a>
    <h2>File Clinic Explorer</h2>
    <details class="group mb-4">
      <summary class="cursor-pointer flex items-center hover:bg-gray-100 transition p-2 rounded">
        📁 Documents
      </summary>
      <ul class="ml-6 mt-2 space-y-1 text-[15px]">
        <li><a href="physical_assessment.php" class="block hover:bg-gray-100 rounded px-1">↳ Student Physical Assessment Form</a></li>
        <li><a href="health_service_report.php" class="block hover:bg-gray-100 rounded px-1">↳ Health Service Utilization Report</a></li>
        <li><a href="first_aid.php" class="block hover:bg-gray-100 rounded px-1">↳ First Aid Procedure</a></li>
        <li><a href="emergency_plan.html" class="block hover:bg-gray-100 rounded px-1">↳ Emergency Respond Plan</a></li>
      </ul>
    </details>
    <a href="medication_dashboard.php">📁 Medical Supplies</a>
    <a href="studentfile_dashboard.php">📁 Student File Dashboard</a>
    <a href="registration.php">📁 Register Student Health</a>
    <a href="Student_visitlogs.php">📁 Student Visit Logs</a>
    <a href="appointment_history.php">📁 Appointment History</a>
    <a href="emergency_reports.php">📁 Emergency Reports</a>
    <a href="responder_status.php">📁 Responder Status</a>
    <a href="nurse_reset_password.php">🔑 Reset User Password</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
</header>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay"></div>


<!-- Main Content -->
<main class="max-w-6xl mx-auto mt-6 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold text-center mb-4">Student Visit Logs</h2>

    <!-- Add Button -->
    <div class="flex justify-end mb-6">
        <button id="addNewVisitBtn" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
            <span class="font-semibold">+ Add New Visit</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-300">
            <thead class="bg-blue-200">
                <tr>
                    <th class="p-2 border">Name</th>
                    <th class="p-2 border">Course YR&Sec</th>
                    <th class="p-2 border">Reason</th>
                    <th class="p-2 border">Action taken</th>
                    <th class="p-2 border">Medicine</th>
                    <th class="p-2 border">Dosage</th>
                    <th class="p-2 border">Quantity</th>
                    <th class="p-2 border">Date/Time</th>
                    <th class="p-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr id="row-<?= $log['id'] ?>"
                            data-student-name="<?= htmlspecialchars($log['student_name']) ?>"
                            data-course="<?= htmlspecialchars($log['course']) ?>"
                            data-reason="<?= htmlspecialchars($log['reason']) ?>"
                            data-action-taken="<?= htmlspecialchars($log['action_taken']) ?>"
                            data-med-id="<?= htmlspecialchars($log['med_id']) ?>"
                            data-medicine-name="<?= htmlspecialchars($log['medicine_name'] ?? $log['med_id']) ?>"
                            data-dosage="<?= htmlspecialchars($log['dosage']) ?>"
                            data-quantity="<?= htmlspecialchars($log['quantity']) ?>"
                            data-visit-date="<?= date('Y-m-d\TH:i', strtotime($log['visit_date'])) ?>"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['student_name']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['course']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['reason']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['action_taken']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['medicine_name'] ?? $log['med_id']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['dosage']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= htmlspecialchars($log['quantity']) ?></td>
                            <td class="p-4 border border-gray-300 text-gray-800"><?= date('m/d/Y h:i A', strtotime($log['visit_date'])) ?></td>
                            <td class="p-4 border border-gray-300 text-center">
                                <button onclick="openModal(<?= $log['id'] ?>)" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition-colors mr-2">Edit</button>
                                <form action="mark_visit_done.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="visit_id" value="<?= $log['id'] ?>">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition-colors">Done</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center p-8 text-gray-500 bg-gray-50 italic">No visit logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


</main>

<!-- Add/Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
  <div class="bg-white p-6 rounded shadow-lg w-96 max-h-[80vh] overflow-y-auto">
    <h3 class="text-lg font-bold mb-4" id="modalTitle">Add/Edit Student Visit Log</h3>

    <form action="update.php" method="POST" class="space-y-4">
      <input type="hidden" name="id" id="edit_id">
      <input type="hidden" name="action" id="form_action" value="update">


      <label class="block text-sm font-semibold mb-1">Student Name
        <input type="text" name="student_name" id="edit_student_name" class="w-full border rounded p-2" required>
      </label>
      <label class="block text-sm font-semibold mb-1">Course YR&Sec
        <input type="text" name="course" id="edit_course_year_section" class="w-full border rounded p-2" required>
      </label>
      <label class="block text-sm font-semibold mb-1">Reason
        <input type="text" name="reason" id="edit_reason" class="w-full border rounded p-2" required>
      </label>
      <label class="block text-sm font-semibold mb-1">Action Taken
        <input type="text" name="action_taken" id="edit_action_taken" class="w-full border rounded p-2" required>
      </label>
      <label class="block text-sm font-semibold mb-1">Medicine Name
        <select name="medicine_name" id="edit_medicine_name" class="w-full border rounded p-2" onchange="updateDosage()">
          <option value="">Select Medicine</option>
          <?php foreach ($medicines as $med): ?>
            <option value="<?= htmlspecialchars($med['name']) ?>" data-dosage="<?= htmlspecialchars($med['dosage']) ?>"><?= htmlspecialchars($med['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block text-sm font-semibold mb-1">Dosage
        <input type="text" name="dosage" id="edit_dosage" class="w-full border rounded p-2" readonly>
      </label>
      <label class="block text-sm font-semibold mb-1">Quantity
        <input type="number" name="quantity" id="edit_quantity" class="w-full border rounded p-2" min="1">
      </label>
      <label class="block text-sm font-semibold mb-1">Date/Time of Visit
        <input type="datetime-local" name="visit_date" id="edit_visit_date" class="w-full border rounded p-2" required>
      </label>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeModal('editModal')" class="px-3 py-1 bg-gray-400 text-white rounded">Cancel</button>
        <button type="submit" id="submit_btn" class="px-3 py-1 bg-blue-500 text-white rounded">Save</button>
      </div>
    </form>
  </div>
</div>


    <script>
    function toggleMenu() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
    }

    // Close sidebar when clicking overlay
    document.getElementById('sidebarOverlay').addEventListener('click', toggleMenu);
    document.getElementById('menuBtn').addEventListener('click', toggleMenu);

    function closeModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
    }

    function updateDosage() {
      const medicineSelect = document.getElementById('edit_medicine_name');
      const dosageInput = document.getElementById('edit_dosage');
      const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
      dosageInput.value = selectedOption.getAttribute('data-dosage') || '';
    }

    function openModal(id = null) {
      const modalTitle = document.getElementById('modalTitle');
      const submitBtn = document.getElementById('submit_btn');
      const formAction = document.getElementById('form_action');

      if (id) {
        // Editing Mode
        const row = document.getElementById(`row-${id}`);
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_student_name').value = row.dataset.studentName;
        document.getElementById('edit_course_year_section').value = row.dataset.course;
        document.getElementById('edit_reason').value = row.dataset.reason;
        document.getElementById('edit_action_taken').value = row.dataset.actionTaken;
        document.getElementById('edit_medicine_name').value = row.dataset.medicineName || '';
        document.getElementById('edit_dosage').value = row.dataset.dosage || '';
        document.getElementById('edit_quantity').value = row.dataset.quantity || '';
        document.getElementById('edit_visit_date').value = row.dataset.visitDate;

        formAction.value = "update";
        submitBtn.textContent = "Update";
        modalTitle.textContent = "Edit Student Visit Log";
      } else {
        // Adding Mode
        document.getElementById('edit_id').value = "";
        document.getElementById('edit_student_name').value = "";
        document.getElementById('edit_course_year_section').value = "";
        document.getElementById('edit_reason').value = "";
        document.getElementById('edit_action_taken').value = "";
        document.getElementById('edit_medicine_name').value = "";
        document.getElementById('edit_dosage').value = "";
        document.getElementById('edit_quantity').value = "";
        document.getElementById('edit_visit_date').value = new Date().toISOString().slice(0,16);

        formAction.value = "add";
        submitBtn.textContent = "Add";
        modalTitle.textContent = "Add New Student Visit Log";
      }

      document.getElementById('editModal').classList.remove('hidden');
    }

    // Add event listener for the Add New Visit button
    document.getElementById('addNewVisitBtn').addEventListener('click', function() {
      openModal();
    });
    </script>
</body>
</html>
<?php if ($conn) $conn->close(); ?>