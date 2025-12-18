<?php
require_once "db.php";

// Allow lightweight lookup by datetime/reason when notifications lack explicit id
if (isset($_GET['lookup'])) {
    $dt = trim($_GET['datetime'] ?? '');
    $reason = trim($_GET['reason'] ?? '');

    $sql = "SELECT a.id
            FROM appointments a
            WHERE (a.status IS NULL OR TRIM(a.status) NOT IN ('Rejected','Declined','Cancelled','Completed'))
              AND (? = '' OR a.appointment_time LIKE CONCAT(?, '%'))
              AND (? = '' OR LOWER(a.reason) LIKE CONCAT('%', LOWER(?), '%'))
            ORDER BY a.appointment_time DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $dt, $dt, $reason, $reason);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    header('Content-Type: application/json');
    if ($row && isset($row['id'])) {
        echo json_encode(['success' => true, 'id' => $row['id']]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

$rows = [];
$sql = "SELECT 
            a.id, 
            a.appointment_time,
            DATE(a.appointment_time) AS date, 
            DATE_FORMAT(a.appointment_time, '%h:%i %p') AS time,
            COALESCE(NULLIF(TRIM(CONCAT(s.first_name,' ',s.last_name)), ''), a.student_id) AS student_name, 
            a.reason, 
            a.status
        FROM appointments a
        LEFT JOIN students s ON (s.id = a.student_id OR s.student_id = a.student_id)
        WHERE (a.status IS NULL OR TRIM(a.status) NOT IN ('Rejected','Declined','Cancelled','Completed'))
        ORDER BY a.appointment_time DESC 
        LIMIT 300";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($rows);
?>  