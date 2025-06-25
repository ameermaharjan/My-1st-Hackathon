<?php
session_start();
include 'config/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$result = $conn->query("SELECT * FROM users WHERE email = '$email'");
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    // Redirect based on role
    switch ($user['role']) {
        case 'Admin': header("Location: dashboard/admin.php"); break;
        case 'Teacher': header("Location: dashboard/teacher.php"); break;
        case 'CR': header("Location: dashboard/cr.php"); break;
        case 'Student': header("Location: dashboard/student.php"); break;
    }
} else {
    echo "Invalid login credentials.";
}
?>
