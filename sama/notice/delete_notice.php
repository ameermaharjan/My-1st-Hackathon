
<?php
require '../config/db.php';
session_start();

if ($_SESSION['role'] !== 'Teacher') exit("Unauthorized");

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM notices WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header("Location: notice_list.php");
exit;
?>
