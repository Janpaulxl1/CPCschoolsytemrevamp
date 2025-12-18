    <?php
session_start();
require_once 'db.php';

$result_medications = false;
if ($conn) {
    $sql = "SELECT * FROM medications ORDER BY id ASC";
    $result_medications = $conn->query($sql);
}

// Handle success/error messages from query parameters
$success_message = '';
$error_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success_message = 'Medication entry deleted successfully.';
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_id') {
        $error_message = 'Invalid medication ID.';
    } elseif ($_GET['error'] === 'delete_failed') {
        $error_message = 'Failed to delete medication entry.';
    }
}

// Function to send notification to nurse
function sendNotificationToNurse($message) {
    global $conn;
    // Get nurse user_id
    $sql = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'nurse' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// Mark medicine and appointment-related notifications as read when dashboard is viewed
if ($conn) {
    $sql = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'nurse' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nurse_id = $row['id'];
        $update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND (message LIKE '%Low stock alert%' OR message LIKE '%Expiration alert%' OR message LIKE '%reject%' OR message LIKE '%accept%' OR message LIKE '%reschedule%') AND is_read = 0";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>CPC Clinic - Medicine Supplies</title>
  <link rel="icon" type="image" href="images/favicon.jpg">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    /* small custom styles to mimic the Figma diagonal red shapes and inner border */
     .left-diagonal {
            position: absolute;
            top: 0;
            left: 0;
            width: 70px;
            height: 320px;
            background: #b71c1c;
            clip-path: polygon(0 0, 100% 0, 70% 100%, 0% 100%);
            z-index: -1;
        }

        /* RIGHT SIDE DIAGONAL (BOTTOM) */
        .right-diagonal {
            position: absolute;
            bottom: 0;
            right: -50px;
            width: 120px;
            height: 520px;
            background: #b71c1c;
            clip-path: polygon(30% 0, 100% 0, 100% 100%, 0 100%);
            z-index: -1;
        }

    /* inner rounded card border like the Figma */
    .inner-card {
      border: 2px solid rgba(191, 54, 54, 0.25);
      border-radius: 18px;
      background: #fff;
    }

    /* subtle table cell border color */
    .tbl-border td, .tbl-border th {
      border: 1px solid rgba(191, 54, 54, 0.25);
    }



    /* keep header profile box border */
    .profile-box {
      border: 1px solid rgba(191,54,54,0.25);
      border-radius: 8px;
      background: #fff;
    }

    /* Sidebar hamburger menu */
    #sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100vh;
        background: #ffffff;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        transition: left 0.3s ease;
        z-index: 1000;
        padding: 1rem;
        overflow-y: auto;
        border-right: 1px solid #e5e7eb;
    }
    #sidebar.open {
        left: 0;
    }
    #sidebar h2 {
        color: #b71c1c;
        font-weight: 700;
        margin-bottom: 1.5rem;
        font-size: 1.2rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    #sidebar a {
        display: block;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        color: #374151;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    #sidebar a:hover {
        background: #dc2626;
        color: white;
        transform: translateX(5px);
        font-weight: bold;
    }
    #sidebar summary {
        color: #374151;
        font-weight: 600;
        cursor: pointer;
    }
    #sidebar details {
        margin-bottom: 1rem;
    }
    #sidebar ul {
        list-style: none;
        padding-left: 1rem;
        margin-top: 0.5rem;
    }
    #sidebar li {
        margin-bottom: 0.25rem;
    }
    #sidebar a i {
        margin-right: 0.5rem;
        width: 20px;
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
<body class="bg-gray-200 relative min-h-screen">
   <div class="left-diagonal"></div>

    <!-- RIGHT DIAGONAL SHAPE -->
    <div class="right-diagonal"></div>

  <!-- Red top bar (thick) -->
  <header class="p-4 text-white relative">
    <div class="flex items-center space-x-4">
      <button id="menuBtn" class="text-2xl font-bold cursor-pointer hover:bg-white hover:bg-opacity-10 rounded p-2 transition">☰</button>
      <!-- top-left label if needed -->
      <div class="text-white font-bold text-lg">School Health Clinic</div>
    </div>

    <!-- right side profile and bell -->
    <div class="ml-auto flex items-center space-x-4">


      <div class="profile-box flex items-center space-x-3 px-4 py-2">
        <img src="images/nurse.jpg" alt="Nurse" class="w-10 h-10 rounded-full border-2 border-red-600 object-cover">
        <div>
          <div class="font-semibold text-sm text-black">Mrs. Lorefe F. Verallo</div>
          <div class="text-xs text-gray-500">Nurse</div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <nav id="sidebar">
      <a href="nurse.php"><i class="fas fa-arrow-left"></i> Back</a>
      <h2>File Clinic Explorer</h2>
      <details>
        <summary onclick="event.preventDefault(); this.parentElement.open = !this.parentElement.open;">📁 Documents</summary>
        <ul>
        <li><a href="physical_assessment.php"><i class="fas fa-file-medical"></i> Student Physical Assessment Form</a></li>
        <li><a href="health_service_report.php"><i class="fas fa-chart-bar"></i> Health Service Utilization Report</a></li>
        <li><a href="first_aid.php"><i class="fas fa-first-aid"></i> First Aid Procedure</a></li>
        <li><a href="emergency_plan.html"><i class="fas fa-exclamation-triangle"></i> Emergency Respond Plan</a></li>
        </ul>
      </details>
      <a href="medication_dashboard.php"><i class="fas fa-pills"></i> Medical Supplies</a>
      <a href="studentfile_dashboard.php"><i class="fas fa-folder-open"></i> Student File Dashboard</a>
      <a href="registration.php"><i class="fas fa-user-plus"></i> Register Student Health</a>
      <a href="Student_visitlogs.php"><i class="fas fa-history"></i> Student Visit Logs</a>
      <a href="appointment_history.php"><i class="fas fa-calendar-alt"></i> Appointment History</a>
      <a href="emergency_reports.php"><i class="fas fa-bell"></i> Emergency Reports</a>
      <a href="convert_responder.php"><i class="fas fa-user-shield"></i> Emergency Responder</a>
      <a href="responder_status.php"><i class="fas fa-user-shield"></i> Responder Status</a>
      <a href="nurse_reset_password.php"><i class="fas fa-key"></i> Reset User Password</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </header>

  <!-- Sidebar Overlay -->
  <div id="sidebarOverlay"></div>

  <!-- red diagonal shapes left & right (behind content) -->
  <div class="diag-left hidden lg:block"></div>
  <div class="diag-right hidden lg:block"></div>

  <!-- MAIN centered content wrapper -->
  <main class="relative z-30 max-w-7xl mx-auto px-6 pt-8 pb-16">

    <!-- MAIN centered content wrapper -->
    <div class="w-full mx-auto">
        <div class="inner-card p-6 shadow-lg">

          <!-- Inner white area with extra rounded corners like the Figma panel -->
          <div class="bg-white rounded-xl p-6" style="border-radius:14px;">
            <!-- Title -->
            <div class="text-center mb-6">
              <h1 class="text-3xl font-bold">Medicine Supplies</h1>
            </div>

            <!-- Controls row: dropdown left, add button right -->
            <div class="flex justify-between items-center mb-6">
              <div>
                <select class="border border-red-300 rounded-md px-3 py-2">
                  <option value="">Filter</option>
                  <option value="all">All</option>
                </select>
              </div>

              <div>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
                  + Add Medicine
                </button>
              </div>
            </div>

            <!-- Success / Error Alerts (unchanged PHP messages) -->
            <?php if ($success_message): ?>
              <div class="mb-4 p-3 bg-green-100 text-green-800 rounded border border-green-300">
                <?= htmlspecialchars($success_message) ?>
              </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
              <div class="mb-4 p-3 bg-red-100 text-red-800 rounded border border-red-300">
                <?= htmlspecialchars($error_message) ?>
              </div>
            <?php endif; ?>

            <!-- The medicine table card with rounded corners -->
            <div class="bg-white rounded-lg p-2 border border-red-200 shadow-sm">
              <div class="overflow-x-auto">
                <table class="min-w-full tbl-border text-sm">
                  <thead class="bg-red-600 text-white">
                    <tr>
                      <th class="px-4 py-2 text-left">Brand Name</th>
                      <th class="px-4 py-2 text-left">Medicine Name</th>
                      <th class="px-4 py-2 text-left">Dosage</th>
                      <th class="px-4 py-2 text-left">Usage</th>
                      <th class="px-4 py-2 text-left">Quantity</th>
                      <th class="px-4 py-2 text-left">Delivery Date</th>
                      <th class="px-4 py-2 text-left">Expiration Date</th>
                      <th class="px-4 py-2 text-left">Notes</th>
                      <th class="px-4 py-2 text-center">Action</th>
                    </tr>
                  </thead>

                  <tbody class="bg-white">
                    <?php if ($result_medications && $result_medications->num_rows > 0): ?>
                      <?php while($row = $result_medications->fetch_assoc()): ?>
                        <?php
                        // highlight classes preserved
                        $quantity_class = $row['quantity'] <= 10 ? 'bg-red-100 text-red-800' : '';
                        $expiration_class = strtotime($row['expiration_date']) < time() ? 'bg-red-100 text-red-800' : '';

                        // Keep notification logic EXACTLY as before (unchanged)
                        if ($row['quantity'] <= 10) {
                            sendNotificationToNurse("Low stock alert: " . htmlspecialchars($row['name']) . " has only " . $row['quantity'] . " items left.");
                        }
                        if (strtotime($row['expiration_date']) < time()) {
                            sendNotificationToNurse("Expiration alert: " . htmlspecialchars($row['name']) . " has expired on " . htmlspecialchars($row['expiration_date']) . ".");
                        }
                        ?>
                        <tr class="text-black">
                          <td class="px-4 py-2"><?= htmlspecialchars($row['brand'] ?? '') ?></td>
                          <td class="px-4 py-2"><?= htmlspecialchars($row['name']) ?></td>
                          <td class="px-4 py-2"><?= htmlspecialchars($row['dosage']) ?></td>
                          <td class="px-4 py-2"><?= htmlspecialchars($row['instructions']) ?></td>
                          <td class="px-4 py-2 <?= $quantity_class ?>"><?= htmlspecialchars($row['quantity']) ?><?php if ($row['quantity'] <= 10) echo ' ⚠️ Low Stock'; ?></td>
                          <td class="px-4 py-2"><?= htmlspecialchars($row['delivery_date'] ?? '') ?></td>
                          <td class="px-4 py-2 <?= $expiration_class ?>"><?= htmlspecialchars($row['expiration_date']) ?><?php if (strtotime($row['expiration_date']) < time()) echo ' ⚠️ Expired'; ?></td>
                          <td class="px-4 py-2"><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                          <td class="px-4 py-2 text-center">
                            <button
                              data-id="<?= $row['id'] ?>"
                              data-name="<?= htmlspecialchars($row['name']) ?>"
                              data-dosage="<?= htmlspecialchars($row['dosage']) ?>"
                              data-instructions="<?= htmlspecialchars($row['instructions']) ?>"
                              data-quantity="<?= htmlspecialchars($row['quantity']) ?>"
                              data-expiration-date="<?= htmlspecialchars($row['expiration_date']) ?>"
                              onclick="openEditModal(this)"
                              class="inline-block mr-2 px-2 py-1 rounded bg-blue-500 text-white hover:bg-blue-600">
                              ✏️
                            </button>

                            <form action="delete_medication.php" method="POST" class="inline">
                              <input type="hidden" name="id" value="<?= $row['id'] ?>">
                              <button type="submit" class="inline-block px-2 py-1 rounded bg-red-500 text-white hover:bg-red-600">🗑️</button>
                            </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">No medication data found.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- end table card -->

          </div> <!-- end inner white area -->
        </div> <!-- end inner-card -->
      </div> <!-- end main card -->
    </div> <!-- end flex -->
  </main>

  <!-- ADD MODAL (preserved content) -->
  <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
      <h2 class="text-xl font-bold mb-4">Add Medicine</h2>
      <form action="add_medicine.php" method="POST" class="space-y-3">
        <input type="text" name="name" placeholder="Medicine Name" class="w-full border p-2 rounded" required>
        <input type="text" name="dosage" placeholder="Dosage" class="w-full border p-2 rounded">
        <textarea name="instructions" placeholder="Instructions / Usage" class="w-full border p-2 rounded"></textarea>
        <input type="number" name="quantity" placeholder="Quantity" class="w-full border p-2 rounded" required>
        <input type="date" name="expiration_date" class="w-full border p-2 rounded" required>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-3 py-2 border rounded">Cancel</button>
          <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MODAL (preserved content) -->
  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
      <h2 class="text-xl font-bold mb-4">Edit Medicine</h2>
      <form action="update_medicine.php" method="POST" class="space-y-3">
        <input type="hidden" name="id" id="edit_id">
        <input type="text" name="name" id="edit_name" placeholder="Medicine Name" class="w-full border p-2 rounded" required>
        <input type="text" name="dosage" id="edit_dosage" placeholder="Dosage" class="w-full border p-2 rounded">
        <textarea name="instructions" id="edit_instructions" placeholder="Instructions / Usage" class="w-full border p-2 rounded"></textarea>
        <input type="number" name="quantity" id="edit_quantity" placeholder="Quantity" class="w-full border p-2 rounded" required>
        <input type="date" name="expiration_date" id="edit_expiration_date" class="w-full border p-2 rounded" required>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-3 py-2 border rounded">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">Update</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    function toggleMenu() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
    }

    window.onload = function() {
      // Close sidebar when clicking overlay
      document.getElementById('sidebarOverlay').addEventListener('click', toggleMenu);

      document.getElementById('menuBtn').addEventListener('click', toggleMenu);
    };

    // open edit modal and populate values
    function openEditModal(button) {
      const id = button.getAttribute('data-id');
      const name = button.getAttribute('data-name');
      const dosage = button.getAttribute('data-dosage');
      const instructions = button.getAttribute('data-instructions');
      const quantity = button.getAttribute('data-quantity');
      const expiration_date = button.getAttribute('data-expiration-date');

      document.getElementById('edit_id').value = id;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_dosage').value = dosage;
      document.getElementById('edit_instructions').value = instructions;
      document.getElementById('edit_quantity').value = quantity;
      document.getElementById('edit_expiration_date').value = expiration_date;

      document.getElementById('editModal').classList.remove('hidden');
    }

    // close modals when clicking outside modal content
    window.addEventListener('click', function(e) {
      const addModal = document.getElementById('addModal');
      const editModal = document.getElementById('editModal');

      if (e.target === addModal) addModal.classList.add('hidden');
      if (e.target === editModal) editModal.classList.add('hidden');
    });
  </script>

</body>
</html>

<?php if ($conn) $conn->close(); ?>
