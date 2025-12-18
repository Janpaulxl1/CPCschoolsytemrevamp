<?php
require_once "db.php";

$sql = "DESCRIBE appointments";
$result = $conn->query($sql);
echo "Appointments table:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nStudents table:\n";
$sql = "DESCRIBE students";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nSample appointments:\n";
$sql = "SELECT * FROM appointments LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}

echo "\nSample students:\n";
$sql = "SELECT id, student_id FROM students LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}
?>
