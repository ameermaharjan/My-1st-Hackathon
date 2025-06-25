<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <form action="../process_login.php" method="POST" class="bg-white p-6 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4">Login</h2>
    <input name="email" type="email" placeholder="Email" required class="w-full p-2 border mb-3 rounded" />
    <input name="password" type="password" placeholder="Password" required class="w-full p-2 border mb-3 rounded" />
    <button type="submit" class="bg-blue-600 text-white p-2 w-full rounded">Login</button>
    <p class="mt-2 text-sm">No account? <a href="register.php" class="text-blue-500">Register</a></p>
  </form>
</body>
</html>
