<?php
require 'db.php';
header('Content-Type: application/json');

// Get raw JSON input
$raw = file_get_contents('php://input');
file_put_contents('log.txt', $raw . PHP_EOL, FILE_APPEND); // Debug log

$input = json_decode($raw, true);

// Check if input is valid JSON
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid or empty JSON']);
    exit;
}

// Sanitize and assign variables
$name = trim($input['name'] ?? '');
$program = strtoupper(trim($input['program'] ?? ''));
$semester = trim($input['semester'] ?? '');
$isArchived = isset($input['isArchived']) && $input['isArchived'] ? 1 : 0;

// Validate input fields
if (!$name || !$program || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Find the program_id from programs table (case-insensitive)
    $stmt = $pdo->prepare("SELECT id FROM programs WHERE UPPER(name) = ?");
    $stmt->execute([$program]); 
    $program_id = $stmt->fetchColumn();

    // If program not found, return error
    if (!$program_id) {
        echo json_encode(['success' => false, 'message' => 'Program not found: ' . $program]);
        exit;
    }

    // Insert section into sections table
    $stmt = $pdo->prepare("INSERT INTO sections (program_id, name, semester, is_archived) VALUES (?, ?, ?, ?)");
    $stmt->execute([$program_id, $name, $semester, $isArchived]);

    // Return success and section ID
    $section_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'section_id' => $section_id]);

} catch (Exception $e) {
    // Catch errors and log/return
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
