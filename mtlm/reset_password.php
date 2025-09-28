<?php
require 'db_connect.php';
require 'mail_config.php';

$step = $_GET['step'] ?? 1;
$message = '';
$message_type = 'info';
$email = $_SESSION['reset_email'] ?? '';

// --- Step 1: Handle Email Submission ---
if ($step == 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM guests WHERE email = ? AND is_verified = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = rand(100000, 999999);
        $otp_expires_at = date("Y-m-d H:i:s", strtotime('+10 minutes'));
        
        $email_sent_result = send_otp_email($email, $otp);
        if ($email_sent_result === true) {
            $stmt_update = $conn->prepare("UPDATE guests SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
            $stmt_update->bind_param("sss", $otp, $otp_expires_at, $email);
            $stmt_update->execute();
            
            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php?step=2");
            exit();
        } else {
            $message = "Could not send OTP email. " . $email_sent_result;
            $message_type = 'danger';
        }
    } else {
        $message = "No verified account found with that email address.";
        $message_type = 'danger';
    }
}

// --- Step 2: Handle OTP and New Password Submission ---
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];

    if (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM guests WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if ($user['otp_expires_at'] < date("Y-m-d H:i:s")) {
                $message = "OTP has expired. Please try again.";
                $message_type = 'danger';
            } elseif ($user['otp_code'] == $otp) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE guests SET password = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $stmt_update->bind_param("si", $hashed_password, $user['id']);
                if ($stmt_update->execute()) {
                    unset($_SESSION['reset_email']);
                    $message = "Password has been reset successfully! You can now log in.";
                    $message_type = 'success';
                    $step = 3; // Move to final success step
                }
            } else {
                $message = "Invalid OTP provided.";
                $message_type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Mantralayam Rooms Booking</title>
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
        .form-card {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .form-card h1 {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="form-card">
        
        <?php if ($step == 1): ?>
            <h1 class="text-primary mb-3">Forgot Password</h1>
            <p class="text-muted mb-4">Enter your email address and we will send you an OTP to reset your password.</p>
            <?php if($message && $message_type == 'danger'): ?><p class="alert alert-danger"><?php echo $message; ?></p><?php endif; ?>
            <form method="POST" action="reset_password.php?step=1">
                <div class="mb-3 text-start"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Send OTP</button></div>
            </form>
        <?php elseif ($step == 2): ?>
            <h1 class="text-primary mb-3">Reset Your Password</h1>
            <p class="alert alert-info">An OTP has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>. Please enter it below.</p>
            <?php if($message): ?><p class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></p><?php endif; ?>
            <form method="POST" action="reset_password.php?step=2">
                <div class="mb-3 text-start"><label class="form-label">Enter OTP</label><input type="text" name="otp" class="form-control" required></div>
                <div class="mb-3 text-start"><label class="form-label">New Password (min. 6 characters)</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Reset Password</button></div>
            </form>
        <?php elseif ($step == 3): ?>
            <h1 class="text-success mb-3">Success!</h1>
            <p class="alert alert-success"><?php echo $message; ?></p>
            <div class="d-grid"><a href="login_user.php" class="btn btn-primary btn-lg">Go to Login</a></div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="login_portal.php" class="text-secondary text-decoration-none">&larr; Back to Login Portal</a>
        </div>
    </div>
</body>
</html>
