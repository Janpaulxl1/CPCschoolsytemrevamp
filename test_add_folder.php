<?php
require_once 'db.php';

echo "Testing add_folder functionality...\n";

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'program_id' => 1,
    'name' => 'Test Section',
    'semester' => '1st Semester',
    'is_archived' => 0
];

// Include add_folder.php to test it
ob_start();
include 'add_folder.php';
$output = ob_get_clean();

echo "Output from add_folder.php: " . $output . "\n";

// Check if section was added
$result = $conn->query("SELECT * FROM sections WHERE name = 'Test Section'");
if ($result && $result->num_rows > 0) {
    echo "Section added successfully.\n";
    // Clean up
    $conn->query("DELETE FROM sections WHERE name = 'Test Section'");
} else {
    echo "Section was not added.\n";
}

$conn->close();
?>
