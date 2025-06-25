<?php
//  include '../includes/navbar.php';
// include '../includes/header.php';
require '../config/db.php';
//  session_start();
//  $_SESSION['name']; 
//  $_SESSION['role'] ;

//  $_SESSION['user_id'];
// $_SESSION['class'];
// $_SESSION['faculty'];

// $role = $_POST['role'] ?? '';
// $user_id = $_POST['user_id'] ?? '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : null;
$class = isset($_SESSION['class']) ? $_SESSION['class'] : null;
// $role = $_SESSION['role'];
// $user_id = $_SESSION['user_id'];
// $faculty = $_SESSION['faculty'];
// $class = $_SESSION['class'];

// Get filter inputs with validation & sanitization
$search = trim($_GET['search'] ?? '');
$filter_type = $_GET['filter_type'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_faculty = $_GET['filter_faculty'] ?? '';
$filter_class = $_GET['filter_class'] ?? '';
$filter_date_start = $_GET['filter_date_start'] ?? '';
$filter_date_end = $_GET['filter_date_end'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$params = [];
$types_str = "";
$whereClauses = ["1=1"];

// Role-based access control filters
if ($role == 'Teacher' || $role == 'CR') {
    $whereClauses[] = "e.faculty = ?";
    $params[] = $faculty;
    $types_str .= "s";
    $whereClauses[] = "e.class = ?";
    $params[] = $class;
    $types_str .= "s";
    if ($role == 'CR') {
        $whereClauses[] = "e.created_by = ?";
        $params[] = $user_id;
        $types_str .= "i";
    }
} elseif ($role == 'Student') {
    $whereClauses[] = "((e.faculty = ? AND e.class = ?) OR (e.created_by = ? AND e.type = 'Personal'))";
    $params[] = $faculty;
    $params[] = $class;
    $params[] = $user_id;
    $types_str .= "ssi";
}

// Filters
if ($search) {
    $whereClauses[] = "e.title LIKE ?";
    $params[] = "%$search%";
    $types_str .= "s";
}
if ($filter_type) {
    $whereClauses[] = "e.type = ?";
    $params[] = $filter_type;
    $types_str .= "s";
}
if ($filter_status) {
    $whereClauses[] = "e.status = ?";
    $params[] = $filter_status;
    $types_str .= "s";
}
if ($filter_faculty) {
    $whereClauses[] = "e.faculty = ?";
    $params[] = $filter_faculty;
    $types_str .= "s";
}
if ($filter_class) {
    $whereClauses[] = "e.class = ?";
    $params[] = $filter_class;
    $types_str .= "s";
}
if ($filter_date_start) {
    $whereClauses[] = "e.date >= ?";
    $params[] = $filter_date_start;
    $types_str .= "s";
}
if ($filter_date_end) {
    $whereClauses[] = "e.date <= ?";
    $params[] = $filter_date_end;
    $types_str .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

// Count total events for pagination
$countSql = "SELECT COUNT(*) FROM events e WHERE $whereSql";
$stmt = $conn->prepare($countSql);
if ($params) {
    $stmt->bind_param($types_str, ...$params);
}
$stmt->execute();
$stmt->bind_result($totalEvents);
$stmt->fetch();
$stmt->close();

$totalPages = ceil($totalEvents / $perPage);

// Fetch events with limit and offset, pinned on top
$dataSql = "SELECT e.*, u.name as creator FROM events e 
            LEFT JOIN users u ON e.created_by = u.id 
            WHERE $whereSql 
            ORDER BY e.pinned DESC, e.date DESC, e.time DESC 
            LIMIT ? OFFSET ?";
$stmt = $conn->prepare($dataSql);
if ($params) {
    $bindParams = $params;
    $bindTypes = $types_str . "ii";
    $bindParams[] = $perPage;
    $bindParams[] = $offset;

    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>


<div class="content ml-12 transform ease-in-out duration-500 pt-20 px-2 md:px-5 pb-4>


<h2 class="text-xl font-semibold mb-4">📋 Event List</h2>

<!-- Filter Form -->
<form method="GET" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-4xl">
    <input
        type="text"
        name="search"
        placeholder="Search title"
        value="<?= htmlspecialchars($search) ?>"
        class="border p-2 rounded"
    />
    <select name="filter_type" class="border p-2 rounded">
        <option value="">All Types</option>
        <?php
        $types = ['Event', 'Notice', 'Holiday', 'Personal'];
        foreach ($types as $t):
            $selected = ($filter_type === $t) ? 'selected' : '';
        ?>
            <option value="<?= $t ?>" <?= $selected ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>
    <select name="filter_status" class="border p-2 rounded">
        <option value="">All Statuses</option>
        <?php
        $statuses = ['pending', 'approved'];
        foreach ($statuses as $s):
            $selected = ($filter_status === $s) ? 'selected' : '';
        ?>
            <option value="<?= $s ?>" <?= $selected ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="filter_faculty" placeholder="Faculty" value="<?= htmlspecialchars($filter_faculty) ?>" class="border p-2 rounded" />
    <input type="text" name="filter_class" placeholder="Class" value="<?= htmlspecialchars($filter_class) ?>" class="border p-2 rounded" />
    <div class="flex gap-2">
        <input type="date" name="filter_date_start" value="<?= htmlspecialchars($filter_date_start) ?>" class="border p-2 rounded flex-grow" placeholder="From Date" />
        <input type="date" name="filter_date_end" value="<?= htmlspecialchars($filter_date_end) ?>" class="border p-2 rounded flex-grow" placeholder="To Date" />
    </div>
    <div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Filter</button>
    </div>
</form>

<!-- Pagination -->
<div class="mb-4 flex justify-between items-center max-w-4xl">
    <div>Showing <?= min($totalEvents, $offset + 1) ?> - <?= min($totalEvents, $offset + $perPage) ?> of <?= $totalEvents ?> events</div>
    <div class="space-x-2">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
               class="px-3 py-1 rounded <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
               <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<!-- Event Table -->
<table class="min-w-full border border-gray-300 rounded max-w-4xl">
    <div>
       <?php if ($role === 'Admin'): ?>
    <div>
        <a href="../events/add_event.php" class="text-green-600 hover:underline">+add event</a>
    </div>
<?php endif; ?>
    </div>
    <thead class="bg-gray-100">
        <tr>
            <th class="border px-3 py-1 text-left">Title</th>
            <th class="border px-3 py-1">Date</th>
            <th class="border px-3 py-1">Time</th>
            <th class="border px-3 py-1">Type</th>
            <th class="border px-3 py-1">Faculty</th>
            <th class="border px-3 py-1">Class</th>
            <th class="border px-3 py-1">Status</th>
            <th class="border px-3 py-1">Pinned</th>
            <th class="border px-3 py-1">Creator</th>
            <th class="border px-3 py-1">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="10" class="text-center p-4">No events found.</td></tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="border px-3 py-1">
                        <a href="../events/event_detail.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:underline">
                            <?= htmlspecialchars($row['title']) ?>
                        </a>
                    </td>
                    <td class="border px-3 py-1 text-center"><?= $row['date'] ?></td>
                    <td class="border px-3 py-1 text-center"><?= $row['time'] ?: '-' ?></td>
                    <td class="border px-3 py-1 text-center"><?= $row['type'] ?></td>
                    <td class="border px-3 py-1 text-center"><?= $row['faculty'] ?></td>
                    <td class="border px-3 py-1 text-center"><?= $row['class'] ?></td>
                    <td class="border px-3 py-1 text-center"><?= ucfirst($row['status']) ?></td>
                    <td class="border px-3 py-1 text-center">
                        <?php if (in_array($role, ['Admin', 'Teacher'])): ?>
                            <a href="toggle_pin.php?id=<?= $row['id'] ?>"
                               class="text-sm <?= $row['pinned'] ? 'text-yellow-500' : 'text-gray-400' ?>"
                               title="<?= $row['pinned'] ? 'Unpin Notice' : 'Pin Notice' ?>">
                               <?= $row['pinned'] ? '📌 Unpin' : '📍 Pin' ?>
                            </a>
                        <?php else: ?>
                            <?= $row['pinned'] ? '📌' : '' ?>
                        <?php endif; ?>
                    </td>
                    <td class="border px-3 py-1 text-center"><?= htmlspecialchars($row['creator']) ?></td>
                    <td class="border px-3 py-1 text-center">
                        <?php
                        $canEdit = ($role == 'Admin') || ($row['created_by'] == $user_id && in_array($role, ['Teacher', 'CR']));
                        $canDelete = $canEdit;
                        ?>
                        <?php if ($canEdit): ?>
                            <a href="../events/edit_event.php?id=<?= $row['id'] ?>" class="text-yellow-600 hover:underline mr-2">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="../events/delete_event.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this event?')" class="text-red-600 hover:underline">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

