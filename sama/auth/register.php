<?php include '../config/db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <form action="../process_register.php" method="POST" class="bg-white p-6 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4">Register</h2>
    <input name="name" type="text" placeholder="Name" required class="w-full p-2 border mb-3 rounded" />
    <input name="email" type="email" placeholder="Email" required class="w-full p-2 border mb-3 rounded" />
    <input name="password" type="password" placeholder="Password" required class="w-full p-2 border mb-3 rounded" />
    <select name="role" required class="w-full p-2 border mb-3 rounded">
      <option value="">Select Role</option>
      <option value="Student">Student</option>
      <option value="CR">CR</option>
      <option value="Teacher">Teacher</option>
    </select>
    <input name="faculty" type="text" placeholder="Faculty (e.g., Science)" required class="w-full p-2 border mb-3 rounded" />
    <input name="class" type="text" placeholder="Class (e.g., BSc CSIT 2nd)" required class="w-full p-2 border mb-3 rounded" />
    <button type="submit" class="bg-blue-600 text-white p-2 w-full rounded">Register</button>
    <p class="mt-2 text-sm">Already have an account? <a href="login.php" class="text-blue-500">Login</a></p>
  </form>
</body>
</html>
