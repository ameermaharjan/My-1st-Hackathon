<?php
 require '../config/db.php';
session_start();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$faculty = $_SESSION['faculty'] ?? '';
$class = $_SESSION['class'] ?? '';

if (!in_array($role, ['Admin', 'Teacher', 'CR'])) die('Access Denied');

$title = $_POST['title'];
$description = $_POST['description'];
$date = $_POST['date'];

$stmt = $conn->prepare("INSERT INTO capsules (title, description, faculty, class, date, created_by, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssis", $title, $description, $faculty, $class, $date, $user_id, $role);
$stmt->execute();

header("Location: capsule_list.php");
exit;
 ?>
