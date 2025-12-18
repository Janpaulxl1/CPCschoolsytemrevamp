<?php
require_once 'db.php';

$payload = json_decode(file_get_contents("php://input"), true);

if (!$payload) {
    // fallback for form posts (not expected)
    $payload = $_POST;
}

if (!empty($payload['title']) && !empty($payload['start'])) {
    $title = $conn->real_escape_string($payload['title']);
    $note  = isset($payload['note']) ? $conn->real_escape_string($payload['note']) : '';
    $start = $conn->real_escape_string($payload['start']);
    $end   = isset($payload['end']) && $payload['end'] !== '' ? $conn->real_escape_string($payload['end']) : $conn->real_escape_string($payload['start']);
    $reminder_minutes = isset($payload['reminder_minutes']) ? intval($payload['reminder_minutes']) : 0;

    $sql = "INSERT INTO events (title, note, start, end, reminder_minutes) VALUES ('$title', '$note', '$start', " . ($end ? "'$end'" : "NULL") . ", $reminder_minutes)";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing title or start date']);
}
