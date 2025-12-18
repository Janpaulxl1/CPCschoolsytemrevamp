<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$id = $_POST['ID'] ?? '';
$password = $_POST['password'] ?? '';

$host = "localhost";                      
$db =   "capstone2";                      
$user =  "root";                  
$pass =  "";                     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name 
                           FROM users u 
                           LEFT JOIN roles r ON u.role_id = r.id
                           WHERE u.id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['role'] = $user['role_name'];

       
        if ($user['role_name'] === 'nurse') {
            $update_stmt = $pdo->prepare("UPDATE users SET last_active = NOW(), last_logout = NULL WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            header("Location: nurse.php");
            exit;
        } elseif ($user['role_name'] === 'admin') {
            header("Location: admin.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['username'] = $student['first_name'] . " " . $student['last_name'];

        
        if (!empty($student['is_responder']) && $student['is_responder'] == 1) {
            $_SESSION['role'] = 'responder';
           
            $responder_name = $student['first_name'] . " " . $student['last_name'];
            $update_sql = "UPDATE emergency_responders SET status = 'Active', last_active = NOW() WHERE name = ?";
            $stmt_update = $pdo->prepare($update_sql);
            $stmt_update->execute([$responder_name]);
            
            $stmt_resp = $pdo->prepare("SELECT id FROM emergency_responders WHERE name = ?");
            $stmt_resp->execute([$responder_name]);
            $resp = $stmt_resp->fetch(PDO::FETCH_ASSOC);
            if ($resp) {
                $_SESSION['responder_id'] = $resp['id'];
            }
            
            $stmt_user = $pdo->prepare("SELECT id FROM users WHERE name = ?");
            $stmt_user->execute([$responder_name]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
            }
        } else {
            $_SESSION['role'] = 'student';
        }

        
        header("Location: student_profile.php?id=" . $student['student_id']);
        exit;
    }

   
    echo "<script>alert('Invalid ID or password.'); window.location.href = 'index.html';</script>";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
