<?php
require '../config/db.php';
session_start();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Check event creator or admin
    $check = $conn->prepare("SELECT created_by FROM events WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $event = $result->fetch_assoc();

    if ($event['created_by'] == $user_id || $role == 'Admin') {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}
header("Location: ../dashboard/admin_event.php");
?>
