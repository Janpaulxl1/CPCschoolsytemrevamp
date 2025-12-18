<?php
  session_start();
  require_once "db.php";

  if (!isset($_SESSION['student_id'])) {
      die("❌ Please log in as a student first.");
  }

  $student_id = $_SESSION['student_id'];

  // Fetch student info
  $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
  $stmt->bind_param("s", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->fetch_assoc();

  // Check if student is a responder
  $student_name = $student['first_name'] . " " . $student['last_name'];
  $stmt = $conn->prepare("SELECT COUNT(*) as is_responder FROM emergency_responders WHERE name = ?");
  $stmt->bind_param("s", $student_name);
  $stmt->execute();
  $responder_result = $stmt->get_result();
  $responder_row = $responder_result->fetch_assoc();
  $is_responder = $responder_row['is_responder'] > 0;

  if (!$student) {
      die("❌ Student not found (ID: $student_id).");
  }

  $fullname = $student['first_name'] . " " . $student['last_name'];

  // Fetch student appointments
  $stmt = $conn->prepare("SELECT id, appointment_time, reason, status FROM appointments WHERE student_id = ? ORDER BY appointment_time DESC");
  $stmt->bind_param("s", $student['student_id']);
  $stmt->execute();
  $appointments_result = $stmt->get_result();
  $appointments = [];
  while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
  }

  // For each appointment, fetch associated medicines
  $medicines = [];
  foreach ($appointments as $appt) {
    $stmt = $conn->prepare("SELECT medicine_name, dosage, quantity, action_taken, created_at FROM appointment_medications WHERE appointment_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $appt['id']);
    $stmt->execute();
    $med_result = $stmt->get_result();
    $medicines[$appt['id']] = [];
    while ($med_row = $med_result->fetch_assoc()) {
      $medicines[$appt['id']][] = $med_row;
    }
  }

  // Fetch appointment-related notifications
  $stmt = $conn->prepare("SELECT id, message, reschedule_status, appointment_id, created_at FROM student_notifications WHERE student_id = ? AND appointment_id IS NOT NULL ORDER BY created_at DESC");
  $stmt->bind_param("s", $student['student_id']);
  $stmt->execute();
  $notifications_result = $stmt->get_result();
  $appointment_notifications = [];
  while ($row = $notifications_result->fetch_assoc()) {
    $appointment_notifications[$row['appointment_id']][] = $row;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CPC Clinic</title>
  <link rel="icon" type="image" href="images/favicon.jpg">    
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  body { background:#f3f3f3; }
  </style>
  </head>
  <body class="bg-gray-100">

  <div class="w-full h-11 bg-white"></div>

  <!-- Navbar -->
  <div class="flex justify-between items-center bg-red-600 p-4 text-black relative">
    <button id="menuBtn" class="text-2xl z-50 mt-[-100px]">&#9776;</button>
  
    <div class="relative">
      <button id="notifBtn" class="text-2xl relative -top-12">&#128276;
        <span id="notifBadge" class="hidden absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-1 rounded-full"></span>
      </button>
      <!-- Notification Box -->
      <div id="notifBox" class="hidden absolute right-0 mt-2 w-72 sm:w-80 bg-white shadow-lg rounded-lg p-4 text-black z-50 max-h-96 overflow-y-auto">
        <h3 class="font-semibold mb-2">Notifications</h3>
        <div id="notifList">
          <p class="text-sm text-gray-500">Loading...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div id="sidebar" class="fixed top-0 left-0 w-64 max-w-full h-full bg-gradient-to-b from-red-50 to-white text-gray-800 p-4 z-40 transform -translate-x-full transition-transform duration-300 md:w-72 shadow-lg border-r border-red-200">
    <div class="mt-16 space-y-2">
      <a href="appointments.php" class="flex items-center py-3 px-3 rounded-lg hover:bg-red-100 transition-colors">
        <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        Book Appointment
      </a>
      <a href="report_emergency.php" class="flex items-center py-3 px-3 rounded-lg hover:bg-red-100 transition-colors">
        <svg class="w-5 h-5 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        Report Emergency
      </a>
      <a href="logout.php" class="flex items-center py-3 px-3 rounded-lg hover:bg-red-100 transition-colors">
        <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
        Logout
      </a>
    </div>
  </div>
  
  <!-- Professional Red Header with Centered Profile Photo -->
  <div class="w-full bg-red-600 pb-24 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-red-500/10 to-transparent"></div>
    <div class="relative z-10">
      <!-- Centered Profile Photo -->
      <div class="absolute left-1/2 transform -translate-x-1/2 top-10">
        <div class="relative">
          <div class="w-40 h-40 md:w-48 md:h-48 rounded-full bg-white shadow-xl ring-4 ring-red-600/50">
            <img src="<?= htmlspecialchars($student['profile_picture'] ?: 'images/default.png') ?>"
              alt="Profile Picture"
              class="w-full h-full object-cover rounded-full border-4 border-white shadow-inner" />
          </div>
          <!-- Elegant Green Online Indicator -->
          <div class="absolute -bottom-1 -right-1">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-green-500 border-3 border-white rounded-full shadow-lg ring-2 ring-green-400/50"></div>
          </div>
          <?php if ($is_responder): ?>
          <!-- Red Medal Icon Overlay on Image -->
          <div class="absolute bottom-2 left-2 bg-red-500 text-white rounded-full p-1.5 shadow-md flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 00-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Professional Profile Info Section -->
  <div class="mt-32 md:mt-36 text-center px-4 max-w-2xl mx-auto">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2 leading-tight"><?= htmlspecialchars($fullname) ?></h1>
    <p class="text-red-600 font-semibold text-lg mb-1 tracking-wide"><?= htmlspecialchars($student['student_id']) ?></p>
    <p class="text-gray-700 text-sm md:text-base italic font-medium">Bachelor of Science in Information Technology</p>
    <?php if ($is_responder): ?>
    <p class="text-red-600 font-semibold text-base mt-1">Student Responder</p>
    <?php endif; ?>
  </div>

  <!-- Tabs for About and Appointments -->
  <div class="flex justify-center gap-3 mt-4">
    <button class="tab-btn bg-red-600 text-white px-6 py-2 rounded-full" data-tab="about">About</button>
    <button class="tab-btn bg-gray-200 text-black px-6 py-2 rounded-full" data-tab="appointments">Appointments</button>
  </div>

  <!-- Enhanced About Content -->
  <div id="about" class="tab-content mt-4 px-4">
    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Personal Info Card -->
      <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          Personal Information
        </h3>
        <div class="space-y-3 text-sm">
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span><strong>Birthday:</strong> <?= htmlspecialchars($student['birthday'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <span><strong>Gender:</strong> <?= htmlspecialchars($student['gender'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span><strong>Phone:</strong> <?= htmlspecialchars($student['phone'] ?? 'N/A') ?></span>
          </div>
        </div>
      </div>

      <!-- Contact & Guardian Card -->
      <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          Contact & Guardian
        </h3>
        <div class="space-y-3 text-sm">
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span><strong>Home Address:</strong> <?= htmlspecialchars($student['home_address'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span><strong>Guardian:</strong> <?= htmlspecialchars($student['guardian_name'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span><strong>Guardian Phone:</strong> <?= htmlspecialchars($student['emergency_contact'] ?? 'N/A') ?></span>
          </div>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-4a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <span><strong>Relationship:</strong> <?= htmlspecialchars($student['guardian_relationship'] ?? 'N/A') ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Enhanced Appointments Content -->
  <div id="appointments" class="tab-content hidden mt-4 px-4">
    <div class="max-w-4xl mx-auto">
      <?php if (count($appointments) > 0): ?>
        <div class="space-y-4">
          <?php foreach ($appointments as $appt): ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-shadow duration-300">
              <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                  <div class="flex items-center mb-2 md:mb-0">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h4 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars(date('M d, Y g:i A', strtotime($appt['appointment_time']))) ?></h4>
                  </div>
                  <span class="px-3 py-1 rounded-full text-xs font-bold <?php 
                    if ($appt['status'] == 'Pending') echo 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                    elseif ($appt['status'] == 'Completed') echo 'bg-green-100 text-green-800 border border-green-200';
                    elseif ($appt['status'] == 'Conflict') echo 'bg-red-100 text-red-800 border border-red-200';
                    else echo 'bg-gray-100 text-gray-800 border border-gray-200';
                  ?>">
                    <?= htmlspecialchars($appt['status']) ?>
                  </span>
                </div>
                <p class="text-gray-600 mb-4"><strong>Reason:</strong> <?= htmlspecialchars($appt['reason']) ?></p>
                
                <?php if (!empty($medicines[$appt['id']])): ?>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 mb-3 flex items-center">
                      <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      Prescribed Medications
                    </h5>
                    <div class="space-y-2">
                      <?php foreach ($medicines[$appt['id']] as $med): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2 bg-white rounded border">
                          <span class="font-medium"><?= htmlspecialchars($med['medicine_name']) ?></span>
                          <span class="text-sm text-gray-600"><?= htmlspecialchars($med['dosage']) ?> (Qty: <?= htmlspecialchars($med['quantity']) ?>)</span>
                          <?php if ($med['action_taken']): ?>
                            <span class="text-xs text-gray-500">Action: <?= htmlspecialchars($med['action_taken']) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php else: ?>
                  <p class="text-gray-500 italic">No medications prescribed for this appointment.</p>
                <?php endif; ?>

                <?php if (!empty($appointment_notifications[$appt['id']])): ?>
                  <div class="bg-blue-50 rounded-lg p-4 mt-4">
                    <h5 class="font-semibold text-gray-800 mb-3 flex items-center">
                      <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Appointment Updates
                    </h5>
                    <div class="space-y-2">
                      <?php foreach ($appointment_notifications[$appt['id']] as $notif): ?>
                        <div class="p-2 bg-white rounded border">
                          <p class="text-sm text-gray-700"><?= htmlspecialchars($notif['message']) ?></p>
                          <p class="text-xs text-gray-500">Status: <?= htmlspecialchars($notif['reschedule_status'] ?? 'N/A') ?> | <?= htmlspecialchars(date('M d, Y g:i A', strtotime($notif['created_at']))) ?></p>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-12">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">No Appointments Yet</h3>
          <p class="text-gray-600 mb-4">Your medical history will appear here once you book an appointment.</p>
          <a href="appointments.php" class="inline-block bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 transition-colors">
            Book Appointment
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <audio id="dingSound" preload="auto">
    <source src="bell.mp3" type="audio/mpeg" />
  </audio>

  <script>
    const sidebar = document.getElementById("sidebar");
    const menuBtn = document.getElementById("menuBtn");
    const notifBtn = document.getElementById("notifBtn");
    const notifBox = document.getElementById("notifBox");
    let lastNotificationCount = 0;
    const ding = document.getElementById("dingSound");

    menuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      sidebar.classList.toggle("-translate-x-full");
      sidebar.classList.toggle("translate-x-0");
    });

    document.addEventListener("click", (e) => {
      if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.add("-translate-x-full");
        sidebar.classList.remove("translate-x-0");
      }
    });

    document.querySelectorAll(".tab-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("bg-red-600", "text-white"));
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.add("bg-gray-200", "text-black"));
        btn.classList.add("bg-red-600", "text-white");
        btn.classList.remove("bg-gray-200", "text-black");

        document.querySelectorAll(".tab-content").forEach(c => c.classList.add("hidden"));
        document.getElementById(btn.dataset.tab).classList.remove("hidden");
      });
    });

    document.body.addEventListener("click", () => {
      if (ding) {
        ding.play().then(() => {
          ding.pause();
          ding.currentTime = 0;
        }).catch(err => console.warn("Could not play notification sound", err));
      }
    });

    async function fetchNotifications() {
      try {
        const res = await fetch("student_notification_checker.php");
        const data = await res.json();

        const list = document.getElementById("notifList");
        list.innerHTML = "";
        let unread = 0;

        if (!data.notifications || data.notifications.length === 0) {
          list.innerHTML = "<p class='text-gray-500 text-sm'>No notifications found.</p>";
        } else {
          data.notifications.forEach(note => {
            const div = document.createElement("div");
            div.className = "border-b pb-1 mb-1 " + (note.is_read == 1 ? "text-gray-500" : "font-semibold text-black");
            let innerHTML = `${note.message}<br><small class="text-gray-500">${new Date(note.created_at).toLocaleString()}</small>`;

            if (note.reschedule_status === 'pending') {
              innerHTML += `<br>
                <button class="accept-resched bg-green-500 text-white px-2 py-1 rounded text-xs mr-1" 
                  data-appointment-id="${note.appointment_id}" data-notification-id="${note.id}">Accept</button>
                <button class="decline-resched bg-red-500 text-white px-2 py-1 rounded text-xs" 
                  data-appointment-id="${note.appointment_id}" data-notification-id="${note.id}">Decline</button>`;
            } else if (note.reschedule_status === 'accepted') {
              innerHTML += `<br><span class="text-green-600 text-xs">You accepted this reschedule.</span>`;
            } else if (note.reschedule_status === 'declined' || note.reschedule_status === 'rejected') {
              innerHTML += `<br><span class="text-red-600 text-xs">You declined this reschedule.</span>`;
            }
            div.innerHTML = innerHTML;
            list.appendChild(div);
            if (note.is_read == 0) unread++;
          });
        }

        const badge = document.getElementById("notifBadge");
        if (unread > 0) {
          badge.textContent = unread;
          badge.classList.remove("hidden");
        } else {
          badge.classList.add("hidden");
        }

        if (unread > lastNotificationCount) {
          playBell();
        }
        lastNotificationCount = unread;
      } catch (e) {
        console.error("fetchNotifications error:", e);
      }
    }

    function playBell() {
      if (ding) {
        ding.play().catch(err => console.warn("Error playing notification sound", err));
      }
    }

    notifBtn.addEventListener("click", async (e) => {
      e.stopPropagation();
      notifBox.classList.toggle("hidden");
      if (!notifBox.classList.contains("hidden")) {
        await fetchNotifications();
        try {
          await fetch("student_mark_read.php", { method: "POST" });
          fetchNotifications();
        } catch (err) {
          console.error("Failed to mark notifications as read", err);
        }
      }
    });

    document.addEventListener("click", () => {
      notifBox.classList.add("hidden");
    });

    notifBox.addEventListener('click', e => {
      e.stopPropagation();
      const target = e.target;
      if (target.classList.contains('accept-resched') || target.classList.contains('decline-resched')) {
        const appointmentId = target.dataset.appointmentId;
        const notificationId = target.dataset.notificationId;
        const response = target.classList.contains('accept-resched') ? 'accept' : 'reject';
        if (!appointmentId || !notificationId) {
          console.error('Missing appointmentId or notificationId');
          return;
        }
        handleRescheduleResponse(notificationId, appointmentId, response, target);
      }
    });

    async function handleRescheduleResponse(notificationId, appointmentId, response, clickedBtn = null) {
      try {
        if (clickedBtn) {
          const parent = clickedBtn.closest('div');
          parent.querySelectorAll('button').forEach(b => b.disabled = true);
        }

        const res = await fetch('student_reschedule_response.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            notificationId: notificationId,
            appointmentId: appointmentId,
            action: response === 'accept' ? 'accepted' : 'declined'
          })
        });

        if (!res.ok) throw new Error(`Network error: ${res.status}`);

        const data = await res.json();
        if (data.status === 'success') {
          if (clickedBtn) {
            const notifItem = clickedBtn.closest('div');
            if (response === 'accept') {
              notifItem.innerHTML = `<p class="text-green-600">✅ You accepted the reschedule.</p>`;
            } else {
              notifItem.innerHTML = `<p class="text-red-600">❌ You declined the reschedule.</p>`;
            }
          } else {
            fetchNotifications();
          }
        } else {
          alert(data.message || 'Failed to respond to reschedule.');
          if (clickedBtn) clickedBtn.disabled = false;
        }
      } catch (err) {
        alert(err.message);
        if (clickedBtn) clickedBtn.disabled = false;
      }
    }

    setInterval(fetchNotifications, 5000);
    fetchNotifications();
  </script>

  </body>
  </html>
