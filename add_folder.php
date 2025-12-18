<?php
header('Content-Type: application/json');
require 'db.php';

/* ============================
   SAFETY CHECK
============================ */
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

/* ============================
   READ JSON INPUT
============================ */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

/* ============================
   VALIDATE FIELDS
============================ */
$program_id  = intval($data['program_id'] ?? 0);
$name        = trim($data['name'] ?? '');
$semester    = trim($data['semester'] ?? '');
$is_archived = intval($data['is_archived'] ?? 0);

if ($program_id <= 0 || $name === '' || $semester === '') {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

/* ============================
   INSERT SECTION
============================ */
$stmt = $conn->prepare("
    INSERT INTO sections (program_id, name, semester, is_archived)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("issi", $program_id, $name, $semester, $is_archived);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Folder added successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Insert failed: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
exit;
