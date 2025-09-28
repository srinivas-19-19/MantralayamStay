<?php
require '../db_connect.php';

$error_message = '';

// Check if the user is in the "awaiting OTP" state
if (!isset($_SESSION['admin_awaiting_otp']) || !isset($_SESSION['admin_login_email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $email = $_SESSION['admin_login_email'];
    $otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($admin = $result->fetch_assoc()) {
        if ($admin['otp_expires_at'] < date("Y-m-d H:i:s")) {
            $error_message = "OTP has expired. Please try logging in again.";
        } elseif ($admin['otp_code'] == $otp) {
            // OTP is correct! Log the admin in.
            
            // Clear OTP from database
            $conn->query("UPDATE admins SET otp_code = NULL, otp_expires_at = NULL WHERE id = {$admin['id']}");

            // Set the final admin session
            $_SESSION['admin_id'] = $admin['id'];
            
            // Unset temporary session variables
            unset($_SESSION['admin_awaiting_otp']);
            unset($_SESSION['admin_login_email']);

            header("Location: index.php");
            exit();
        } else {
            $error_message = "Invalid OTP. Please try again.";
        }
    } else {
        $error_message = "An error occurred. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <!-- Your admin login CSS can be linked here -->
</head>
<body>
    <!-- This is a simple error display. You can style it like your login page. -->
    <h1>Verification Failed</h1>
    <p><?php echo $error_message; ?></p>
    <a href="login.php">Try Again</a>
</body>
</html>
