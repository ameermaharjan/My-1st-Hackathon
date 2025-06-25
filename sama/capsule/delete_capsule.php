<?php
session_start();
require '../config/db.php';

$role     = $_SESSION['role']     ?? null;
$user_id  = $_SESSION['user_id']  ?? null;
$faculty  = $_SESSION['faculty']  ?? null;
$class    = $_SESSION['class']    ?? null;

// Delete logic
if (isset($_GET['delete']) && isset($_GET['source'])) {
    $id = $_GET['delete'];
    $source = $_GET['source'];
    
    if ($source === 'event') {
        $stmt = $conn->prepare("DELETE FROM events WHERE id=? AND created_by=?");
    } else {
        $stmt = $conn->prepare("DELETE FROM capsules WHERE id=? AND created_by=?");
    }
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: calendar.php");
    exit();
}

// Fetching Events and Capsules logic same as before...
// We assume $events array populated with each item having ['id', 'source', ...]
?>

<!-- Inside HTML calendar rendering -->
<?php foreach($events[$day] as $e): ?>
  <?php $editLink = "edit_item.php?id={$e['id']}&source={$e['source']}"; ?>
  <div class="flex justify-between items-center">
    <button onclick="event.stopPropagation(); showDetail(<?= json_encode($e) ?>)"
            class="text-white text-xs px-2 py-0.5 mt-1 rounded-full <?= $color ?>">
      <?= $e['source']=='capsule' ? 'Capsule' : $e['type'] ?>
    </button>
    <?php if ($e['created_by'] == $user_id): ?>
      <a href="<?= $editLink ?>" class="text-blue-500 text-xs ml-1">Edit</a>
      <a href="?delete=<?= $e['id'] ?>&source=<?= $e['source'] ?>"
         onclick="return confirm('Delete this item?')"
         class="text-red-500 text-xs ml-1">Del</a>
    <?php endif; ?>
  </div>
<?php endforeach; ?>