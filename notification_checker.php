<?php
require_once "db.php";

$rows = [];
$sql = "SELECT a.id, a.appointment_time, a.reason, a.status, a.is_emergency,
               COALESCE(NULLIF(TRIM(CONCAT(s.first_name,' ',s.last_name)), ''), a.student_id) AS student_name
        FROM appointments a
        LEFT JOIN students s ON (s.id = a.student_id OR s.student_id = a.student_id)
        WHERE a.status IN ('Pending', 'Pending Nurse Confirmation')
           OR LOWER(a.status) LIKE 'pending%'
        ORDER BY a.appointment_time ASC
        LIMIT 100";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($rows);

$conn->close();
?>