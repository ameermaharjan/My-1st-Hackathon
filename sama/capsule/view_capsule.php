<?php
session_start();
require '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("Invalid capsule ID.");
}

$stmt = $conn->prepare("SELECT * FROM capsules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$capsule = $result->fetch_assoc();

if (!$capsule) {
    die("Capsule not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>View Capsule - <?= htmlspecialchars($capsule['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center p-6">

    <div class="max-w-xl w-full bg-white rounded shadow p-6">

        <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($capsule['title']) ?></h1>

        <div class="mb-4 space-y-1 text-gray-700">
            <p><strong>Type:</strong> <?= ucfirst(htmlspecialchars($capsule['type'])) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($capsule['date']) ?> <?= htmlspecialchars($capsule['time']) ?></p>
            <p><strong>Class:</strong> <?= htmlspecialchars($capsule['class']) ?></p>
            <p><strong>Faculty:</strong> <?= htmlspecialchars($capsule['faculty']) ?></p>
        </div>

        <div class="mb-6 whitespace-pre-wrap text-gray-800">
            <?= nl2br(htmlspecialchars($capsule['description'])) ?>
        </div>

        <?php if (!empty($capsule['file'])): ?>
            <p class="mb-6">
                <a href="uploads/capsules/<?= rawurlencode($capsule['file']) ?>" 
                   class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                   download>
                    📎 Download Attachment
                </a>
            </p>
        <?php endif; ?>

        <a href="capsule_list_admin.php" 
           class="inline-block text-blue-600 hover:underline">
           ← Back to Capsule List
        </a>

    </div>

</body>
</html>
