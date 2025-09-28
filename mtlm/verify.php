<?php
require 'db_connect.php';

$message = '';
$message_type = 'danger'; // Default to error
$login_link_visible = true;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['otp'])) {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM guests WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $current_time = date("Y-m-d H:i:s");
        if ($user['otp_expires_at'] < $current_time) {
            $message = "OTP has expired. Please register again.";
        } elseif ($user['otp_code'] == $otp) {
            $update_stmt = $conn->prepare("UPDATE guests SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            if ($update_stmt->execute()) {
                $message_type = 'success';
                
                // Auto-login and Redirect Logic
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'guest';
                
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    echo "<script>setTimeout(function(){ window.location.href = '{$redirect_url}'; }, 2000);</script>";
                    $message = "Success! Redirecting you to your booking page...";
                } else {
                    echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 2000);</script>";
                     $message = "Success! Redirecting you to your bookings page...";
                }
                $login_link_visible = false; // Hide the manual login button
                
            } else {
                $message = "An error occurred. Please try again.";
            }
            $update_stmt->close();
        } else {
            $message = "Invalid OTP. Please try again.";
        }
    } else {
        $message = "Invalid email or this account is already verified.";
    }
    $stmt->close();
} else {
    $message = "Invalid request.";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - Mantralayam Rooms Booking</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            width: 100%;
            max-width: 500px;
        }
        .card-body {
            padding: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="card text-center border-0 shadow-lg">
        <div class="card-body">
            <h1 class="h3 fw-bold mb-4">Account Verification</h1>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php if ($login_link_visible): ?>
                <div class="d-grid">
                    <a href="login_user.php" class="btn btn-primary btn-lg">Go to Login Page</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
