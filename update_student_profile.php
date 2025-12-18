<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])){
    echo json_encode(['status'=>'error','message'=>'Please log in']);
    exit;
}

$student_id = $_SESSION['student_id'];
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$guardian_name = $_POST['guardian_name'] ?? '';
$emergency_contact = $_POST['emergency_contact'] ?? '';
$guardian_relationship = $_POST['guardian_relationship'] ?? '';

if(empty($first_name) || empty($last_name) || empty($phone)){
    echo json_encode(['status'=>'error','message'=>'First name, Last name, and Phone are required']);
    exit;
}

$stmt = $conn->prepare("UPDATE students SET first_name=?, last_name=?, phone=?, guardian_name=?, emergency_contact=?, guardian_relationship=? WHERE student_id=?");
$stmt->bind_param("sssssss",$first_name,$last_name,$phone,$guardian_name,$emergency_contact,$guardian_relationship,$student_id);

if($stmt->execute()){
    // Notify nurses
    $notif_message = "Student $student_id updated their profile.";
    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id,message,created_at,is_read)
                                  SELECT id,?,NOW(),0 FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name='nurse'");
    $stmt_notif->bind_param("s",$notif_message);
    $stmt_notif->execute();
    $stmt_notif->close();

    echo json_encode(['status'=>'success','message'=>'Profile updated successfully']);
} else {
    echo json_encode(['status'=>'error','message'=>'Update failed']);
}
$stmt->close();
?>
