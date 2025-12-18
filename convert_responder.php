<?php
require_once "db.php";

// ✅ Ensure responder_status exists
$conn->query("ALTER TABLE students
    ADD COLUMN IF NOT EXISTS is_responder TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS responder_status ENUM('Active','On Duty','Off Duty') DEFAULT 'Off Duty'
");

// Handle convert action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['convert'])) {
    $student_id = $_POST['student_id'];

    // Update students table
    $update_stmt = $conn->prepare("UPDATE students SET is_responder = 1, responder_status = 'Active' WHERE student_id = ?");
    $update_stmt->bind_param("s", $student_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Get student details for emergency_responders
    $select_stmt = $conn->prepare("SELECT first_name, last_name, phone FROM students WHERE student_id = ? LIMIT 1");
    $select_stmt->bind_param("s", $student_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $responder_name = $row['first_name'] . " " . $row['last_name'];
        $phone = $row['phone'];

        // Check if already in emergency_responders
        $check_stmt = $conn->prepare("SELECT id FROM emergency_responders WHERE name = ? LIMIT 1");
        $check_stmt->bind_param("s", $responder_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows == 0) {
            // Insert into emergency_responders
            $insert_stmt = $conn->prepare("INSERT INTO emergency_responders (name, status, phone) VALUES (?, 'Active', ?)");
            $insert_stmt->bind_param("ss", $responder_name, $phone);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    $select_stmt->close();

    // Redirect to refresh page
    header("Location: convert_responder.php");
    exit;
}

// Handle unconvert action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['unconvert'])) {
    $student_id = $_POST['student_id'];

    // Update students table
    $update_stmt = $conn->prepare("UPDATE students SET is_responder = 0, responder_status = 'Off Duty' WHERE student_id = ?");
    $update_stmt->bind_param("s", $student_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Get student details for emergency_responders
    $select_stmt = $conn->prepare("SELECT first_name, last_name FROM students WHERE student_id = ? LIMIT 1");
    $select_stmt->bind_param("s", $student_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $responder_name = $row['first_name'] . " " . $row['last_name'];

        // Delete from emergency_responders
        $delete_stmt = $conn->prepare("DELETE FROM emergency_responders WHERE name = ?");
        $delete_stmt->bind_param("s", $responder_name);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    $select_stmt->close();

    // Redirect to refresh page
    header("Location: convert_responder.php");
    exit;
}

// Fetch students based on search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$students = [];

$query = "SELECT id, student_id, first_name, last_name, is_responder FROM students";

if (!empty($search)) {
    $query .= " WHERE (student_id LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
} else {
    $query .= " WHERE is_responder = 1";
}

$query .= " ORDER BY last_name ASC";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPC Clinic</title>
    <link rel="icon" type="image" href="images/favicon.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background: white;
            min-height: 100vh;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }
        #menuBtn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.3s ease;
            margin-right: 1rem;
        }
        #menuBtn:hover {
            background-color: rgba(44, 58, 80, 0.1);
        }
        .header h1 {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
            text-align: center;
        }
        .header h1 i {
            margin-right: 10px;
            color: white;
        }
        .header-actions {
            display: flex;
            gap: 10px;
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
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ecf0f1;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .search-section {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        thead {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        tbody tr {
            transition: all 0.3s ease;
        }
        tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-student {
            background: #cce5ff;
            color: #004085;
        }
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .action-btn:disabled {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
        }
        .footer {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            .header-actions {
                width: 100%;
                justify-content: center;
            }
            .main-content {
                padding: 0 1rem;
            }
            .dashboard-card {
                padding: 1rem;
            }
            .search-section {
                flex-direction: column;
            }
            .table-container {
                font-size: 0.9rem;
            }
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <button id="menuBtn"><i class="fas fa-bars"></i></button>
        <div class="header-content">
            <h1><i class="fas fa-shield-alt"></i> Emergency Responder Management</h1>
        </div>
    </header>

    <!-- Sidebar -->
        <nav id="sidebar">
        <a href="nurse.php"><i class="fas fa-arrow-left"></i> Back</a>
        <h2>File Clinic Explorer</h2>
        <details>
            <summary>📁 Documents</summary>
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
        <a href="responder_status.php"><i class="fas fa-user-shield"></i> Responder Status</a>
        <a href="nurse_reset_password.php"><i class="fas fa-key"></i> Reset User Password</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-users"></i> Student List</h2>
                <div class="stats">
                    <span class="status-badge status-active">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        $responder_count = 0;
                        foreach ($students as $student) {
                            if ($student['is_responder'] == 1) $responder_count++;
                        }
                        echo $responder_count . ' Responders';
                        ?>
                    </span>
                </div>
            </div>

            <form method="GET" class="search-section">
                <input type="text" name="search" class="search-input" placeholder="Search students by ID or name..." value="">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i>Search</button>
            </form>

            <div class="table-container">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i>Student ID</th>
                            <th><i class="fas fa-user"></i>Name</th>
                            <th><i class="fas fa-cogs"></i>Status</th>
                            <th><i class="fas fa-bolt"></i>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($student['student_id']) ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-user-circle" style="color: #3498db; font-size: 1.2em;"></i>
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($student['is_responder'] == 1): ?>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-shield-alt"></i>Active Responder
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-student">
                                            <i class="fas fa-user"></i>Student
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student['is_responder'] == 1): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                            <button type="submit" name="unconvert" class="action-btn" style="background: #28a745; color: white;" onclick="return confirm('Are you sure you want to unconvert this responder back to student?');">
                                                <i class="fas fa-check"></i>Already Responder
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                            <button type="submit" name="convert" class="action-btn" style="background: #dc2626; color: white;">
                                                <i class="fas fa-plus"></i>Convert to Responder
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 School Health Clinic & Management System. All rights reserved.</p>
        <p>Ensuring safety and preparedness for our community.</p>
    </footer>

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

        // Update stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            let responderCount = 0;
            rows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(3) .status-badge');
                if (statusCell && statusCell.textContent.includes('Active Responder')) {
                    responderCount++;
                }
            });

            const statsElement = document.querySelector('.stats .status-badge');
            if (statsElement) {
                const iconHtml = statsElement.querySelector('i').outerHTML;
                statsElement.innerHTML = iconHtml + ' ' + responderCount + ' Responders';
            }
        });
    </script>
</body>
</html>