<?php
session_start();
require '../config/db.php'; // Your mysqli connection, assigns $conn

// Get current user role and id from session
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Permissions
$can_add = ($role === 'teacher'); // Only teachers can add
$can_edit = in_array($role, ['teacher', 'admin']); // Teachers and admins can edit/delete

// Handle search term from GET
$search = $_GET['search'] ?? '';
$search_sql = '';
if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    $search_sql = " AND (title LIKE '%$search_escaped%' OR description LIKE '%$search_escaped%') ";
}

// SQL to get notices with type homework or others
$sql = "SELECT * FROM notices WHERE type IN ('homework', 'others') $search_sql ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result === false) {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Notice List</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  h1 { margin-bottom: 10px; }
  form { margin-bottom: 15px; }
  input[type="text"] { padding: 6px; width: 250px; }
  button { padding: 6px 12px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
  th { background-color: #f0f0f0; }
  a { color: blue; text-decoration: none; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>

<h1>Notice List</h1>

<!-- Search form -->
<form method="get" action="">
    <input type="text" name="search" placeholder="Search notices..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<?php if (!in_array($role, ['Admin', 'Student'])): ?>
    <!-- Visible only to Teacher or other roles not Admin/Student -->
      <a href="../notice/add_notice.php" class="text-green-600 hover:underline">+add New Notice</a>
<?php endif; ?>
   
      <div>
       <!-- <?php if ($role === 'Teacher'): ?>
    <div>
        <a href="../notice/add_notice.php" class="text-green-600 hover:underline">+add New Notice</a>
    </div>
<?php endif; ?>
    </div> -->

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Description</th>
            <th>Created At</th>
            <?php if ($can_edit): ?><th>Actions</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="<?= $can_edit ? 6 : 5 ?>" style="text-align:center;">No notices found.</td></tr>
        <?php else: ?>
            <?php while ($notice = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($notice['id']) ?></td>
                    <td>
                        <a href="../notice/notice_detail.php?id=<?= $notice['id'] ?>">
                            <?= htmlspecialchars($notice['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($notice['type']) ?></td>
                    <td><?= nl2br(htmlspecialchars($notice['description'])) ?></td>
                    <td><?= htmlspecialchars($notice['created_at']) ?></td>
                    <?php if ($can_edit): ?>
                        <td>
                            <a href="edit_notice.php?id=<?= urlencode($notice['id']) ?>">Edit</a> |
                            <a href="delete_notice.php?id=<?= urlencode($notice['id']) ?>" onclick="return confirm('Are you sure you want to delete this notice?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
