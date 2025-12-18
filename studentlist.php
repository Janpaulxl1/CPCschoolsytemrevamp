<?php
require 'db.php';

ini_set('display_errors', 0);
error_reporting(0);

$section_id = (int)($_GET['section_id'] ?? 0);

/* ===================== JSON MODE ===================== */
if (isset($_GET['json'])) {
    header('Content-Type: application/json');

    if (!$conn || !$section_id) {
        echo json_encode(['students' => []]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, student_id,
               first_name, middle_name, last_name,
               phone, email, emergency_contact,
               requirements_completed
        FROM students
        WHERE section_id = ?
        ORDER BY last_name ASC
    ");

    if (!$stmt) {
        echo json_encode(['students' => []]);
        exit;
    }

    $stmt->bind_param("i", $section_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $row['name'] = trim(
            $row['first_name'] . ' ' .
            $row['middle_name'] . ' ' .
            $row['last_name']
        );
        $students[] = $row;
    }

    echo json_encode(['students' => $students]);
    exit;
}

/* ===================== HTML MODE ===================== */
if (!$conn || !$section_id) {
    die('Invalid section or database connection.');
}

$stmt = $conn->prepare("SELECT * FROM students WHERE section_id = ?");
$stmt->bind_param("i", $section_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student List</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<h1 class="text-2xl font-bold mb-4">
  Student List (Section <?= htmlspecialchars($section_id) ?>)
</h1>

<div class="bg-white rounded-xl shadow p-4">
  <table class="w-full text-left border-collapse">
    <thead>
      <tr class="bg-gray-200">
        <th class="p-2 border">Student ID</th>
        <th class="p-2 border">Name</th>
        <th class="p-2 border">Phone</th>
        <th class="p-2 border">Email</th>
        <th class="p-2 border">Emergency Contact</th>
        <th class="p-2 border">Requirements</th>
        <th class="p-2 border">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td class="p-2 border"><?= htmlspecialchars($row['student_id']) ?></td>
            <td class="p-2 border">
              <?= htmlspecialchars(trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'])) ?>
            </td>
            <td class="p-2 border"><?= htmlspecialchars($row['phone']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($row['email']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($row['emergency_contact']) ?></td>
            <td class="p-2 border"><?= $row['requirements_completed'] == 1 ? 'Yes' : 'No' ?></td>
            <td class="p-2 border">
              <a href="student_profile.php?student_id=<?= $row['id'] ?>" class="text-blue-500">View</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" class="p-2 border text-center">No students found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
