<?php
require_once 'db.php';

echo "Fixing sections table...\n";

// First, delete the row with id 0 if it exists
$conn->query("DELETE FROM sections WHERE id = 0");

// Set auto-increment to start from 1
$conn->query("ALTER TABLE sections AUTO_INCREMENT = 1");

echo "Sections table fixed.\n";

$conn->close();
?>
