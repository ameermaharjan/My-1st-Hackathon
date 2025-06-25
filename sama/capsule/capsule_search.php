<?php
// require '../config/db.php';

// $search = $_GET['search'] ?? '';
// $type = $_GET['type'] ?? '';

// $query = "SELECT * FROM capsules WHERE title LIKE ? AND type LIKE ?";
// $stmt = $conn->prepare($query);
// $stmt->execute(["%$search%", "%$type%"]);
// $capsules = $stmt->fetchAll();
// ?>

// <form method="GET" class="p-4 bg-white shadow rounded flex gap-2">
//     <input type="text" name="search" placeholder="Search title..." class="p-2 border rounded w-1/2" />
//     <select name="type" class="p-2 border rounded">
//         <option value="">All Types</option>
//         <option value="event">Event</option>
//         <option value="reminder">Reminder</option>
//         <option value="notice">Notice</option>
//     </select>
//     <button class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
// </form>

// <?php foreach ($capsules as $c): ?>
// <div class="border p-3 mt-2">
//     <h3 class="text-lg font-semibold"><?= $c['title'] ?> (<?= ucfirst($c['type']) ?>)</h3>
//     <p><?= $c['date'] ?> - <?= $c['class'] ?> <?= $c['faculty'] ?></p>
// </div>
<?php endforeach; ?>
