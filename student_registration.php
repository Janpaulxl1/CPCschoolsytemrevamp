<?php
session_start();
require_once "db.php";

// Ensure responder_status exists
$conn->query("ALTER TABLE students
    ADD COLUMN IF NOT EXISTS is_responder TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS responder_status ENUM('Active','On Duty','Off Duty') DEFAULT 'Off Duty'
");

// Fetch Sections
$sections = [];
$res = $conn->query("SELECT id, name, semester FROM sections ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sections[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CPC Clinic - Student Registration</title>
  <link rel="icon" type="image/png" href="images/favicon.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poltawski+Nowy:wght@400;700&display=swap" rel="stylesheet">

  <style>
    /* ------- Reset & base ------- */
    :root{
      --accent: #b71c1c;
      --card-bg: rgba(255,255,255,0.16);
      --card-border: rgba(255,255,255,0.28);
      --input-bg: #f7f7f7;
      --text-dark: #222;
      --muted: #6b6b6b;
      --radius: 12px;
    }

    * { box-sizing: border-box; }
    html,body { height: 100%; }
    body {
      margin: 0;
      height: 100vh;
      overflow: hidden;             /* lock background so only card scrolls */
      font-family: 'Inter', 'Poltawski Nowy', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg, #fff 0%, #fff 40%);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      color: #0b0b0b;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      position: relative;
    }

    /* ------- Decorative background shapes ------- */
    .bg-white-tilt, .bg-red-tilt, .bg-overlay {
      position: absolute;
      pointer-events: none;
      z-index: -3;
    }
    .bg-white-tilt {
      top: -28%;
      left: -28%;
      width: 160%;
      height: 160%;
      background: #ffffff;
      transform: rotate(25deg);
      filter: drop-shadow(0 0 40px rgba(0,0,0,0.05));
    }
    .bg-red-tilt {
      top: -40%;
      right: -38%;
      width: 160%;
      height: 140%;
      background: var(--accent);
      transform: rotate(-35deg);
      z-index: -2;
      opacity: 0.95;
      mix-blend-mode: multiply;
    }
    .bg-overlay {
      inset: 0;
      background: rgba(0,0,0,0.06);
      z-index: -1;
    }

    /* ------- Page container ------- */
    .page-wrap {
      width: 100%;
      max-width: 1200px;
      display: flex;
      gap: 32px;
      align-items: flex-start;
      justify-content: center;
    }

    /* ------- Header (modernized) ------- */
    .header {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 6px;
      width: 100%;
      justify-content: center;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .brand-logo {
      width: 74px;
      height: 74px;
      border-radius: 12px;
      overflow: hidden;
      flex-shrink: 0;
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
      border: 1px solid rgba(0,0,0,0.04);
    }

    .brand-logo img { width: 64px; height: auto; display:block; }

    .brand-text {
      display:flex;
      flex-direction: column;
      line-height: 1;
    }

    .brand-title {
      font-family: 'Poltawski Nowy', serif;
      font-weight: 700;
      color: var(--text-dark);
      font-size: 20px;
      letter-spacing: -0.2px;
    }

    .brand-sub {
      color: var(--muted);
      font-size: 13px;
      margin-top: 2px;
    }

    /* ------- Registration card (center) ------- */
    .registration-card {
      width: 100%;
      max-width: 850px;            /* professional wider card */
      background: var(--card-bg);
      border-radius: calc(var(--radius) + 4px);
      padding: 28px;
      box-shadow: 0 18px 50px rgba(18,18,18,0.08);
      border: 1px solid var(--card-border);
      backdrop-filter: blur(10px);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      gap: 14px;
      max-height: 85vh;            /* card scrolls internally */
    }

    .card-inner {
      overflow-y: auto;
      padding-right: 6px; /* room for scrollbar */
      scroll-behavior: smooth;
    }

    /* Header inside card */
    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .card-title {
      font-size: 22px;
      font-weight: 700;
      color: #fff;
      margin: 0;
      text-shadow: 0 2px 8px rgba(0,0,0,0.35);
    }

    .card-sub {
      font-size: 13px;
      color: rgba(255,255,255,0.9);
      opacity: 0.95;
    }

    /* ------- Form grid: 2 columns ------- */
    form { width: 100%; }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px 20px;
      margin-top: 18px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    label {
      font-weight: 600;
      font-size: 0.95rem;
      color: rgba(255,255,255,0.95);
      margin-bottom: 8px;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="password"],
    input[type="date"],
    select,
    input[type="file"] {
      background: var(--input-bg);
      border: 1px solid rgba(0,0,0,0.06);
      padding: 11px 12px;
      border-radius: 10px;
      font-size: 0.98rem;
      color: var(--text-dark);
      transition: box-shadow .15s ease, transform .08s ease;
      outline: none;
    }

    input:focus, select:focus {
      box-shadow: 0 6px 20px rgba(0,0,0,0.08), 0 0 0 3px rgba(183,28,28,0.08);
      transform: translateY(-1px);
    }

    /* Make file input readable */
    input[type="file"] {
      padding: 8px 10px;
      font-size: 0.92rem;
    }

    /* Full-width items */
    .full-span { grid-column: 1 / -1; }

    /* Action row */
    .actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 12px;
      align-items: center;
    }

    .register-button {
      background: #fff;
      color: var(--accent);
      border: none;
      padding: 12px 16px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 1.02rem;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(183,28,28,0.08);
      transition: transform .12s ease, box-shadow .12s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .register-button:hover { transform: translateY(-3px); box-shadow: 0 22px 45px rgba(183,28,28,0.09); }
    .register-button:disabled { opacity: .7; transform: none; cursor: not-allowed; }

    .secondary-link {
      text-align: center;
      color: rgba(255,255,255,0.95);
      font-size: 0.95rem;
      text-decoration: underline;
    }

    .muted-note {
      color: rgba(255,255,255,0.85);
      font-size: 0.92rem;
    }

    /* spinner */
    .spinner {
      width: 18px;
      height: 18px;
      border: 3px solid rgba(0,0,0,0.18);
      border-top-color: rgba(0,0,0,0.6);
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ------- Responsive adjustments ------- */
    @media (max-width: 980px) {
      .form-grid { grid-template-columns: 1fr 1fr; }
      .registration-card { max-width: 720px; padding: 22px; }
      .brand-title { font-size: 18px; }
    }

    @media (max-width: 700px) {
      body { padding: 18px; }
      .page-wrap { padding: 0; gap: 12px; }
      .brand { justify-content: center; }
      .form-grid { grid-template-columns: 1fr; }
      .actions { grid-template-columns: 1fr; }
      .registration-card { max-width: 100%; padding: 18px; }
    }

    /* small niceties for terrible browsers */
    ::-webkit-scrollbar { width: 9px; height:9px; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 12px; }
    ::-webkit-scrollbar-track { background: transparent; }
  </style>

  <script>
    // On load: add small entrance (no heavy animations)
    window.addEventListener('DOMContentLoaded', () => {
      const card = document.querySelector('.registration-card');
      if (card) card.style.transform = 'translateY(0)';
    });

    function handleRegister(e) {
      const btn = document.getElementById("registerBtn");
      // fields used by server-side - preserved
      const fields = [
        "studentID","firstName","lastName","birthday","gender",
        "email","phone","homeAddress","guardiansName","guardiansAddress",
        "contactNo","relationship","course","yearLevel","section","createPassword"
      ];

      let hasError = false;
      for (let id of fields) {
        const el = document.getElementById(id);
        if (!el) continue;
        // only validate required ones
        if (el.hasAttribute('required') && String(el.value).trim() === "") {
          el.style.animation = 'shake 0.25s';
          setTimeout(()=> el.style.animation = '', 300);
          el.scrollIntoView({behavior:'smooth', block:'center'});
          hasError = true;
        }
      }

      if (hasError) {
        e.preventDefault();
        return false;
      }

      // show spinner, disable button
      if (btn) {
        btn.innerHTML = '<div class="spinner" aria-hidden="true"></div> Registering...';
        btn.disabled = true;
      }
    }
  </script>
</head>
<body>

  <!-- Decorative backgrounds -->
  <div class="bg-white-tilt" aria-hidden="true"></div>
  <div class="bg-red-tilt" aria-hidden="true"></div>
  <div class="bg-overlay" aria-hidden="true"></div>

  <div class="page-wrap" role="main" aria-labelledby="pageTitle">
    <div style="width:100%; display:flex; flex-direction:column; align-items:center; gap:12px;">
      <!-- Modern header -->
      <header class="header" role="banner">
        <div class="brand" aria-hidden="false">   
        </div>
      </header>

      <!-- Registration Card -->
      <section class="registration-card" aria-labelledby="regTitle">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
          <div>
            <h2 id="regTitle" class="card-title">Student Registration</h2>
            <div class="card-sub muted-note">Fill the form below.</div>
          </div>
        </div>

        <!-- Scrollable inner area -->
        <div class="card-inner" role="region" aria-label="Registration form area">
          <form action="physical_assessment.php" method="POST" enctype="multipart/form-data" onsubmit="handleRegister(event)">
            <div class="form-grid">

              <div class="form-group">
                <label for="studentID">Student ID</label>
                <input id="studentID" name="studentID" type="text" placeholder="Enter your student ID" required>
              </div>

              <div class="form-group">
                <label for="firstName">First Name</label>
                <input id="firstName" name="firstName" type="text" placeholder="Enter your first name" required>
              </div>

              <div class="form-group">
                <label for="middleName">Middle Name</label>
                <input id="middleName" name="middleName" type="text" placeholder="Enter your middle name">
              </div>

              <div class="form-group">
                <label for="lastName">Last Name</label>
                <input id="lastName" name="lastName" type="text" placeholder="Enter your last name" required>
              </div>

              <div class="form-group">
                <label for="birthday">Birthday</label>
                <input id="birthday" name="birthday" type="date" required>
              </div>

              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                  <option value="" disabled selected>Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>

              <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" placeholder="Enter your email" required>
              </div>

              <div class="form-group">
                <label for="phone">Phone No</label>
                <input id="phone" name="phone" type="tel" placeholder="Enter your phone number" required>
              </div>

              <div class="form-group">
                <label for="homeAddress">Home Address</label>
                <input id="homeAddress" name="homeAddress" type="text" placeholder="Enter your home address" required>
              </div>

              <div class="form-group">
                <label for="guardiansName">Guardian's Name</label>
                <input id="guardiansName" name="guardiansName" type="text" placeholder="Enter guardian's name" required>
              </div>

              <div class="form-group">
                <label for="guardiansAddress">Guardian's Home Address</label>
                <input id="guardiansAddress" name="guardiansAddress" type="text" placeholder="Enter guardian's address" required>
              </div>

              <div class="form-group">
                <label for="contactNo">Emergency Contact</label>
                <input id="contactNo" name="contactNo" type="tel" placeholder="Enter emergency contact" required>
              </div>

              <div class="form-group">
                <label for="relationship">Relationship to Student</label>
                <input id="relationship" name="relationship" type="text" placeholder="Enter relationship" required>
              </div>

              <div class="form-group">
                <label for="course">Course</label>
                <select id="course" name="course" required>
                  <option value="" disabled selected>Select course</option>
                  <option value="BSIT">BSIT</option>
                  <option value="BSED">BSED</option>
                  <option value="BEED">BEED</option>
                  <option value="BSHM">BSHM</option>
                </select>
              </div>

              <div class="form-group">
                <label for="yearLevel">Year Level</label>
                <select id="yearLevel" name="yearLevel" required>
                  <option value="" disabled selected>Select year level</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                </select>
              </div>

              <div class="form-group">
                <label for="section">Section</label>
                <select id="section" name="section" required>
                  <option value="" disabled selected>Select section</option>
                  <?php foreach ($sections as $sec): ?>
                    <option value="<?= htmlspecialchars($sec['id']) ?>"><?= htmlspecialchars($sec['name']) ?> - <?= htmlspecialchars($sec['semester']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="createPassword">Create Password</label>
                <input id="createPassword" name="createPassword" type="password" placeholder="Create a password" required>
              </div>

              <div class="form-group">
                <label for="profilePicture">Profile Picture</label>
                <input id="profilePicture" name="profilePicture" type="file" accept="image/*">
              </div>

              <div class="form-group">
                <label for="responderType">Emergency Responder</label>
                <select id="responderType" name="responderType" required>
                  <option value="" disabled selected>Select type</option>
                  <option value="student">Student</option>
                  <option value="responder">Responder</option>
                </select>
              </div>

              <!-- Extra spacing row: set register button to full width -->
              <div class="form-group full-span" style="margin-top:6px;">
                <div class="actions" style="align-items:center;">
                  <button id="registerBtn" type="submit" class="register-button" aria-label="Next: proceed to physical assessment">Next</button>
                </div>
              </div>

            </div> <!-- /.form-grid -->
          </form>

          <div style="margin-top:12px; display:flex; justify-content:center;">
            <div class="secondary-link">
              Already have an account? <a href="index.html" style="color:inherit; text-decoration:underline;">Login</a>
            </div>
          </div>

        </div> <!-- /.card-inner -->
      </section>
    </div>
  </div>

</body>
</html>
