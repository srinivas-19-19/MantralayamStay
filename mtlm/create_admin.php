<?php
$new_hash = '';
$password_to_hash = '';
$admin_exists = false;
$sql_query = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['new_password'])) {
    // We need a database connection to check if the admin exists.
    // Make sure db_connect.php is in the parent directory.
    require 'db_connect.php';

    $password_to_hash = $_POST['new_password'];
    // Generate a secure hash using your server's PHP environment
    $new_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);

    // Check if an admin user already exists
    $result = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
    if ($result && $result->num_rows > 0) {
        $admin_exists = true;
    }
    $conn->close();

    // Prepare the correct SQL query based on whether the admin exists
    if ($admin_exists) {
        // If admin exists, create an UPDATE query
        $sql_query = "UPDATE `admins` SET `password` = '{$new_hash}' WHERE `username` = 'admin';";
    } else {
        // If admin does not exist, create an INSERT query
        $sql_query = "INSERT INTO `admins` (`username`, `password`) VALUES ('admin', '{$new_hash}');";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Account Setup Tool</title>
    <style>
        body { font-family: sans-serif; background-color: #f0f2f5; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 2rem 0; }
        .container { background: white; padding: 2rem 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; max-width: 700px; }
        h1 { color: #007BFF; }
        form { margin: 2rem 0; }
        input { font-size: 1.1rem; padding: 10px; width: 70%; border: 1px solid #ccc; border-radius: 4px; }
        button { font-size: 1.1rem; padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
        .result { margin-top: 2rem; text-align: left; }
        .result h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
        code { background-color: #e9ecef; padding: 15px; display: block; border-radius: 4px; word-wrap: break-word; line-height: 1.6; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Account Setup Tool</h1>
        <p>Enter a secure password for the 'admin' user below to generate the correct SQL query.</p>
        
        <form method="POST" action="create_admin.php">
            <input type="text" name="new_password" placeholder="Enter new admin password" required>
            <button type="submit">Generate SQL</button>
        </form>

        <?php if ($sql_query): ?>
            <div class="result">
                <?php if ($admin_exists): ?>
                    <h2>Reset Admin Password</h2>
                    <p>An 'admin' user already exists. Run the following SQL query in phpMyAdmin to reset its password to "<strong><?php echo htmlspecialchars($password_to_hash); ?></strong>".</p>
                <?php else: ?>
                    <h2>Create Admin User</h2>
                    <p>No 'admin' user found. Run the following SQL query in phpMyAdmin to create one with the password "<strong><?php echo htmlspecialchars($password_to_hash); ?></strong>".</p>
                <?php endif; ?>
                
                <code>
                    <?php echo $sql_query; ?>
                </code>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
