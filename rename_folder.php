<?php
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$section_id = (int)($input['section_id'] ?? 0);
$new_name = trim($input['new_name'] ?? '');

if (!$section_id || !$new_name || strlen($new_name) < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid section ID or empty name']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Validate program
$stmt_prog = $conn->prepare("SELECT p.name AS program_name FROM sections s JOIN programs p ON s.program_id = p.id WHERE s.id = ?");
if (!$stmt_prog) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed for program check']);
    exit;
}
$stmt_prog->bind_param("i", $section_id);
$stmt_prog->execute();
$result_prog = $stmt_prog->get_result();
if ($row_prog = $result_prog->fetch_assoc()) {
    $program_name = trim($row_prog['program_name']);
    $valid_programs = ['BEED', 'BSED', 'BSHM', 'BSIT'];
    if (!in_array(strtoupper($program_name), array_map('strtoupper', $valid_programs))) {
        echo json_encode(['success' => false, 'message' => 'Invalid program associated with this folder. Only BEED, BSED, BSHM, BSIT are allowed.']);
        $stmt_prog->close();
        exit;
    }
    if (stripos($new_name, $program_name) === false) {
        echo json_encode(['success' => false, 'message' => "Folder name must include the program name '{$program_name}'. Use the correct spelling."]);
        $stmt_prog->close();
        exit;
    }
    if (strlen($new_name) > 7) {
        echo json_encode(['success' => false, 'message' => 'Folder name must be 7 characters or less.']);
        $stmt_prog->close();
        exit;
    }
    $has_valid_number = false;
    $has_valid_letter = false;
    for ($i = 0; $i < strlen($new_name); $i++) {
        $char = $new_name[$i];
        if (in_array($char, ['1', '2', '3', '4'])) {
            $has_valid_number = true;
        }
        if (in_array(strtoupper($char), ['A', 'B', 'C', 'D'])) {
            $has_valid_letter = true;
        }
    }
    if (!$has_valid_number || !$has_valid_letter) {
        echo json_encode(['success' => false, 'message' => 'Folder name must include a number 1-4 and a letter A-D (e.g., BEED-1A).']);
        $stmt_prog->close();
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Section not found or no program associated']);
    $stmt_prog->close();
    exit;
}
$stmt_prog->close();

// Proceed with update
$stmt = $conn->prepare("UPDATE sections SET name = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
    exit;
}

$stmt->bind_param("si", $new_name, $section_id);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Folder renamed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No folder found with that ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
?>
