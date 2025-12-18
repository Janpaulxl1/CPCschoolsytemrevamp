    <?php
    session_start();
    require_once "db.php";

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false]);
        exit;
    }

    // Update nurse last_active timestamp on page load
    if (isset($_SESSION['user_id'])) {
        $update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("i", $_SESSION['user_id']);
        $stmt_update->execute();
        $stmt_update->close();
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false]);
        exit;
    }

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => $affected > 0]);
    ?>