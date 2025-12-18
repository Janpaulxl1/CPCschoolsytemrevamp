<?php
require_once 'db.php';

// Fetch all completed appointments that don't have medicine entries
$sql = "SELECT a.id as appointment_id, sv.med_id, sv.dosage, sv.quantity, sv.action_taken, m.name as medicine_name
        FROM appointments a
        LEFT JOIN appointment_medications am ON a.id = am.appointment_id
        JOIN student_visits sv ON a.student_id = sv.student_id AND a.appointment_time = sv.visit_date AND a.reason = sv.reason
        LEFT JOIN medicines m ON sv.med_id = m.name
        WHERE a.status = 'Completed' AND am.id IS NULL AND sv.status = 'Completed' AND sv.med_id IS NOT NULL";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $medicine_name = !empty($row['medicine_name']) ? $row['medicine_name'] : $row['med_id'];
        $insert_sql = "INSERT INTO appointment_medications (appointment_id, medicine_name, dosage, quantity, action_taken) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issss", $row['appointment_id'], $medicine_name, $row['dosage'], $row['quantity'], $row['action_taken']);
        $stmt->execute();
        $stmt->close();
    }
    echo "Updated existing appointments with medicine data.";
} else {
    echo "No updates needed or no matching records found.";
}

$conn->close();
?>
