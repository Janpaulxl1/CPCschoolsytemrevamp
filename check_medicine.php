<?php
require_once 'db.php';

$result = $conn->query('SELECT COUNT(*) as count FROM appointment_medications');
$row = $result->fetch_assoc();
echo 'Rows in appointment_medications: ' . $row['count'];
?>
