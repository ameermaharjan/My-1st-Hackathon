<?php
session_start();
require '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get session variables
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';
$faculty = $_SESSION['faculty'] ?? '';
$class   = $_SESSION['class'] ?? '';

$errors = [];
$title = $description = $type = $date = $time = '';
$homework_file = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type        = $_POST['type'] ?? '';
    $date        = $_POST['date'] ?? '';
    $time        = $_POST['time'] ?? '';
    $faculty     = trim($_POST['faculty'] ?? '');
    $class       = trim($_POST['class'] ?? '');

    // Validation
    if ($title === '') $errors[] = "Title is required.";
    if (!in_array($type, ['homework', 'others'])) $errors[] = "Invalid type selected.";
    if ($date === '') $errors[] = "Date is required.";

    // Handle file upload
    if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $tmp_name = $_FILES['homework_file']['tmp_name'];
        $file_name = basename($_FILES['homework_file']['name']);
        $file_path = $upload_dir . time() . '_' . $file_name;

        if (move_uploaded_file($tmp_name, $file_path)) {
            $homework_file = basename($file_path);
        } else {
            $errors[] = "File upload failed.";
        }
    }

    // Insert into DB
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO notices (title, description, date, time, type, faculty, class, homework_file, created_by, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        // 🔧 Fixed bind_param: 9 parameters + 1 integer = 9 's' + 1 'i'
        $stmt->bind_param("ssssssssi", $title, $description, $date, $time, $type, $faculty, $class, $homework_file, $user_id);

        if ($stmt->execute()) {
            header("Location: notice_list.php?success=1");
            exit;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Notice</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; }
        form { max-width: 600px; margin: auto; }
        label { font-weight: bold; display: block; margin-top: 12px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 20px; padding: 10px 15px; }
    </style>
</head>
<body>

<h2>Add New Notice</h2>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label for="title">Title:</label>
    <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>

    <label for="description">Description:</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($description) ?></textarea>

    <label for="date">Date:</label>
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>

    <label for="time">Time:</label>
    <input type="time" name="time" value="<?= htmlspecialchars($time) ?>">

    <label for="type">Type:</label>
    <select name="type" required>
        <option value="">-- Select Type --</option>
        <option value="homework" <?= $type === 'homework' ? 'selected' : '' ?>>Homework</option>
        <option value="others" <?= $type === 'others' ? 'selected' : '' ?>>Others</option>
    </select>

    <label for="faculty">Faculty:</label>
    <input type="text" name="faculty" value="<?= htmlspecialchars($faculty) ?>">

    <label for="class">Class:</label>
    <input type="text" name="class" value="<?= htmlspecialchars($class) ?>">

    <label for="homework_file">Homework File (optional):</label>
    <input type="file" name="homework_file">

    <button type="submit">Submit Notice</button>
</form>

<p><a href="notice_list.php">← Back to Notice List</a></p>

</body>
</html>
