<?php 
session_start();
$role = $_SESSION['role'];
if (!in_array($role, ['Admin', 'Teacher'])) die('Access Denied');
?>
<script src="https://cdn.tailwindcss.com"></script>
<form action="save_capsule.php" method="post" class="max-w-md mx-auto bg-white p-4 rounded shadow">
    <h3 class="text-xl font-bold mb-4">➕ Add Capsule</h3>

    <input type="text" name="name" placeholder="Your Name" class="w-full border mb-3 p-2" required>

    <input type="text" name="title" placeholder="Capsule Title" class="w-full border mb-3 p-2" required>

    <textarea name="description" placeholder="Description" class="w-full border mb-3 p-2" required></textarea>

    <input type="date" name="date" class="w-full border mb-3 p-2" required>

    <input type="text" name="faculty" placeholder="Faculty" class="w-full border mb-3 p-2" required>

    <input type="text" name="class" placeholder="Class" class="w-full border mb-3 p-2" required>

    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded w-full">
        Save Capsule
    </button>
</form>
