<?php

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
    class="p-2 border rounded relative hover:bg-gray-100 cursor-default <?= $isToday ? 'bg-blue-100 border-blue-500' : 'bg-white' ?>"
  >
    <div class="flex justify-between items-center font-bold <?= $isToday ? 'text-blue-600' : '' ?>">
      <span><?= $day ?></span>

      <?php if (in_array($role, ['Teacher', 'Student'])): ?>
        <button 
          type="button"
          class="text-green-600 text-lg font-bold hover:text-green-800"
          aria-label="View events for <?= $fullDate ?>"
          onclick="openDayPopup('<?= $day ?>', '<?= $fullDate ?>')"
        >
          👁️
        </button>
      <?php endif; ?>
    </div>

    <?php if (!empty($events[$day])): ?>
      <?php foreach($events[$day] as $e): 
        // Capsules visible only for Admin
        if ($e['source'] === 'capsule' && $role !== 'Admin') continue;

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

<!-- Day Events Popup Modal -->
<div id="dayEventsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50 p-4">
  <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[80vh] overflow-y-auto p-6 relative">
    <button 
      onclick="closeDayPopup()" 
      class="absolute top-2 right-3 text-gray-600 text-2xl font-bold hover:text-gray-900" 
      aria-label="Close"
    >
      &times;
    </button>
    <h3 id="dayEventsTitle" class="text-xl font-semibold mb-4"></h3>
    <div id="dayEventsContent" class="space-y-3 text-sm text-gray-700"></div>
  </div>
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

  // Opens the modal and fills it with events of the clicked day
  function openDayPopup(day, fullDate) {
    const modal = document.getElementById('dayEventsModal');
    const title = document.getElementById('dayEventsTitle');
    const content = document.getElementById('dayEventsContent');

    title.textContent = `Events on ${fullDate}`;

    // Clear previous content
    content.innerHTML = '';

    // Events data from PHP embedded as JS object
    const events = <?= json_encode($events) ?>;

    if (!events[day] || events[day].length === 0) {
      content.innerHTML = '<p>No events found.</p>';
    } else {
      events[day].forEach(event => {
        // Skip capsules for non-admin
        if (event.source === 'capsule' && '<?= $role ?>' !== 'Admin') return;

        const div = document.createElement('div');
        div.classList.add('border', 'p-2', 'rounded', 'bg-gray-100');

        div.innerHTML = `
          <strong>${event.title}</strong><br>
          <em>${event.date} ${event.time ? '🕒 ' + event.time : ''}</em><br>
          <p>${event.description ? event.description.replace(/\n/g, '<br>') : ''}</p>
          <small>${event.source === 'capsule' ? '🎒 Capsule by ' + (event.role ?? 'N/A') : '👤 Event by ' + (event.created_by ?? 'N/A')}</small>
        `;
        content.appendChild(div);
      });
    }

    modal.classList.remove('hidden');
  }

  // Close day events modal
  function closeDayPopup() {
    document.getElementById('dayEventsModal').classList.add('hidden');
  }

  // Close modal if clicked outside content box
  document.getElementById('dayEventsModal').addEventListener('click', (e) => {
    if (e.target.id === 'dayEventsModal') {
      closeDayPopup();
    }
  });
</script>

</body>
</html>
