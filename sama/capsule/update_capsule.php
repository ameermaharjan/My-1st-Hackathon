<?php
session_start();
require '../config/db.php';

$id      = $_POST['id'];
$source  = $_POST['source'];
$title   = $_POST['title'];
$desc    = $_POST['description'];
$date    = $_POST['date'];
$time    = $_POST['time'];
$name    = $_POST['name'] ?? '';
$faculty = $_POST['faculty'] ?? '';
$class   = $_POST['class'] ?? '';

if ($source === 'event') {
    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, date=?, time=?, name=?, faculty=?, class=? WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $desc, $date, $time, $name, $faculty, $class, $id);
} else {
    $stmt = $conn->prepare("UPDATE capsules SET title=?, description=?, date=?, time=?, name=?, faculty=?, class=? WHERE id=?");
    $stmt->bind_param("sssssssi", $title, $desc, $date, $time, $name, $faculty, $class, $id);
}

$stmt->execute();
header("Location: calendar.php");
exit;
