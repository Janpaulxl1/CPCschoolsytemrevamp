<?php
require_once 'db.php';

$payload = json_decode(file_get_contents("php://input"), true);
if (!$payload) $payload = $_POST;

if (!empty($payload['id'])) {
    $id = intval($payload['id']);
    $updates = [];

    if (isset($payload['title'])) {
        $title = $conn->real_escape_string($payload['title']);
        $updates[] = "title='$title'";
    }
    if (isset($payload['note'])) {
        $note = $conn->real_escape_string($payload['note']);
        $updates[] = "note='$note'";
    }
    if (isset($payload['start'])) {
        $start = $conn->real_escape_string($payload['start']);
        $updates[] = "start='$start'";
    }
    if (isset($payload['end'])) {
        $end = $payload['end'] !== '' ? $conn->real_escape_string($payload['end']) : $conn->real_escape_string($payload['start']);
        $updates[] = "end='$end'";
    }
    if (isset($payload['reminder_minutes'])) {
        $reminder_minutes = intval($payload['reminder_minutes']);
        $updates[] = "reminder_minutes=$reminder_minutes";
    }

    if (!empty($updates)) {
        $sql = "UPDATE events SET " . implode(", ", $updates) . " WHERE id=$id";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing id']);
}