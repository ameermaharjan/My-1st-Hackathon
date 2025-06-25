<?php
// session_start();
require '../config/db.php';

$role     = $_SESSION['role']     ?? null;
$user_id  = $_SESSION['user_id']  ?? null;
$faculty  = $_SESSION['faculty']  ?? null;
$class    = $_SESSION['class']    ?? null;

// Calendar parameters
$month       = $_GET['month']      ?? date('m');
$year        = $_GET['year']       ?? date('Y');
$filterType  = $_GET['type']       ?? '';
$filterDate  = $_GET['filter_date'] ?? '';

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDay    = date('N', strtotime("$year-$month-01")); // 1 (Mon) to 7 (Sun)
$today       = date('Y-m-d');

// Fetch events with role-based filtering
$eventQuery = "SELECT * FROM events WHERE MONTH(date)=? AND YEAR(date)=?";
$params = [$month, $year];

if ($filterType) {
    $eventQuery .= " AND type = ?";
    $params[] = $filterType;
}
if ($filterDate) {
    $eventQuery .= " AND date = ?";
    $params[] = $filterDate;
}

if ($role === 'Teacher' || $role === 'CR') {
    $eventQuery .= " AND faculty = ? AND class = ?";
    $params[] = $faculty;
    $params[] = $class;
} elseif ($role === 'Student') {
    $eventQuery .= " AND ((faculty = ? AND class = ?) OR (type = 'Personal' AND created_by = ?))";
    $params[] = $faculty;
    $params[] = $class;
    $params[] = $user_id;
}
// Admin sees all

$stmt = $conn->prepare($eventQuery);
$typesStr = str_repeat("s", count($params));
$stmt->bind_param($typesStr, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $day = date('j', strtotime($row['date']));
    $row['source'] = 'event';
    $events[$day][] = $row;
}

// Fetch capsules with role-based filtering
$cq = "SELECT * FROM capsules WHERE MONTH(date)=? AND YEAR(date)=?";
$cparams = [$month, $year];

if ($role === 'Teacher' || $role === 'CR') {
    $cq .= " AND faculty = ? AND class = ?";
    $cparams[] = $faculty;
    $cparams[] = $class;
} elseif ($role === 'Student') {
    $cq .= " AND faculty = ? AND class = ?";
    $cparams[] = $faculty;
    $cparams[] = $class;
}

$cstmt = $conn->prepare($cq);
$cstmt->bind_param(str_repeat("s", count($cparams)), ...$cparams);
$cstmt->execute();
$cresult = $cstmt->get_result();

while ($cap = $cresult->fetch_assoc()) {
    $day = date('j', strtotime($cap['date']));
    $cap['source'] = 'capsule';
    $events[$day][] = $cap;
}

// Dropdown data
$months = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
$currentYear = date('Y');

// Initialize add event form variables
$title = $description = $time = $faculty_input = $class_input = "";
$type = ($role === 'Student') ? 'Personal' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Calendar + Events + Capsules</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50 min-h-screen">

<form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
  <select name="month" class="border p-1 rounded">
    <?php foreach($months as $num=>$name): ?>
      <option value="<?= $num ?>" <?= $num == $month?'selected':'' ?>><?= $name ?></option>
    <?php endforeach; ?>
  </select>
  <select name="year" class="border p-1 rounded">
    <?php for($y=$currentYear-3;$y<=$currentYear+3;$y++): ?>
      <option value="<?=$y?>" <?= $y==$year?'selected':''?>><?=$y?></option>
    <?php endfor; ?>
  </select>
  <select name="type" class="border p-1 rounded">
    <option value="">All Types</option>
    <option value="Event" <?= $filterType=='Event'?'selected':'' ?>>Event</option>
    <option value="Notice" <?= $filterType=='Notice'?'selected':'' ?>>Notice</option>
    <option value="Holiday" <?= $filterType=='Holiday'?'selected':'' ?>>Holiday</option>
    <option value="Personal" <?= $filterType=='Personal'?'selected':'' ?>>Personal</option>
  </select>
  <input type="date" name="filter_date" value="<?= $filterDate ?>" class="border p-1 text-sm rounded" />
  <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">Filter</button>
</form>

<h2 class="text-2xl mb-4 font-semibold">📅 Calendar — <?= $months[$month].' '.$year ?></h2>

<div class="grid grid-cols-7 gap-2 text-center font-bold mb-2">
  <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?><div><?= $d ?></div><?php endforeach; ?>
</div>

<div class="grid grid-cols-7 gap-2 text-center text-sm">
  <?php 
  // empty cells before first day
  for($i=1;$i<$firstDay;$i++): ?>
    <div class="p-4 border bg-gray-100 rounded"></div>
  <?php endfor; ?>
  <?php 
  for($day=1;$day<=$daysInMonth;$day++):
    $fullDate = "$year-$month-".str_pad($day,2,'0',STR_PAD_LEFT);
    $isToday = $fullDate === $today;
  ?>
  <div 
    class="p-2 border rounded relative hover:bg-gray-100 cursor-pointer <?= $isToday ? 'bg-blue-100 border-blue-500' : 'bg-white' ?>"
    onclick="openAddModal('<?= $fullDate ?>')"
    >
    <div class="font-bold <?= $isToday ? 'text-blue-600' : '' ?>"><?= $day ?></div>
    <?php if (!empty($events[$day])): ?>
      <?php foreach($events[$day] as $e): 
        $color = $e['source']=='event'
          ? ($e['type']=='Event'?'bg-blue-500'
            : ($e['type']=='Notice'?'bg-yellow-400 text-black'
              : ($e['type']=='Holiday'?'bg-red-500':'bg-green-500')))
          : 'bg-purple-500';
      ?>
      <button 
        onclick="event.stopPropagation(); showDetail(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" 
        class="text-white text-xs px-2 py-0.5 mt-1 rounded-full block <?= $color ?>"
        type="button"
      >
        <?= $e['source']=='capsule' ? 'Capsule' : $e['type'] ?>
      </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endfor; ?>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-40 hidden justify-center items-center z-50">
  <div class="bg-white p-6 rounded w-[90%] max-w-md relative">
    <button onclick="closeModal()" class="absolute top-2 right-3 text-gray-600 text-xl">✖</button>
    <h3 id="modalTitle" class="text-xl font-bold mb-2"></h3>
    <p id="modalDesc" class="mb-1 whitespace-pre-line"></p>
    <p id="modalDate" class="text-sm text-gray-600 mb-2"></p>
    <p id="modalMeta" class="text-xs text-gray-500"></p>
  </div>
