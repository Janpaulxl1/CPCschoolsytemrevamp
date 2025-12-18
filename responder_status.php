<?php
session_start();
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'db.php';

$result_resp = false;

if ($conn) {
    $sql_resp = "SELECT name, status FROM emergency_responders ORDER BY name ASC";
    $result_resp = $conn->query($sql_resp);
}
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
        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid #28a745;
            object-fit: cover;
        }
        .profile-info h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .profile-info p {
            color: #6c757d;
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
        .status-on-duty {
            background: #fff3cd;
            color: #856404;
        }
        .status-off-duty {
            background: #f8d7da;
            color: #721c24;
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
            .profile-section {
                flex-direction: column;
                text-align: center;
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
            <h1><i class="fas fa-user-shield"></i> Responder Status</h1>
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
            <!-- Nurse Profile -->
            <div class="profile-section">
                <img src="images/nurse.jpg" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h2>Mrs. Lorefe F. Verallo</h2>
                    <p>Nurse ID: 2022001</p>
                </div>
            </div>

            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-shield-alt"></i> Emergency Responder Status</h2>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Name</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_resp && $result_resp->num_rows > 0): ?>
                            <?php while($row = $result_resp->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td>
                                        <?php
                                        $status = htmlspecialchars($row['status']);
                                        $badgeClass = 'status-active';
                                        if (strtolower($status) === 'on duty') {
                                            $badgeClass = 'status-on-duty';
                                        } elseif (strtolower($status) === 'off duty') {
                                            $badgeClass = 'status-off-duty';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $badgeClass; ?>">
                                            <i class="fas fa-circle"></i> <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center" style="padding: 2rem; color: #6c757d;">No responders found.</td>
                            </tr>
                        <?php endif; ?>
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
    </script>
</body>
</html>
