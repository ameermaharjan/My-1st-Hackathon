<?php
session_start();
require '../config/db.php';

$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Validate notice ID
$notice_id = $_GET['id'] ?? '';
if (!$notice_id) {
    die("Notice ID is missing.");
}

// Fetch notice data
$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$result = $stmt->get_result();
$notice = $result->fetch_assoc();

if (!$notice) {
    die("Notice not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        h2 { margin-bottom: 10px; }
        .field { margin-bottom: 10px; }
        label { font-weight: bold; display: block; }
        a { color: blue; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <h2>Notice Details</h2>

    <div class="field">
        <label>Title:</label>
        <div><?= htmlspecialchars($notice['title']) ?></div>
    </div>

    <div class="field">
        <label>Description:</label>
        <div><?= nl2br(htmlspecialchars($notice['description'])) ?></div>
    </div>

    <div class="field">
        <label>Type:</label>
        <div><?= htmlspecialchars($notice['type']) ?></div>
    </div>

    <div class="field">
        <label>Date:</label>
        <div><?= htmlspecialchars($notice['date']) ?></div>
    </div>

    <div class="field">
        <label>Time:</label>
        <div><?= htmlspecialchars($notice['time']) ?></div>
    </div>

    <div class="field">
        <label>Faculty:</label>
        <div><?= htmlspecialchars($notice['faculty']) ?></div>
    </div>

    <div class="field">
        <label>Class:</label>
        <div><?= htmlspecialchars($notice['class']) ?></div>
    </div>

    <?php if (!empty($notice['homework_file'])): ?>
    <div class="field">
        <label>Attached File:</label>
        <div><a href="../uploads/<?= htmlspecialchars($notice['homework_file']) ?>" download>Download File</a></div>
    </div>
    <?php endif; ?>

    <div class="field">
        <label>Created At:</label>
        <div><?= htmlspecialchars($notice['created_at']) ?></div>
    </div>

    <p><a href="notice_list.php">← Back to Notice List</a></p>
</div>

</body>
</html>
