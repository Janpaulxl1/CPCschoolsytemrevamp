<?php
require_once 'db.php';

$payload = json_decode(file_get_contents("php://input"), true);
if (!$payload) $payload = $_POST;

if (!empty($payload['id'])) {
    $id = intval($payload['id']);
    $sql = "DELETE FROM events WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing id']);
}