</div>

<!-- Add Event Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-40 hidden justify-center items-center z-50 overflow-auto p-4">
  <form action="events/add_event.php" method="post" class="bg-white p-6 rounded w-full max-w-lg relative" id="addEventForm" novalidate>
    <button type="button" onclick="closeAddModal()" class="absolute top-2 right-3 text-gray-600 text-xl">✖</button>
    <h2 class="text-xl font-bold mb-4">Add New Event / Notice</h2>

    <div class="mb-4">
      <label for="date" class="block font-semibold mb-1">Date</label>
      <input type="date" name="date" id="date" value="" required
        class="w-full border p-2 rounded" />
    </div>

    <div class="mb-4">
      <label for="title" class="block font-semibold mb-1">Title</label>
      <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required
        class="w-full border p-2 rounded" />
    </div>

    <div class="mb-4">
      <label for="description" class="block font-semibold mb-1">Description</label>
      <textarea name="description" id="description" class="w-full border p-2 rounded" rows="3"><?= htmlspecialchars($description) ?></textarea>
    </div>

    <div class="mb-4">
      <label for="time" class="block font-semibold mb-1">Time</label>
      <input type="time" name="time" id="time" value="<?= htmlspecialchars($time) ?>"
        class="w-full border p-2 rounded" />
    </div>

    <?php if ($role === 'Student'): ?>
      <input type="hidden" name="type" value="Personal" />
      <p class="italic text-gray-600 mb-4">Event Type: Personal Schedule (fixed)</p>
    <?php else: ?>
      <div class="mb-4">
        <label for="type" class="block font-semibold mb-1">Event Type</label>
        <select name="type" id="type" class="w-full border p-2 rounded" required>
          <?php
          $allTypes = ['Event', 'Notice', 'Holiday', 'Personal'];
          foreach ($allTypes as $t):
            $selected = ($type === $t) ? 'selected' : '';
          ?>
            <option value="<?= $t ?>" <?= $selected ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="mb-4">
      <label for="faculty" class="block font-semibold mb-1">Faculty</label>
      <input type="text" name="faculty" id="faculty" value="<?= htmlspecialchars($faculty_input) ?>"
        class="w-full border p-2 rounded"
        <?= ($role === 'Student' || $type === 'Personal') ? 'disabled' : 'required' ?> />
      <?php if ($role === 'Student' || $type === 'Personal'): ?>
        <p class="text-sm text-gray-500 italic">Not required for Personal schedule</p>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <label for="class" class="block font-semibold mb-1">Class / Section</label>
      <input type="text" name="class" id="class" value="<?= htmlspecialchars($class_input) ?>"
        class="w-full border p-2 rounded"
        <?= ($role === 'Student' || $type === 'Personal') ? 'disabled' : 'required' ?> />
      <?php if ($role === 'Student' || $type === 'Personal'): ?>
        <p class="text-sm text-gray-500 italic">Not required for Personal schedule</p>
      <?php endif; ?>
    </div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"><a href="events/event_list.php">Add Event</a></button>
  </form>
</div>

<script>
  function showDetail(item) {
    document.getElementById('modalTitle').innerText = item.title;
    document.getElementById('modalDesc').innerText = item.description;
    let when = `📅 ${item.date}` + (item.time ? ` 🕒 ${item.time}` : '');
    document.getElementById('modalDate').innerText = when;
    let meta = item.source === 'capsule'
      ? `🎒 Capsule by ${item.role ?? 'N/A'}`
      : `👤 Event by ${item.created_by ?? 'N/A'}`;
    document.getElementById('modalMeta').innerText = meta;
    document.getElementById('detailModal').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
  }

  function openAddModal(date) {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('date').value = date;

    // Reset other form fields
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('time').value = '';

    <?php if ($role !== 'Student'): ?>
      document.getElementById('type').value = '';
      document.getElementById('faculty').value = '';
      document.getElementById('faculty').disabled = false;
      document.getElementById('class').value = '';
      document.getElementById('class').disabled = false;
    <?php else: ?>
      // Student role: faculty/class inputs disabled in PHP, no action needed here
    <?php endif; ?>
  }

  function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
  }

  <?php if ($role !== 'Student'): ?>
  // Adjust faculty/class required/disabled based on event type select
  document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const facultyInput = document.getElementById('faculty');
    const classInput = document.getElementById('class');

    function adjustFacultyClass() {
      if (typeSelect.value === 'Personal') {
        facultyInput.disabled = true;
        facultyInput.required = false;
        classInput.disabled = true;
        classInput.required = false;
      } else {
        facultyInput.disabled = false;
        facultyInput.required = true;
        classInput.disabled = false;
        classInput.required = true;
      }
    }

    typeSelect.addEventListener('change', adjustFacultyClass);
    adjustFacultyClass();
  });
  <?php endif; ?>
</script>
</body>
</html>
