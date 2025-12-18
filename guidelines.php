<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CPC Clinic</title>
    <link rel="icon" type="image" href="images/favicon.jpg">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- Header -->
<header class="bg-gradient-to-r from-orange-400 to-red-700 text-white p-4 flex justify-between items-center">
  <div class="flex items-center gap-4">
    <button onclick="document.getElementById('menu').classList.toggle('hidden')">☰</button>
    <h1 class="font-bold text-xl">Clinic Policies & Emergency Guidelines</h1>
  </div>
  <div class="absolute right-4 -top-1 z-10">
      <img src="images/Logo.png" alt="Logo" class="w-30 h-28 rounded-full">
    </div>
</header>

<!-- Sidebar -->
<div id="menu" class="absolute bg-white shadow-md w-64 p-4 hidden top-[65px] left-0 z-10">
  <h2 class="text-lg font-semibold mb-4">File Clinic Explorer</h2>
  <form id="uploadForm" action="upload_handler.php" method="POST" enctype="multipart/form-data" class="mb-4">
      <input type="file" name="file" id="fileInput" class="hidden" onchange="document.getElementById('uploadForm').submit();">
      <button type="button" onclick="document.getElementById('fileInput').click();" class="bg-gray-200 px-3 py-1 text-sm rounded">
       + Upload File
      </button>
   </form>
  <a class="block mb-2" href="nurse.php">↩️ Go Back</a>
  <ul class="space-y-2 text-sm">
    <li><a href="document_dashboard.php">📁 Documents</a></li>
    <li><a href="medication_dashboard.php">📁 Medical Supplies Monitoring</a></li>
    <li><a href="guidelines.php" onclick="event.preventDefault(); showTab('firstAid')">📁 Clinic Guidelines</a></li>
    <li><a href="Student_visitlogs.php">📁 Student Visit Logs</a></li>
  </ul>
</div>

<!-- Nurse Profile -->
<section class="bg-white p-4 mt-4 max-w-6xl mx-auto flex items-center rounded shadow">
  <img src="images/Nurse.jpg" class="w-20 h-20 rounded-full border-4 border-green-500" alt="Nurse">
  <div class="ml-4">
    <h2 class="text-xl font-semibold">Mrs. Lorefe F. Verallo</h2>
    <p class="text-gray-500 text-sm">Nurse ID: NUR-001</p>
  </div>
  <div class="ml-auto">
    <button class="relative">
      <img src="images/bell.png" class="w-6 h-6" alt="Notification">
      <span class="absolute top-0 right-0 bg-red-500 text-white text-xs px-1 rounded-full">!</span>
    </button>
  </div>
</section>

