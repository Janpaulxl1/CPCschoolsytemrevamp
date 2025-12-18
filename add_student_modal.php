<?php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$required = ['student_id', 'name', 'section_id'];
$missing = [];
$input = [];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $missing[] = $field;
    }
    $input[$field] = trim($_POST[$field] ?? '');
}

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing)
    ]);
    exit;
}

// Optional fields
$input['phone'] = trim($_POST['phone'] ?? '');
$input['email'] = trim($_POST['email'] ?? '');
$input['emergency_contact'] = trim($_POST['emergency_contact'] ?? '');

// Ensure section_id is an integer
$sectionId = (int)$input['section_id'];

try {
    // Check if section exists
    $stmt = $conn->prepare("SELECT id FROM sections WHERE id = ?");
    $stmt->bind_param("i", $sectionId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
        exit;
    }
    $stmt->close();

    // Insert student
    $stmt = $conn->prepare("
        INSERT INTO students 
        (student_id, name, phone, email, emergency_contact, section_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssi",
        $input['student_id'],
        $input['name'],
        $input['phone'],
        $input['email'],
        $input['emergency_contact'],
        $sectionId
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
