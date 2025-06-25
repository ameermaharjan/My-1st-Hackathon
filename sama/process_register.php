<?php
include 'config/db.php';
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    header('Location: auth/login.php');
    exit();
}

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];
$faculty = $_POST['faculty'];
$class = $_POST['class'];

include 'config/db.php';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, faculty, class) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $password, $role, $faculty, $class);

if ($stmt->execute()) {
    header("Location: auth/login.php");
} else {
    echo "Registration failed!";
}

?>
