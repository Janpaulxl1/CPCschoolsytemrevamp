<?php
require 'db.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['section_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$section_id = (int)$data['section_id'];

// Check if section has students
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE section_id = ?");
$check_stmt->bind_param("i", $section_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();

if ($check_result['count'] > 0) {
    echo json_encode(["success" => false, "message" => "Cannot delete folder with students. Please reassign students first."]);
    $check_stmt->close();
    exit;
}

// Delete section
$sql = "DELETE FROM sections WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $section_id);
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Folder deleted successfully"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete folder"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$conn->close();
?>
