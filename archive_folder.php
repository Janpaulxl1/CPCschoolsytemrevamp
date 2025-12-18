<?php
require 'db.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['section_id']) || !isset($data['is_archived'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$section_id = (int)$data['section_id'];
$is_archived = (int)$data['is_archived'];

// Update section archive status
$sql = "UPDATE sections SET is_archived = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $is_archived, $section_id);
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => $is_archived ? "Folder archived successfully" : "Folder unarchived successfully"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update section"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$conn->close();
