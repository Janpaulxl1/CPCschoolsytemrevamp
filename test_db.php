<?php
require_once 'db.php';

echo "Testing database connection...\n";

if ($conn) {
    echo "Connected to database.\n";

    // Test fetch medications
    $sql = "SELECT name FROM medications ORDER BY name ASC";
    $result = $conn->query($sql);
    if ($result) {
        echo "Medications found:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['name'] . "\n";
        }
    } else {
        echo "No medications found or query failed.\n";
    }

    $conn->close();
} else {
    echo "Failed to connect to database.\n";
}
?>
