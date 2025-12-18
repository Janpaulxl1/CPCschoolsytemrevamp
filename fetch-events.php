<?php
require_once 'db.php';

$sql = "SELECT id, title, note, start, end, reminder_minutes FROM events";
$result = $conn->query($sql);

$events = [];
while ($row = $result->fetch_assoc()) {
    $eventClass = strpos($row['title'], 'Appointment:') === 0 ? 'appointment' : '';
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['start'],
        'end'   => $row['end'],
        'allDay' => false,
        'className' => $eventClass,
        'extendedProps' => [
            'note' => $row['note'],
            'reminder_minutes' => $row['reminder_minutes']
        ]
    ];
}

// Add appointments as events only if not already in events table
$appt_sql = "SELECT DISTINCT a.id, a.appointment_time, a.reason, s.first_name, s.last_name
             FROM appointments a
             JOIN students s ON a.student_id = s.id
             WHERE ((a.status IN ('Pending', 'Confirmed', 'Accepted') AND a.appointment_time >= NOW()) OR a.status IN ('Follow-up', 'Pending Confirmation'))
             AND NOT EXISTS (SELECT 1 FROM events e WHERE e.appointment_id = a.id)";
$appt_result = $conn->query($appt_sql);
while ($appt = $appt_result->fetch_assoc()) {
    $student_name = trim($appt['first_name'] . ' ' . $appt['last_name']);
    $title = 'Appointment: ' . $student_name . ' - ' . $appt['reason'];
    $events[] = [
        'id' => 'appt_' . $appt['id'],
        'title' => $title,
        'start' => $appt['appointment_time'],
        'end' => date('Y-m-d H:i:s', strtotime($appt['appointment_time']) + 3600), // Assume 1 hour duration
        'allDay' => false,
        'className' => 'appointment',
        'extendedProps' => [
            'note' => '',
            'reminder_minutes' => 1
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($events);