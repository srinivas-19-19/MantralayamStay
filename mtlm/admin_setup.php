<?php
require 'db_connect.php';

$message = '';
$message_type = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Username and password cannot be empty.";
        $message_type = 'error';
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new admin into the database
        $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $message = "Admin user '{$username}' created successfully! You can now log in.";
            $message_type = 'success';
        } else {
            $message = "Error: Could not create admin user. It might already exist.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Account Setup</title>
    <style>
        body { font-family: sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; width: 400px; }
        .message { padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; font-size: 1.1rem; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .info { background-color: #cce5ff; color: #004085; }
        input { width: 100%; padding: 10px; box-sizing: border-box; margin-bottom: 1rem; }
        button { width: 100%; padding: 10px; background-color: #007BFF; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Account Setup</h1>
        <?php if ($message): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
        <?php else: ?>
            <p class="message info">Create your admin account. Use a simple username like 'admin'.</p>
        <?php endif; ?>
        <form method="POST" action="admin_setup.php">
            <input type="text" name="username" placeholder="Choose a Username" required>
            <input type="password" name="password" placeholder="Choose a Password" required>
            <button type="submit">Create Admin</button>
        </form>
        <p style="margin-top: 1.5rem;"><a href="admin/login.php">Go to Admin Login</a></p>
    </div>
</body>
</html>
