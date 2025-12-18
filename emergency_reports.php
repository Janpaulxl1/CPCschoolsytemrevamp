<?php
session_start();
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'db.php';

$result_emerg = false;

if ($conn) {
    $sql_emerg = "SELECT id, message, created_at, action_taken FROM notifications WHERE message LIKE 'Emergency%' ORDER BY created_at DESC LIMIT 20";
    $result_emerg = $conn->query($sql_emerg);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>School Health Clinic</title>
    <link rel="icon" type="image" href="images/favicon.jpg">
    <link rel="icon" type="image" href="images/favicon.jpg">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#BB1D1D]"></body>

  <!-- HEADER -->
  <header class="bg-gradient-to-r from-orange-400 to-red-600 text-white py-3 px-6 flex justify-between items-center shadow relative">
    <div class="flex items-center space-x-3">
      <button id="menuBtn" class="text-2xl cursor-pointer">&#9776;</button>
      <h1 class="text-xl font-bold">School Health Clinic</h1>
    </div>
    <div>
     <img src="images/logo.png" alt="Logo" class="absolute right-4 top-2 w-48 h-48 rounded-full object-cover z-10">
    </div>
  </header>

  <!-- Dropdown Menu -->
  <div id="menu" class="absolute bg-white w-64 p-4 hidden top-[65px] left-0 shadow z-10 rounded-md">
    <div class="flex items-center mb-4">
      <h2 class="text-lg font-semibold">File Clinic Explorer</h2>
    </div>
    <a class="block mb-4" href="nurse.php">↩️ Go Back</a>
    <details class="group mb-4">
      <summary class="cursor-pointer flex items-center hover:bg-gray-300 transition p-2 rounded">
      📁 Documents
      </summary>
      <ul class="ml-6 mt-2 space-y-1 text-[15px]">
        <li><a href="physical_assessment.php" class="block hover:bg-gray-200 rounded px-1">↳ Student Physical Assessment Form</a></li>
        <li><a href="health_service_report.php" class="block hover:bg-gray-200 rounded px-1">↳ Health Service Utilization Report</a></li>
        <li><a href="first_aid.php" class="block hover:bg-gray-200 rounded px-1">↳ First Aid Procedure</a></li>
        <li><a href="Emergency Plan.php" class="block hover:bg-gray-200 rounded px-1">↳ Emergency Respond Plan</a></li>
      </ul>
    </details>

    <a class="block mb-4" href="medication_dashboard.php">📁 Medical Supplies</a>
    <a class="block mb-4" href="studentfile_dashboard.php">📁 Student File Dashboard</a>
    <a class="block mb-4" href="registration.php">📁 Register Student Health</a>
    <a class="block mb-4" href="Student_visitlogs.php">📁 Student Visit Logs</a>
    <a class="block mb-4" href="appointment_history.php">📁 Appointment History</a>
    <a class="block mb-4" href="emergency_reports.php">📁 Emergency Reports</a>
    <a class="block mb-4" href="responder_status.php">📁 Responder Status</a>
  </div>

  <!-- Nurse Profile Section -->
  <section class="bg-white shadow-md p-4 rounded-md max-w-6xl mx-auto mt-4 flex items-center gap-4">
    <img src="images/nurse.jpg" alt="Nurse" class="w-20 h-20 rounded-full border-4 border-green-500">
    <div>
      <p class="text-sm text-gray-500">Nurse</p>
      <h2 class="text-xl font-semibold">Mrs. Lorefe F. Verallo</h2>
      <p class="text-sm text-gray-500">Nurse ID: 2022001</p>
    </div>
  </section>

  <!-- MAIN CONTAINER -->
  <div class="max-w-6xl mx-auto bg-white mt-8 rounded-xl shadow-lg overflow-hidden">

    <!-- Emergency Report Log -->
    <div class="px-6 py-4">
      <h3 class="text-lg font-bold text-red-600 mb-3 flex items-center">
        Emergency Report Log
      </h3>

      <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full border-collapse">
          <thead class="bg-red-500 text-white">
            <tr>
              <th class="p-2 text-left">Date & Time</th>
              <th class="p-2 text-left">Reported By</th>
              <th class="p-2 text-left">Incident</th>
              <th class="p-2 text-left">Details</th>
              <th class="p-2 text-left">Action Taken</th>
              <th class="p-2 text-left">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white text-black">
            <?php if ($result_emerg && $result_emerg->num_rows > 0): ?>
              <?php while($row = $result_emerg->fetch_assoc()): ?>
                <tr class="border-b hover:bg-red-50">
                  <td class="p-2"><?php echo date('Y-m-d h:i A', strtotime($row['created_at'])); ?></td>
                  <td class="p-2 text-blue-700 font-medium">
                    <?php
                      // Extract reporter name if stored in message (example: "Emergency reported by John Doe")
                      if (preg_match('/by\s+([A-Za-z\s]+)/', $row['message'], $matches)) {
                        echo htmlspecialchars(trim($matches[1]));
                      } else {
                        echo "Unknown";
                      }
                    ?>
                  </td>
                  <td class="p-2 text-red-600 font-semibold">Emergency</td>
                  <td class="p-2"><?php echo htmlspecialchars($row['message']); ?></td>
                  <td class="p-2">
                    <div id="action-<?php echo $row['id']; ?>" class="mb-1"><?php echo htmlspecialchars($row['action_taken'] ?? ''); ?></div>
                    <button onclick="openActionModal(<?php echo $row['id']; ?>)" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition">Add/Edit</button>
                  </td>
                  <td class="p-2">
                    <span class="<?php echo (!empty($row['action_taken'])) ? 'text-green-600 font-semibold' : 'text-orange-600 font-semibold'; ?>">
                      <?php echo (!empty($row['action_taken'])) ? 'Resolved' : 'Pending'; ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-gray-500 p-4">No emergency reports found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal for Action Taken -->
  <div id="actionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
      <h3 class="text-lg font-bold mb-4 text-red-600">Add/Edit Action Taken</h3>
      <textarea id="actionTextarea" rows="4" class="w-full p-2 border border-gray-300 rounded" placeholder="Describe the action taken..."></textarea>
      <div class="flex justify-end mt-4 space-x-2">
        <button onclick="closeActionModal()" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
        <button onclick="saveAction()" class="px-4 py-2 bg-red-600 text-white rounded">Save</button>
      </div>
    </div>
  </div>

  <script>
    let currentActionId = null;

    function toggleMenu() {
      const menu = document.getElementById('menu');
      menu.classList.toggle('hidden');
    }

    function openActionModal(id) {
      currentActionId = id;
      // Fetch current action from the database or from the DOM
      const currentAction = document.getElementById(`action-${id}`).textContent;
      document.getElementById('actionTextarea').value = currentAction;
      document.getElementById('actionModal').classList.remove('hidden');
    }

    function closeActionModal() {
      document.getElementById('actionModal').classList.add('hidden');
      currentActionId = null;
    }

    async function saveAction() {
      const action = document.getElementById('actionTextarea').value.trim();
      if (!currentActionId) return;

      try {
        const response = await fetch('update_action_taken.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: currentActionId, action_taken: action })
        });

        const result = await response.json();
        if (result.success) {
          document.getElementById(`action-${currentActionId}`).textContent = action;
          // Update status dynamically
          const statusSpan = document.querySelector(`#action-${currentActionId}`).closest('tr').querySelector('td:last-child span');
          if (action.trim() !== '') {
            statusSpan.textContent = 'Resolved';
            statusSpan.className = 'text-green-600 font-semibold';
          } else {
            statusSpan.textContent = 'Pending';
            statusSpan.className = 'text-orange-600 font-semibold';
          }
          closeActionModal();
        } else {
          alert('Failed to save action: ' + result.message);
        }
      } catch (error) {
        alert('Error saving action: ' + error.message);
      }
    }

    document.getElementById('menuBtn').addEventListener('click', toggleMenu);
  </script>
</body>
</html>