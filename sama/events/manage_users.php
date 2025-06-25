<?php
session_start();
require '../config/db.php';

// Only admin allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$user = $_SESSION['user'];

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id !== $user['id']) { // Prevent admin deleting self
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: manage_users.php?msg=User deleted");
        exit;
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Fetch all users except current admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY role, name");
$stmt->execute([$user['id']]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<script src="https://cdn.tailwindcss.com"></script>
<title>Manage Users</title>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Manage Users</h1>

  <?php if (isset($_GET['msg'])): ?>
    <p class="text-green-600 mb-4"><?php echo htmlspecialchars($_GET['msg']); ?></p>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <table class="w-full border-collapse border border-gray-300">
    <thead>
      <tr class="bg-gray-200">
        <th class="border border-gray-300 p-2 text-left">Name</th>
        <th class="border border-gray-300 p-2 text-left">Email</th>
        <th class="border border-gray-300 p-2 text-left">Role</th>
        <th class="border border-gray-300 p-2 text-left">Faculty</th>
        <th class="border border-gray-300 p-2 text-left">Class Section</th>
        <th class="border border-gray-300 p-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($users) === 0): ?>
        <tr><td colspan="6" class="p-4 text-center">No other users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="border border-gray-300 p-2"><?php echo htmlspecialchars($u['name']); ?></td>
            <td class="border border-gray-300 p-2"><?php echo htmlspecialchars($u['email']); ?></td>
            <td class="border border-gray-300 p-2"><?php echo htmlspecialchars($u['role']); ?></td>
            <td class="border border-gray-300 p-2"><?php echo htmlspecialchars($u['faculty'] ?? '-'); ?></td>
            <td class="border border-gray-300 p-2"><?php echo htmlspecialchars($u['class_section'] ?? '-'); ?></td>
            <td class="border border-gray-300 p-2">
              <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
              <a href="manage_users.php?delete_id=<?php echo $u['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="text-red-600 hover:underline">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="admin_dashboard.php" class="inline-block mt-6 text-blue-600 hover:underline">Back to Dashboard</a>
</div>
</body>
</html>
