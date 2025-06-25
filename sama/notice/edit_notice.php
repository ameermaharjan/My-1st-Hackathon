<?php
require '../config/db.php';
session_start();

if ($_SESSION['role'] !== 'Teacher') exit("Unauthorized");

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) exit("Notice not found or no permission");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $faculty = $_POST['faculty'];
    $class = $_POST['class'];

    $fileName = $event['homework_file'];
    if (!empty($_FILES['homework']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['homework']['name']);
        move_uploaded_file($_FILES['homework']['tmp_name'], $uploadDir . $fileName);
    }

    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, faculty = ?, class = ?, homework_file = ? WHERE id = ? AND created_by = ?");
    $stmt->bind_param("sssssii", $title, $description, $faculty, $class, $fileName, $id, $user_id);
    $stmt->execute();

    header("Location: notice_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Notice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-4">Edit Notice</h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" class="w-full border p-2 rounded" required>
            <textarea name="description" class="w-full border p-2 rounded"><?= htmlspecialchars($event['description']) ?></textarea>
            <input type="text" name="faculty" value="<?= htmlspecialchars($event['faculty']) ?>" class="w-full border p-2 rounded">
            <input type="text" name="class" value="<?= htmlspecialchars($event['class']) ?>" class="w-full border p-2 rounded">

            <label>Replace Homework File</label>
            <input type="file" name="homework" class="w-full border p-2 rounded">

            <?php if ($event['homework_file']): ?>
                <p class="text-sm text-blue-700">Current: <a href="../uploads/<?= htmlspecialchars($event['homework_file']) ?>" download><?= htmlspecialchars($event['homework_file']) ?></a></p>
            <?php endif; ?>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
        </form>
    </div>
</body>
</html>
