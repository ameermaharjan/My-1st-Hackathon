<?php
// event_detail.php - Show full details of an event

require '../config/db.php';

if (!isset($_GET['id'])) {
    echo "No event selected.";
    exit();
}
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    echo "Event not found.";
    exit();
}
?>
<script src="https://cdn.tailwindcss.com"></script>
<h2 class="text-xl font-bold mb-4">📌 <?= htmlspecialchars($event['title']) ?></h2>
<div class="space-y-2 text-sm">
    <p><strong>📅 Date:</strong> <?= $event['date'] ?> at <?= $event['time'] ?></p>
    <p><strong>🏷️ Type:</strong> <?= $event['type'] ?></p>
    <p><strong>🏫 Faculty:</strong> <?= $event['faculty'] ?> - <?= $event['class'] ?></p>
    <p><strong>📝 Description:</strong></p>
    <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
    <p><strong>Status:</strong> <?= ucfirst($event['status']) ?></p>
</div>
<a href="javascript:history.back()" class="mt-4 inline-block text-blue-500 underline">🔙 Go back</a>


<?php
// Update calendar.php - Add filter and navigation UI above calendar
// Add at top of calendar.php (after include/header)
?>
<form method="GET" class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-2">
    <select name="month" class="p-1 border rounded">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m == $month ? 'selected' : '') ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
        <?php endfor; ?>
    </select>
    <select name="year" class="p-1 border rounded">
        <?php for ($y = 2023; $y <= 2030; $y++): ?>
            <option value="<?= $y ?>" <?= ($y == $year ? 'selected' : '') ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    <select name="filter_type" class="p-1 border rounded">
        <option value="">All Types</option>
        <option value="Event" <?= ($_GET['filter_type'] ?? '') == 'Event' ? 'selected' : '' ?>>Event</option>
        <option value="Notice" <?= ($_GET['filter_type'] ?? '') == 'Notice' ? 'selected' : '' ?>>Notice</option>
        <option value="Holiday" <?= ($_GET['filter_type'] ?? '') == 'Holiday' ? 'selected' : '' ?>>Holiday</option>
        <option value="Personal" <?= ($_GET['filter_type'] ?? '') == 'Personal' ? 'selected' : '' ?>>Personal</option>
    </select>
    <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">🔍 Filter</button>
</form>
<?php
// Modify query in calendar.php to include type filter
$typeFilter = $_GET['filter_type'] ?? '';
$query = "SELECT * FROM events WHERE MONTH(date) = ? AND YEAR(date) = ?";
if ($typeFilter) {
    $query .= " AND type = '" . $conn->real_escape_string($typeFilter) . "'";
}
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
// (continue with same event fetch loop)
?>