<!-- Guidelines Section -->
<section class="max-w-6xl mx-auto mt-6 bg-white p-6 rounded shadow border">
  <h3 class="text-lg font-bold mb-4 text-center">Clinic Policies & Emergency Guidelines</h3>

  <!-- Tabs -->
  <div class="flex space-x-6 font-semibold text-gray-700 mb-6 cursor-pointer">
    <div onclick="showTab('firstAid')" id="tab-firstAid" class="tab-btn flex items-center space-x-2">
      <span>📄</span><h2>First Aid Procedures</h2>
    </div>
    <div onclick="showTab('medication')" id="tab-medication" class="tab-btn flex items-center space-x-2">
      <span>📄</span><h2>Medication Guidelines</h2>
    </div>
    <div onclick="showTab('emergency')" id="tab-emergency" class="tab-btn flex items-center space-x-2">
      <span>📄</span><h2>Emergency Response Plan</h2>
    </div>
  </div>

  <!-- First Aid Tab -->
  <div id="content-firstAid" class="tab-content hidden">
    <div class="bg-green-50 p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 rounded mb-6">
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">1. Minor Cuts & Wounds</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Clean with antiseptic solution.</li>
          <li>Apply sterile bandage.</li>
          <li>Monitor for infection signs.</li>
        </ul>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">2. Fainting or Dizziness</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Lay the student down.</li>
          <li>Elevate legs to improve blood flow.</li>
          <li>Provide water and monitor recovery.</li>
        </ul>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">3. Fever Management</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Check temperature.</li>
          <li>Give paracetamol.</li>
          <li>Advise rest and hydration.</li>
        </ul>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">4. Nosebleeds</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Keep upright and tilt head forward.</li>
          <li>Pinch nostrils for 10 minutes.</li>
          <li>Apply cold compress if needed.</li>
        </ul>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">5. Asthma Attack</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Assist with inhaler.</li>
          <li>Encourage slow, deep breaths.</li>
          <li>Call emergency if no improvement.</li>
        </ul>
      </div>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-bold mb-2">6. Emergency Contact</h3>
        <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
          <li>Call parents/guardians if symptoms worsen.</li>
          <li>Activate Emergency Escort if urgent care is needed.</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Medication Guidelines Tab -->
  <div id="content-medication" class="tab-content hidden">
    <div class="bg-red-100 border border-red-300 p-6 mt-4 rounded-lg text-sm">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <h3 class="font-bold mb-2">1. General Rules:</h3>
          <ul class="space-y-1">
            <li>✅ Medications require parental/guardian consent.</li>
            <li>💊 Prescription drugs must have a doctor's prescription.</li>
            <li>📋 All medication administration must be recorded.</li>
            <li>♻️ Expired medications must be properly disposed of.</li>
          </ul>
        </div>
        <div>
          <h3 class="font-bold mb-2">2. Common Medications in the Clinic</h3>
          <ul class="space-y-1 list-disc list-inside text-gray-700">
            <li>OTC Medications (with parental consent):</li>
            <li class="ml-4">• Paracetamol – Fever/headaches</li>
            <li class="ml-4">• Antihistamines – Allergies</li>
            <li>Prescription Meds (w/ doctor's note):</li>
            <li class="ml-4">• Antibiotics – Infections</li>
            <li class="ml-4">• Inhalers – Asthma</li>
            <li>Emergency Medications:</li>
            <li class="ml-4">• Epinephrine (EpiPen) – Severe allergic reactions</li>
            <li class="ml-4">• Glucose Tablets – Low blood sugar</li>
          </ul>
        </div>
        <div>
          <h3 class="font-bold mb-2">3. Storage & Documentation:</h3>
          <ul class="space-y-1">
            <li>🔒 Medications must be stored in a locked cabinet.</li>
          </ul>
        </div>
      </div>
      <div class="mt-6">
        <h3 class="font-bold mb-2">4. Disposal of Medications:</h3>
        <p>⚠️ Expired or unused medications must be properly disposed of and documented.</p>
      </div>
      <div class="mt-6">
        <table class="w-full text-left border border-gray-300">
          <thead class="bg-gray-200">
            <tr>
              <th class="p-2 border">Date</th>
              <th class="p-2 border">Student Name</th>
              <th class="p-2 border">Medication</th>
              <th class="p-2 border">Dosage</th>
              <th class="p-2 border">Given By</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr>
              <td class="p-2 border">03/28/2025</td>
              <td class="p-2 border">John Dela Cruz</td>
              <td class="p-2 border">Paracetamol</td>
              <td class="p-2 border">500mg</td>
              <td class="p-2 border">Nurse Lorefe</td>
            </tr>
            <tr>
              <td class="p-2 border">03/30/2025</td>
              <td class="p-2 border">Jonnal Inoc</td>
              <td class="p-2 border">Neozep</td>
              <td class="p-2 border">500mg</td>
              <td class="p-2 border">Nurse Lorefe</td>
            </tr>
            <tr>
              <td class="p-2 border">03/25/2025</td>
              <td class="p-2 border">Charlene Rio</td>
              <td class="p-2 border">Diatabs</td>
              <td class="p-2 border">500mg</td>
              <td class="p-2 border">Nurse Lorefe</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Emergency Response Plan Tab -->
  <div id="content-emergency" class="tab-content hidden">
    <div class="bg-gray-100 p-6 rounded shadow">
      <h3 class="text-lg font-semibold text-red-600 mb-4 flex items-center">🚨 When an Emergency Happens:</h3>
      <ol class="list-decimal ml-6 text-sm text-gray-700 space-y-1 mb-4">
        <li>Assess the situation – Is it minor or serious?</li>
        <li>Provide first aid if possible.</li>
        <li>Call parents/guardians if needed.</li>
        <li>For serious cases, call an ambulance immediately.</li>
      </ol>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <h4 class="text-red-500 font-semibold mb-2">📌 Common Emergencies & Quick Actions:</h4>
          <ul class="list-disc ml-4 text-sm text-gray-700 space-y-1">
            <li>Severe Allergic Reaction – Use EpiPen, call ambulance.</li>
            <li>Seizure – Keep student safe, don't restrain, call for help.</li>
            <li>Fractures/Cuts – Stop bleeding, immobilize, call parents.</li>
            <li>Fainting/Dizziness – Lay student down, check breathing.</li>
            <li>Asthma Attack – Assist with inhaler, keep student calm.</li>
          </ul>
        </div>
        <div>
          <h4 class="text-red-500 font-semibold mb-2">📞 Emergency Contacts:</h4>
          <ul class="text-sm text-gray-700 space-y-1">
            <li><span class="text-red-600">★</span> Clinic Hotline: (XXX) XXX–XXXX</li>
            <li><span class="text-red-600">✔</span> Nearest Hospital: [Hospital Name] – (XXX) XXX–XXXX</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</section>

<!-- JS Tab Switcher -->
<script>
  function showTab(tab) {
    const tabs = ['firstAid', 'medication', 'emergency'];
    tabs.forEach(t => {
      document.getElementById('content-' + t).classList.add('hidden');
      document.getElementById('tab-' + t).classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
      document.getElementById('tab-' + t).classList.add('text-gray-500');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    document.getElementById('tab-' + tab).classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
    document.getElementById('tab-' + tab).classList.remove('text-gray-500');
  }

  window.addEventListener('DOMContentLoaded', () => {
    showTab('firstAid'); // default tab
  });
</script>

</body>
</html>
