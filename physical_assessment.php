<?php
session_start();
require_once "db.php";

// ✅ Ensure responder_status exists
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['register'])) {
    // Store form data from student_registration.php in session
    $_SESSION['registration_data'] = $_POST;
    // Handle profile picture upload and store path in session
    if (!empty($_FILES['profilePicture']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = uniqid("profile_") . "_" . basename($_FILES["profilePicture"]["name"]);
        $profile_picture = $targetDir . $filename;
        move_uploaded_file($_FILES["profilePicture"]["tmp_name"], $profile_picture);
        $_SESSION['registration_data']['profilePicture'] = $profile_picture;
    }
    // Redirect to physical_assessment.php to display the form
    header("Location: physical_assessment.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    // Check if registration data exists in session
    if (!isset($_SESSION['registration_data'])) {
        echo "No registration data found. Please start from the registration page.";
        exit;
    }

    $data = $_SESSION['registration_data'];

    $student_id       = $data['studentID'];
    $first_name       = $data['firstName'];
    $middle_name      = $data['middleName'];
    $last_name        = $data['lastName'];
    $birthday         = $data['birthday'];
    $gender           = $data['gender'];
    $email            = $data['email'];
    $phone            = $data['phone'];
    $home_address     = $data['homeAddress'];
    $guardian_name    = $data['guardiansName'];
    $guardian_address = $data['guardiansAddress'];
    $emergency_contact= $data['contactNo'];
    $relationship     = $data['relationship'];
    $course           = $data['course'];
    $year_level       = $data['yearLevel'];
    $section_id       = $data['section'];
    $password         = password_hash($data['createPassword'], PASSWORD_BCRYPT);

    // ✅ Emergency Responder dropdown
    $is_responder = $data['responderType'] === 'responder' ? 1 : 0;
    $responder_status = "Active"; // default for responders

    // Profile picture
    $profile_picture = isset($data['profilePicture']) ? $data['profilePicture'] : null;

    // Get section name
    $secStmt = $conn->prepare("SELECT name FROM sections WHERE id = ? LIMIT 1");
    $secStmt->bind_param("i", $section_id);
    $secStmt->execute();
    $secRes = $secStmt->get_result();
    $section_name = "";
    if ($secRes && $row = $secRes->fetch_assoc()) {
        $section_name = $row['name'];
    }
    $secStmt->close();

    // Insert into students
    $stmt = $conn->prepare("INSERT INTO students
    (student_id, first_name, middle_name, last_name, birthday, gender, email, phone, home_address,
    guardian_name, guardian_address, emergency_contact, relationship, course, year_level, section, password, profile_picture, section_id,
    is_responder, responder_status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->bind_param(
        "ssssssssssssssssssiis",
        $student_id,
        $first_name,
        $middle_name,
        $last_name,
        $birthday,
        $gender,
        $email,
        $phone,
        $home_address,
        $guardian_name,
        $guardian_address,
        $emergency_contact,
        $relationship,
        $course,
        $year_level,
        $section_name,
        $password,
        $profile_picture,
        $section_id,
        $is_responder,
        $responder_status
    );

    if ($stmt->execute()) {
        // If registered as responder, add to emergency_responders table
        if ($is_responder == 1) {
            $responder_name = $first_name . " " . $last_name;
            $responder_stmt = $conn->prepare("INSERT INTO emergency_responders (name, status, phone) VALUES (?, ?, ?)");
            $responder_stmt->bind_param("sss", $responder_name, $responder_status, $phone);
            $responder_stmt->execute();
            $responder_stmt->close();
        }
        // Clear session data
        unset($_SESSION['registration_data']);
        echo "success";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>CPC Clinic - Physical Assessment</title>
    <link rel="icon" type="image" href="images/favicon.jpg">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    /* Page background so PDF white area stands out */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #e8e8e8;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* Printable container sized to A4 minus outer margin */
    #printArea {
      width: 100%;
      max-width: 210mm;
      min-height: auto;
      margin: 10mm auto;
      padding: 12mm;
      background: #fff;
      box-sizing: border-box;
      border: 1px solid #111;
      color: #000;
      transform: none;
    }

    .avoid-break { page-break-inside: avoid; break-inside: avoid; }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .header img { width: 78px; height: auto; display: block; }
    .header-text {
      flex: 1 1 auto;
      text-align: center;
      font-size: 13px;
      line-height: 1.2;
    }

    h3 {
      text-align: center;
      margin: 10px 0 12px;
      font-size: 16px;
      text-decoration: underline;
    }

    .section { margin: 10px 0; font-size: 13px; }
    label { display: block; font-weight: 600; margin-bottom: 6px; }

    input[type="text"], input[type="date"], input[type="email"], select {
      width: 100%;
      padding: 6px 8px;
      margin-bottom: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
      box-sizing: border-box;
      font-size: 13px;
    }

    .inline-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .inline-group .field { flex: 1 1 150px; min-width: 120px; }

    .checkbox-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }
    .checkbox-row input[type="text"] { flex: 1 1 180px; min-width: 140px; }

    /* Action buttons */
    .action-buttons {
      display:flex;
      gap:10px;
      justify-content:flex-end;
      margin: 12px auto;
      max-width: 190mm;
      padding: 8px 12mm;
      box-sizing: border-box;
    }
    .action-buttons button {
      padding: 8px 14px;
      border: none;
      background: #0b74d1;
      color: #fff;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
    }
    .action-buttons button:hover { background:#095ea6; }

    .register-button {
      background-color: #fff;
      color: #b71c1c;
      padding: 8px 14px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      font-weight: bold;
    }

    .register-button:hover { background:#f2f2f2; }

    /* Hide action buttons & page outline when printing */
    @media print {
      .action-buttons { display: none; }
      body { background: #fff; }
    }

    .page-break-after { page-break-after: always; }
  </style>
</head>
<body>
  <form action="physical_assessment.php" method="POST" enctype="multipart/form-data">
    <div id="printArea" role="main" aria-label="Student Physical Assessment Form">

    <!-- Header -->
    <div class="header avoid-break">
      <img src="images/cpc.jpg" alt="Left logo" crossorigin="anonymous">
      <div class="header-text">
        Republic of the Philippines<br>
        Province of Cebu<br>
        Municipality of Cordova<br>
        <strong>CORDOVA PUBLIC COLLEGE</strong><br>
        Gabi, Cordova, Cebu<br>
        PATIENT INFORMATION SHEET
      </div>
      <img src="images/municipal.jpg" alt="Right logo" crossorigin="anonymous">
    </div>

    <h3 class="avoid-break">STUDENT PHYSICAL ASSESSMENT FORM<br><small style="font-weight:600">(TO BE FILLED-UP BY STUDENTS)</small></h3>

    <!-- Personal Info -->
    <div class="section avoid-break">
      <div class="inline-group">
        <div class="field"><label for="name">Name</label><input id="name" type="text" placeholder="Enter name"></div>
        <div class="field"><label for="age">Age</label><input id="age" type="text" placeholder="Age"></div>
        <div class="field"><label for="sex">Sex</label>
          <select id="sex"><option value="">-- Select --</option><option>Male</option><option>Female</option></select>
        </div>
        <div class="field"><label for="civil">Civil Status</label><input id="civil" type="text" placeholder="Civil status"></div>
      </div>

      <label for="address">Address</label>
      <input id="address" type="text" placeholder="Complete address">

      <div class="inline-group">
        <div class="field"><label for="dob">Date of Birth</label><input id="dob" type="date"></div>
        <div class="field"><label for="pob">Place of Birth</label><input id="pob" type="text" placeholder="Place of birth"></div>
      </div>

      <div class="inline-group">
        <div class="field"><label for="nat">Nationality</label><input id="nat" type="text" placeholder="Nationality"></div>
        <div class="field"><label for="rel">Religion</label><input id="rel" type="text" placeholder="Religion"></div>
        <div class="field"><label for="course">Course</label><input id="course" type="text" placeholder="Course"></div>
      </div>

      <label for="email">Email Address</label>
      <input id="email" type="email" placeholder="Email address">

      <label for="fb">Facebook Account</label>
      <input id="fb" type="text" placeholder="Facebook account">
    </div>

    <!-- Covid Info -->
    <div class="section avoid-break">
      <h4 style="margin:6px 0 8px;">Covid Vaccine Info</h4>
      <div class="inline-group">
        <div class="field"><label for="dose1">1st Dose Date</label><input id="dose1" type="date"></div>
        <div class="field"><label for="dose2">2nd Dose Date</label><input id="dose2" type="date"></div>
        <div class="field"><label for="booster1">1st Booster Date</label><input id="booster1" type="date"></div>
        <div class="field"><label for="booster2">2nd Booster Date</label><input id="booster2" type="date"></div>
      </div>
    </div>

    <!-- Family Info -->
    <div class="section avoid-break">
      <div class="inline-group">
        <div class="field"><label for="spouse">Spouse Name</label><input id="spouse" type="text"></div>
        <div class="field"><label for="father">Father's Name</label><input id="father" type="text"></div>
      </div>
      <div class="inline-group">
        <div class="field"><label for="father_contact">Father's Contact No.</label><input id="father_contact" type="text"></div>
        <div class="field"><label for="mother">Mother's Name</label><input id="mother" type="text"></div>
      </div>
      <div class="inline-group">
        <div class="field"><label for="mother_contact">Mother's Contact No.</label><input id="mother_contact" type="text"></div>
        <div class="field"><label for="complete_address">Complete Address</label><input id="complete_address" type="text"></div>
      </div>
      <div class="inline-group">
        <div class="field"><label for="emergency_person">Emergency Contact Person</label><input id="emergency_person" type="text"></div>
        <div class="field"><label for="relationship">Relationship</label><input id="relationship" type="text"></div>
      </div>
      <div class="inline-group">
        <div class="field"><label for="emergency_no">Emergency Contact No.</label><input id="emergency_no" type="text"></div>
      </div>
    </div>

    <!-- Yes/No Section -->
    <div class="section avoid-break">
  <h4 style="margin:6px 0 8px;">Personal Info Checkboxes</h4>
  <div class="checkbox-row"><label><input type="checkbox" id="child"> Do you have a child?</label><input type="text" placeholder="How many?"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="solo"> Are you a solo parent?</label><input type="text" placeholder="Since when?"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="preg"> Are you pregnant?</label><input type="text" placeholder="EDC/EDD, GPTALM"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="pwd"> Are you PWD?</label><input type="text" placeholder="Which part of the body? If no, when accident?"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="inborn"> Inborn?</label><input type="text" placeholder="Details"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="indig"> Are you part of indigenous people?</label><input type="text" placeholder="What group/Culture?"></div>
  <div class="checkbox-row"><label><input type="checkbox" id="otherp"> Other persons/Contact:</label><input type="text" placeholder="Enter here"></div>

  <!-- Added allergies field -->
  <div class="checkbox-row">
    <label><input type="checkbox" id="allergiesChk"> Do you have any allergies?</label>
    <input type="text" id="allergiesText" placeholder="Enter allergies if any">
  </div>
</div>

  </div><!-- end printArea -->
  </form>

  <!-- Action buttons -->
  <div class="action-buttons" role="toolbar" aria-label="Actions">
    <button onclick="window.location.href='student_registration.php'">⬅️ Back</button>
    <button type="button" class="register-button" onclick="registerStudent()">Register</button>
  </div>

  <div class="login-link" style="text-align: center; margin-top: 1rem; color: white;">
    Already have an account?
    <a href="index.html" style="color: #f8f8f8; text-decoration: underline; font-size: 1rem;">Login</a>
  </div>

  <!-- html2pdf -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    function registerStudent() {
      if (confirm('Are you sure you want to register?')) {
        // Send AJAX request to register
        fetch('physical_assessment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'register=true'
        })
        .then(response => response.text())
        .then(data => {
          if (data.includes('success')) {
            alert('Registration successful!');
            window.location.href = 'studentfile_dashboard.php';
          } else {
            alert('Registration failed: ' + data);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred during registration.');
        });
      }
    }

    function waitForImages(parent) {
      const imgs = parent.querySelectorAll('img');
      const promises = [];
      imgs.forEach(img => {
        if (img.complete && img.naturalWidth !== 0) return;
        promises.push(new Promise(resolve => {
          img.addEventListener('load', resolve, { once: true });
          img.addEventListener('error', resolve, { once: true });
        }));
      });
      return Promise.all(promises);
    }

    document.getElementById('printBtn').addEventListener('click', () => window.print());

    document.getElementById('download').addEventListener('click', async function () {
      const element = document.getElementById('printArea');
      await waitForImages(element);
      const opt = {
        margin: [8, 8, 8, 8],
        filename: 'Student_Physical_Assessment.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
      };
      const outline = element.style.border;
      element.style.border = 'none';
      try {
        await html2pdf().set(opt).from(element).save();
      } catch (err) {
        console.error('PDF export error:', err);
        alert('PDF export failed — see console for details.');
      } finally {
        element.style.border = outline || '1px solid #111';
      }
    });
  </script>
</body>
</html>
