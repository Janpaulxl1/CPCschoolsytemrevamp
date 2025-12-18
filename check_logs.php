<?php
require_once 'db.php';

$result = $conn->query('SELECT COUNT(*) as count FROM appointment_logs');
$row = $result->fetch_assoc();
echo 'Rows in appointment_logs: ' . $row['count'];
?>
