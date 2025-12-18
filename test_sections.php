<?php
require_once 'db.php';

echo "Checking sections table...\n";

$result = $conn->query("SELECT id, program_id, name FROM sections ORDER BY id ASC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Program: " . $row['program_id'] . ", Name: " . $row['name'] . "\n";
    }
} else {
    echo "Query failed.\n";
}

$conn->close();
?>
