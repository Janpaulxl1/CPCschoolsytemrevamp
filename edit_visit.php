<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No ID provided.");
}

$stmt = $conn->prepare("SELECT * FROM student_visits WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Record not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Visit</title>
</head>
<body>
<h2>Edit Student Visit</h2>
<form action="update.php" method="POST">
    <input type="hidden" name="id" value="<?= $row['id'] ?>">

    <label>Date:</label>
    <input type="date" name="date" value="<?= $row['visit_date'] ?>" required><br><br>

    <label>Student Name:</label>
    <input type="text" name="student" value="<?= $row['student_name'] ?>" required><br><br>

    <label>Reason:</label>
    <input type="text" name="reason" value="<?= $row['reason'] ?>"><br><br>

    <label>Action Taken:</label>
    <input type="text" name="action" value="<?= $row['action_taken'] ?>"><br><br>

    <label>Remarks:</label>
    <textarea name="remarks"><?= $row['remarks'] ?></textarea><br><br>

    <button type="submit">Update</button>
</form>
</body>
</html>
