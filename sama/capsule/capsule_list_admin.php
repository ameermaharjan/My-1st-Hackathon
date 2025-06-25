<?php

require '../config/db.php';
$role = $_SESSION['role'];
// Only Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access denied.");
}

$limit = 10; // capsules per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters from GET
$q = $_GET['q'] ?? '';
$faculty = $_GET['faculty'] ?? '';
$class = $_GET['class'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Build WHERE conditions dynamically
$where = [];
$params = [];
$paramTypes = '';

if ($q !== '') {
    $where[] = "title LIKE ?";
    $params[] = "%$q%";
    $paramTypes .= 's';
}
if ($faculty !== '') {
    $where[] = "faculty = ?";
    $params[] = $faculty;
    $paramTypes .= 's';
}
if ($class !== '') {
    $where[] = "class = ?";
    $params[] = $class;
    $paramTypes .= 's';
}
if ($from_date !== '') {
    $where[] = "date >= ?";
    $params[] = $from_date;
    $paramTypes .= 's';
}
if ($to_date !== '') {
    $where[] = "date <= ?";
    $params[] = $to_date;
    $paramTypes .= 's';
}

$whereSQL = $where ? "WHERE " . implode(' AND ', $where) : "";

// Get total count for pagination
$countSQL = "SELECT COUNT(*) AS total FROM capsules $whereSQL";
$countStmt = $conn->prepare($countSQL);
if ($where) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalCapsules = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalCapsules / $limit);

// Add limit and offset params
$params[] = $limit;
$params[] = $offset;
$paramTypes .= 'ii';

// Fetch capsules with filters and pagination
$sql = "SELECT * FROM capsules $whereSQL ORDER BY date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Handle deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $del_stmt = $conn->prepare("DELETE FROM capsules WHERE id=?");
    $del_stmt->bind_param("i", $del_id);
    $del_stmt->execute();
    header("Location: capsule_list_admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Capsule List with Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50">
    <html>
    
<?php
//  if ($role === 'Admin'): 
    ?>
    <button
        id="openAddCapsuleBtn"
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
    >
        + Add Capsule
    </button>
<?php
//  endif;
  ?> 

<!-- Modal -->
<div
    id="addCapsuleModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50"
>
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full p-4 relative" style="height: 80vh;">
        <button
            id="closeAddCapsuleBtn"
            class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold z-10"
            aria-label="Close modal"
        >
            &times;
        </button>

        <iframe
            src="../capsule/add_capsule.php"
            frameborder="0"
            style="width: 100%; height: 100%; border-radius: 0.5rem;"
            id="addEventIframe"
        ></iframe>
    </div>
</div>

<script>
    const openBtn = document.getElementById('openAddCapsuleBtn');
    const closeBtn = document.getElementById('closeAddCapsuleBtn');
    const modal = document.getElementById('addCapsuleModal');

    openBtn?.addEventListener('click', () => {
        modal.classList.remove('hidden');
    });

    closeBtn?.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

   
</script>
</html>

<h1 class="text-3xl font-bold mb-6">🗂 Capsule Listing (Admin)</h1>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
    <input type="text" name="q" placeholder="Search title..." 
           value="<?= htmlspecialchars($q) ?>" 
           class="border p-1 flex-grow min-w-[150px]" />
    <input type="text" name="faculty" placeholder="Faculty" 
           value="<?= htmlspecialchars($faculty) ?>" 
           class="border p-1 min-w-[120px]" />
    <input type="text" name="class" placeholder="Class" 
           value="<?= htmlspecialchars($class) ?>" 
           class="border p-1 min-w-[120px]" />
    <label class="text-sm">From:
      <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="border p-1" />
    </label>
    <label class="text-sm">To:
      <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="border p-1" />
    </label>
    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded">Filter</button>
</form>

<table class="min-w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-200 text-left">
        <tr>
            
            <th class="py-2 px-4 border">Title</th>
            <th class="py-2 px-4 border">Description</th>
            <th class="py-2 px-4 border">Faculty</th>
            <th class="py-2 px-4 border">Class</th>
            <th class="py-2 px-4 border">Date</th>
            <th class="py-2 px-4 border">Created By (User ID)</th>
            <th class="py-2 px-4 border">Role</th>
            <th class="py-2 px-4 border">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr class="border-b hover:bg-gray-100">
           
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['title']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars(strlen($row['description']) > 50 ? substr($row['description'], 0, 50) . '...' : $row['description']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['faculty']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['class']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['date']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['created_by']) ?></td>
            <td class="py-2 px-4 border"><?= htmlspecialchars($row['role']) ?></td>
            <td class="py-2 px-4 border space-x-2">
                <a href="../capsule/edit_capsule.php?id=<?= $row['id'] ?>&source=capsule" class="text-blue-600 hover:underline">Edit</a>
                <a href="../capsule/delete_capsule.php?delete=<?= $row['id'] ?>" 
                   onclick="return confirm('Delete this capsule?')" 
                   class="text-red-600 hover:underline">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if($result->num_rows === 0): ?>
        <tr><td colspan="9" class="text-center p-4">No capsules found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="mt-4 flex justify-center space-x-2">
    <?php for ($p=1; $p <= $totalPages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" 
           class="px-3 py-1 rounded <?= $p == $page ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
           <?= $p ?>
        </a>
    <?php endfor; ?>
</div>

</body>
</html>
