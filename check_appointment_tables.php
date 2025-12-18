<?php
require 'db.php';

$tables = ['appointment_logs', 'appointments', 'appointment_medications'];

foreach ($tables as $table) {
    echo "=== Table: $table ===\n";
    
    // Describe table
    $desc_result = $conn->query("DESCRIBE $table");
    if ($desc_result) {
        echo "Columns:\n";
        while ($row = $desc_result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table does not exist or query failed.\n";
    }
    
    // Count rows
    $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        echo "Row count: " . $count_row['count'] . "\n";
    }
    
    echo "\n";
}

$conn->close();
?>
