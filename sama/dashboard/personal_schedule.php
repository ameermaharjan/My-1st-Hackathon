<?php
require '../config/db.php';
session_start();

$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if ($role !== 'Student') {
    http_response_code(403);
    echo "Access denied. Personal schedule is for Students only.";
    exit;
}

// Handle POST requests for edit and delete on this page itself:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit') {
            // Validate inputs
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $date = $_POST['date'];
            $time = $_POST['time'] ?: null;
            $description = trim($_POST['description']);

            if ($title && $date) {
                // FIXED bind_param string here (4 strings + 2 ints)
                $stmt = $conn->prepare("UPDATE events SET title = ?, date = ?, time = ?, description = ? WHERE id = ? AND created_by = ? AND type = 'Personal'");
                $stmt->bind_param("ssssii", $title, $date, $time, $description, $id, $user_id);
                $stmt->execute();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND created_by = ? AND type = 'Personal'");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
        }
    }
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch personal schedules
$stmt = $conn->prepare("SELECT * FROM events WHERE type = 'Personal' AND created_by = ? ORDER BY date ASC, time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<script src="https://cdn.tailwindcss.com"></script>

<div class="max-w-4xl mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">My Personal Schedule</h2>

    <?php if ($result->num_rows === 0): ?>
        <p>You have no personal schedules yet.</p>
    <?php else: ?>
        <table class="min-w-full border border-gray-300 rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-1">Title</th>
                    <th class="border px-3 py-1">Date</th>
                    <th class="border px-3 py-1">Time</th>
                    <th class="border px-3 py-1">Description</th>
                    <th class="border px-3 py-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="border px-3 py-1"><?= htmlspecialchars($row['title']) ?></td>
                        <td class="border px-3 py-1 text-center"><?= $row['date'] ?></td>
                        <td class="border px-3 py-1 text-center"><?= $row['time'] ?: '-' ?></td>
                        <td class="border px-3 py-1"><?= htmlspecialchars($row['description']) ?></td>
                        <td class="border px-3 py-1 text-center">
                            <button 
                                class="text-yellow-600 hover:underline mr-2 edit-btn" 
                                data-id="<?= $row['id'] ?>" 
                                data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                                data-date="<?= $row['date'] ?>"
                                data-time="<?= $row['time'] ?>"
                                data-description="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>"
                            >Edit</button>
                            
                            <form method="post" class="inline" onsubmit="return confirm('Delete this schedule?');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= $row['id'] ?>" />
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="mt-4">
        <a href="../events/add_event.php?type=Personal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + Add New Personal Schedule
        </a>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg max-w-lg w-full p-6">
        <h3 class="text-xl font-semibold mb-4">Edit Personal Schedule</h3>
        <form method="post" id="editForm" class="space-y-4">
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="id" id="edit-id" />
            
            <div>
                <label class="block font-semibold mb-1" for="edit-title">Title</label>
                <input type="text" id="edit-title" name="title" required class="w-full border rounded px-3 py-2" />
            </div>
            
            <div>
                <label class="block font-semibold mb-1" for="edit-date">Date</label>
                <input type="date" id="edit-date" name="date" required class="w-full border rounded px-3 py-2" />
            </div>
            
            <div>
                <label class="block font-semibold mb-1" for="edit-time">Time</label>
                <input type="time" id="edit-time" name="time" class="w-full border rounded px-3 py-2" />
            </div>
            
            <div>
                <label class="block font-semibold mb-1" for="edit-description">Description</label>
                <textarea id="edit-description" name="description" rows="4" class="w-full border rounded px-3 py-2"></textarea>
            </div>
            
            <div class="flex justify-end space-x-4">
                <button type="button" id="editCancelBtn" class="px-4 py-2 rounded border hover:bg-gray-100">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal elements
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const editCancelBtn = document.getElementById('editCancelBtn');

    // Open modal and prefill form on Edit button click
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            editModal.classList.remove('hidden');
            document.getElementById('edit-id').value = btn.dataset.id;
            document.getElementById('edit-title').value = btn.dataset.title;
            document.getElementById('edit-date').value = btn.dataset.date;
            document.getElementById('edit-time').value = btn.dataset.time;
            document.getElementById('edit-description').value = btn.dataset.description;
        });
    });

    // Close modal on Cancel
    editCancelBtn.addEventListener('click', () => {
        editModal.classList.add('hidden');
    });

    // Close modal if clicked outside modal content
    window.addEventListener('click', (e) => {
        if (e.target === editModal) {
            editModal.classList.add('hidden');
        }
    });
</script>
