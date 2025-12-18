<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true); 
    }

    $filename = basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo "<script>alert('File uploaded successfully!'); window.location.href = 'nurse.php';</script>";
    } else {
        echo "<script>alert('Upload failed.'); window.location.href = 'nurse.php';</script>";
    }
} else {
    echo "<script>alert('No file selected.'); window.location.href = 'nurse.php';</script>";
}
?>
