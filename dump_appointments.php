<?php
require 'db.php';

echo "=== Appointments Table Structure ===\n";
$desc_result = $conn->query("DESCRIBE appointments");
if ($desc_result) {
    while ($row = $desc_result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== Sample Data (LIMIT 5) ===\n";
$sql = "SELECT * FROM appointments ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        foreach ($row as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
        echo "---\n";
    }
} else {
    echo "No data or error: " . $conn->error . "\n";
}

$conn->close();
?>
