<?php

require '../config/db.php';


// $role = $_SESSION['role'];
// $user_id = $_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid event ID");
}
$event_id = (int)$_GET['id'];

// Fetch event
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found");
}

// Permission check: Student can only edit their own personal schedules
if ($role === 'Student') {
    if ($event['created_by'] != $user_id || $event['type'] !== 'Personal') {
        die("Access denied");
    }
} elseif ($role === 'CR' || $role === 'Teacher') {
    if ($event['created_by'] != $user_id && $role !== 'Teacher') {
        die("Access denied");
    }
}

// Allowed types
$allTypes = ['Event', 'Notice', 'Holiday', 'Personal'];
$allowedTypes = ($role === 'Student') ? ['Personal'] : ['Event', 'Notice', 'Holiday', 'Personal'];

$title = $event['title'];
$description = $event['description'];
$date = $event['date'];
$time = $event['time'];
$type = $event['type'];
$faculty = $event['faculty'];
$class = $event['class'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'] ?? null;
    $type = $_POST['type'] ?? '';
    $faculty = $_POST['faculty'] ?? '';
    $class = $_POST['class'] ?? '';

    if (!$title) $errors[] = "Title is required";
    if (!$date) $errors[] = "Date is required";

    if (!in_array($type, $allowedTypes)) {
        $errors[] = "Invalid event type for your role";
    }

    if ($type !== 'Personal') {
        if (!$faculty) $errors[] = "Faculty is required for this event type";
        if (!$class) $errors[] = "Class is required for this event type";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, date=?, time=?, type=?, faculty=?, class=? WHERE id=?");
        $stmt->bind_param("sssssssi", $title, $description, $date, $time, $type, $faculty, $class, $event_id);
        $stmt->execute();

        header("Location: event_list.php");
        exit;
    }
}
?>
 <script src="https://cdn.tailwindcss.com"></script>
<div class="max-w-4xl mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Edit Event / Notice</h2>

    <?php if ($errors): ?>
        <div class="mb-4 p-3 bg-red-200 text-red-800 rounded">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 max-w-md">
        <div>
            <label class="block mb-1 font-semibold" for="title">Title</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" class="w-full border p-2 rounded" required />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="description">Description</label>
            <textarea name="description" id="description" class="w-full border p-2 rounded"><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="date">Date</label>
            <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>" class="w-full border p-2 rounded" required />
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="time">Time</label>
            <input type="time" name="time" id="time" value="<?= htmlspecialchars($time) ?>" class="w-full border p-2 rounded" />
        </div>

        <?php if ($role === 'Student'): ?>
            <input type="hidden" name="type" value="Personal" />
            <p class="italic text-gray-600">Event Type: Personal Schedule (fixed)</p>
        <?php else: ?>
            <div>
                <label class="block mb-1 font-semibold" for="type">Event Type</label>
                <select name="type" id="type" class="w-full border p-2 rounded" required>
                    <?php foreach ($allTypes as $t): 
                        $selected = ($type === $t) ? 'selected' : '';
                    ?>
                        <option value="<?= $t ?>" <?= $selected ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div>
            <label class="block mb-1 font-semibold" for="faculty">Faculty</label>
            <input type="text" name="faculty" id="faculty" value="<?= htmlspecialchars($faculty) ?>" class="w-full border p-2 rounded" <?= ($role === 'Student' || $type === 'Personal') ? 'disabled' : 'required' ?> />
            <?php if ($role === 'Student' || $type === 'Personal'): ?>
                <p class="text-sm text-gray-500 italic">Not required for Personal schedule</p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block mb-1 font-semibold" for="class">Class / Section</label>
            <input type="text" name="class" id="class" value="<?= htmlspecialchars($class) ?>" class="w-full border p-2 rounded" <?= ($role === 'Student' || $type === 'Personal') ? 'disabled' : 'required' ?> />
            <?php if ($role === 'Student' || $type === 'Personal'): ?>
                <p class="text-sm text-gray-500 italic">Not required for Personal schedule</p>
            <?php endif; ?>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Changes</button>
    </form>
</div>
