<?php
session_start();
require '../config/db.php';
$id     = $_GET['id'];
$source = $_GET['source'];

if ($source === 'event') {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
} else {
    $stmt = $conn->prepare("SELECT * FROM capsules WHERE id=?");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html><head><title>Edit Item</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-6">
<form method="post" action="update_item.php" class="max-w-md mx-auto bg-white p-4 rounded shadow">
  <input type="hidden" name="id" value="<?= $id ?>">
  <input type="hidden" name="source" value="<?= $source ?>">
  <label class="block">Title <input name="title" value="<?= $item['title'] ?>" class="w-full border p-1"></label>
  <label class="block">Description <textarea name="description" class="w-full border p-1"><?= $item['description'] ?></textarea></label>
  <label class="block">Date <input type="date" name="date" value="<?= $item['date'] ?>" class="w-full border p-1"></label>
  <label class="block">Time <input type="time" name="time" value="<?= $item['time'] ?? '' ?>" class="w-full border p-1"></label>
  <button class="bg-green-500 text-white px-3 py-1 rounded mt-2">Update</button>
</form>
</body></html>