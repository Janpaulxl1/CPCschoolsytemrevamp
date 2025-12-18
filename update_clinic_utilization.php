<?php
require_once 'db.php';

$currentYear = date("Y");

// Calculate student_registered: count of distinct students who visited in the year
$studentRegisteredQuery = $conn->query("SELECT COUNT(DISTINCT student_id) as registered FROM student_visits WHERE YEAR(visit_date) = '$currentYear'");
$studentRegistered = $studentRegisteredQuery->fetch_assoc()['registered'];

// Calculate total_visits: count of visits from student_visits table for the year
$totalVisitsQuery = $conn->query("SELECT COUNT(*) as total FROM student_visits WHERE YEAR(visit_date) = '$currentYear'");
$totalVisits = $totalVisitsQuery->fetch_assoc()['total'];

// Calculate emergency_cases: count of notifications with message starting with 'Emergency' or appointments with is_emergency=1
$emergencyNotificationsQuery = $conn->query("SELECT COUNT(*) as emerg FROM notifications WHERE YEAR(created_at) = '$currentYear' AND message LIKE 'Emergency%'");
$emergencyNotifications = $emergencyNotificationsQuery->fetch_assoc()['emerg'];

$emergencyAppointmentsQuery = $conn->query("SELECT COUNT(*) as emerg FROM appointments WHERE YEAR(appointment_time) = '$currentYear' AND is_emergency = 1");
$emergencyAppointments = $emergencyAppointmentsQuery->fetch_assoc()['emerg'];

$emergencyCases = $emergencyNotifications + $emergencyAppointments;

// Calculate health_concerns: count of appointments with status 'Confirmed' or 'Pending' (assuming these indicate health concerns)
$healthConcernsQuery = $conn->query("SELECT COUNT(*) as concerns FROM appointments WHERE YEAR(appointment_time) = '$currentYear' AND status IN ('Pending', 'Confirmed')");
$healthConcerns = $healthConcernsQuery->fetch_assoc()['concerns'];

// Update the clinic_utilization table for the current year
$updateSql = "UPDATE clinic_utilization SET total_visits = ?, return_visits = ?, emergency_cases = ?, health_concerns = ?, date_generated = NOW() WHERE year = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("iiiis", $studentRegistered, $totalVisits, $emergencyCases, $healthConcerns, $currentYear);
$stmt->execute();
$stmt->close();

echo "Clinic utilization data updated for year $currentYear.";
$conn->close();
?>
