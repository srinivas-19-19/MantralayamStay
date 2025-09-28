<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../db_connect.php';
require '../mail_config.php';

$login_error = '';
$show_otp_form = false;

// Check if the user is already in the process of entering an OTP
if (isset($_SESSION['admin_awaiting_otp']) && $_SESSION['admin_awaiting_otp'] === true) {
    $show_otp_form = true;
}

// Handle initial username/password submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin['password'])) {
            // Password is correct, now send OTP
            $otp = rand(100000, 999999);
            $otp_expires_at = date("Y-m-d H:i:s", strtotime('+10 minutes'));

            // Send the email
            $email_sent_result = send_admin_otp_email($admin['email'], $otp);

            if ($email_sent_result === true) {
                // Update database with OTP
                $stmt_otp = $conn->prepare("UPDATE admins SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
                $stmt_otp->bind_param("ssi", $otp, $otp_expires_at, $admin['id']);
                $stmt_otp->execute();
                $stmt_otp->close();

                // Set session flags to show the OTP form
                $_SESSION['admin_awaiting_otp'] = true;
                $_SESSION['admin_login_email'] = $admin['email'];
                $show_otp_form = true;
            } else {
                $login_error = "Could not send OTP email. " . $email_sent_result;
            }
        } else {
            $login_error = "Invalid username or password.";
        }
    } else {
        $login_error = "Invalid username or password.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; background-color: #343a40; }
        .login-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 350px; text-align: center; }
        h1 { margin-bottom: 1.5rem; }
        .input-group { margin-bottom: 1rem; text-align: left; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .error { color: #dc3545; margin-bottom: 1rem; }
        .info { color: #004085; background-color: #cce5ff; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <?php if ($show_otp_form): ?>
            <h1>Enter Verification Code</h1>
            <p class="info">An OTP has been sent to your registered email address.</p>
            <form method="POST" action="verify_otp.php">
                <div class="input-group">
                    <label for="otp">One-Time Password</label>
                    <input type="text" id="otp" name="otp" required>
                </div>
                <button type="submit">Verify</button>
            </form>
        <?php else: ?>
            <h1>Admin Login</h1>
            <?php if ($login_error): ?><p class="error"><?php echo $login_error; ?></p><?php endif; ?>
            <form method="POST" action="login.php">
                <input type="hidden" name="login" value="1">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
