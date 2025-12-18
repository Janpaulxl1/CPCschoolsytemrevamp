<?php
// ------------------ DATABASE CONNECTION ------------------
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "capstone2";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$currentYear = date("Y");

// Handle AJAX request
if (isset($_GET['fetch']) && $_GET['fetch'] == 1) {

    // Ensure current year row exists
    $result = $conn->query("SELECT * FROM clinic_utilization WHERE year = '$currentYear'");
    if ($result->num_rows == 0) {
        $conn->query("INSERT INTO clinic_utilization (year, total_visits, return_visits, emergency_cases, health_concerns, date_generated) VALUES ('$currentYear', 0, 0, 0, 0, NOW())");
    }

    // Calculate stats
    $studentRegistered = (int)$conn->query("SELECT COUNT(DISTINCT student_id) AS cnt FROM student_visits WHERE YEAR(visit_date) = '$currentYear'")->fetch_assoc()['cnt'];
    $totalVisits = (int)$conn->query("SELECT COUNT(*) AS cnt FROM student_visits WHERE YEAR(visit_date) = '$currentYear'")->fetch_assoc()['cnt'];
    $emergencyNotifications = (int)$conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE YEAR(created_at) = '$currentYear' AND message LIKE 'Emergency%'")->fetch_assoc()['cnt'];
    $emergencyAppointments = (int)$conn->query("SELECT COUNT(*) AS cnt FROM appointments WHERE YEAR(appointment_time) = '$currentYear' AND is_emergency = 1")->fetch_assoc()['cnt'];
    $emergencyCases = $emergencyNotifications + $emergencyAppointments;
    $healthConcerns = (int)$conn->query("SELECT COUNT(*) AS cnt FROM appointments WHERE YEAR(appointment_time) = '$currentYear' AND status IN ('Pending','Confirmed')")->fetch_assoc()['cnt'];

    // Update clinic_utilization
    $stmt = $conn->prepare("UPDATE clinic_utilization SET total_visits=?, return_visits=?, emergency_cases=?, health_concerns=?, date_generated=NOW() WHERE year=?");
    $stmt->bind_param("iiiis", $totalVisits, $studentRegistered, $emergencyCases, $healthConcerns, $currentYear);
    $stmt->execute();
    $stmt->close();

    // Fetch updated data
    $currentData = $conn->query("SELECT * FROM clinic_utilization WHERE year='$currentYear'")->fetch_assoc();

    // Fetch archive
    $archiveData = [];
    $res = $conn->query("SELECT * FROM clinic_utilization ORDER BY year ASC");
    while ($row = $res->fetch_assoc()) { $archiveData[] = $row; }

    echo json_encode(['current'=>$currentData,'archive'=>$archiveData]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CPC Clinic</title>
<link rel="icon" type="image" href="images/favicon.jpg">    
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: 'Inter', sans-serif; background: #b71c1c; color: white; margin:0; padding:20px; display:flex; justify-content:center; }
.report-container { background:white; color:black; border-radius:20px; padding:20px; width:95%; max-width:800px; box-shadow:0 10px 20px rgba(0,0,0,0.2); }
canvas { margin-top:20px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#f5f5f5; }
button { padding:10px 20px; margin-bottom:20px; background:#b71c1c; color:white; border:none; border-radius:5px; cursor:pointer; }
.back-btn { margin-right:10px; background:#555; }
</style>
</head>
<body>
<div class="report-container">
    <div style="margin-bottom:20px;">
        <button class="back-btn" onclick="history.back()">← Back</button>
        <button onclick="fetchData()">Refresh</button>
    </div>
    <h1>Clinic Utilization Summary – <span id="yearLabel"><?php echo $currentYear; ?></span></h1>
    <canvas id="clinicChart"></canvas>

    <h3>📦 Archive Reports</h3>
    <table id="archiveTable">
        <thead>
            <tr>
                <th>Year</th>
                <th>Student Registered</th>
                <th>Total Visits</th>
                <th>Emergency Cases</th>
                <th>Health Concerns</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
let chart;
let previousData = [0,0,0,0];

// Use PHP to dynamically get the current file path to avoid 404
const fetchURL = "<?php echo basename(__FILE__); ?>?fetch=1";

function fetchData() {
    fetch(fetchURL)
    .then(res => res.json())
    .then(data => {
        const current = data.current;
        const archive = data.archive;

        document.getElementById('yearLabel').innerText = current.year;

        const chartData = [current.total_visits, current.return_visits, current.emergency_cases, current.health_concerns];
        const bgColors = chartData.map((val,i) => val > previousData[i] ? '#ff3d00' : ['#2196F3','#4CAF50','#FF9800','#9E9E9E'][i]);
        previousData = chartData.slice();

        const ctx = document.getElementById('clinicChart').getContext('2d');
        if(chart) {
            chart.data.datasets[0].data = chartData;
            chart.data.datasets[0].backgroundColor = bgColors;
            chart.update();
        } else {
            chart = new Chart(ctx, {
                type:'bar',
                data:{
                    labels:['Student Registered','Total Visits','Emergency Cases','Health Concerns'],
                    datasets:[{label:'Current Year', data:chartData, backgroundColor:bgColors, borderRadius:8}]
                },
                options:{responsive:true, scales:{y:{beginAtZero:true, title:{display:true,text:'Count'}}, x:{title:{display:true,text:'Metric'}}}}
            });
        }

        // Update archive table
        const tbody = document.querySelector('#archiveTable tbody');
        tbody.innerHTML='';
        archive.forEach(row=>{
            tbody.innerHTML+=`<tr>
                <td>${row.year}</td>
                <td>${row.total_visits}</td>
                <td>${row.return_visits}</td>
                <td>${row.emergency_cases}</td>
                <td>${row.health_concerns}</td>
            </tr>`;
        });
    })
    .catch(err=>console.error(err));
}

// Initial fetch
fetchData();
// Refresh every 5 seconds
setInterval(fetchData, 5000);
</script>
</body>
</html>
