<?php
require 'db_connect.php';
require 'mail_config.php';

$login_error = '';
$register_message = '';
$show_otp_form = false;
$registration_email = '';
$message_type = 'error';

// Check if user was redirected because they need to log in
if (isset($_GET['error']) && $_GET['error'] == 'login_required') {
    $login_error = "You must be logged in to book a room.";
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $fullName = $_POST['reg-name'];
    $email = $_POST['reg-email'];
    $phone = $_POST['reg-phone'];
    $password = $_POST['reg-password'];
    $confirm_password = $_POST['reg-confirm-password'];

    if (strlen($password) < 6) {
        $register_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $register_message = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM guests WHERE email = ? AND is_verified = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $register_message = "A verified guest account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $otp_expires_at = date("Y-m-d H:i:s", strtotime('+10 minutes'));
            $email_sent_result = send_otp_email($email, $otp);

            if ($email_sent_result === true) {
                $stmt_user = $conn->prepare("INSERT INTO guests (full_name, email, phone_number, password, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name=?, phone_number=?, password=?, otp_code=?, otp_expires_at=?");
                $stmt_user->bind_param("sssssssssss", $fullName, $email, $phone, $hashed_password, $otp, $otp_expires_at, $fullName, $phone, $hashed_password, $otp, $otp_expires_at);
                if ($stmt_user->execute()) {
                    $register_message = "Registration successful! An OTP has been sent to your email address.";
                    $show_otp_form = true;
                    $registration_email = $email;
                    $message_type = 'success';
                } else {
                    $register_message = "Database error. Please contact support.";
                }
            } else {
                $register_message = $email_sent_result;
            }
        }
        $stmt->close();
    }
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['login-email'];
    $password = $_POST['login-password'];
    $stmt = $conn->prepare("SELECT id, password, is_verified FROM guests WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'guest';

                $redirect_url = 'my_bookings.php'; // Default redirect
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                }
                
                // Use JavaScript to save FCM token and then redirect
                echo <<<HTML
                <script>
                    if (window.Android && typeof window.Android.saveFcmToken === 'function') {
                        window.Android.saveFcmToken();
                    }
                    window.location.href = '{$redirect_url}';
                </script>
HTML;
                exit();

            } else {
                $login_error = "Your account is not verified. Please register again to get a new OTP.";
            }
        } else {
            $login_error = "Invalid email or password.";
        }
    } else {
        $login_error = "Invalid email or password.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Access - Mantralayam Rooms Booking</title>
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
            padding: 2rem 0;
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
        <?php if ($show_otp_form): ?>
            <!-- OTP Verification Form -->
            <h1 class="text-primary mb-4">Verify Your Account</h1>
            <p class="alert alert-<?php echo $message_type; ?>"><?php echo $register_message; ?></p>
            <form action="verify.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($registration_email); ?>">
                <div class="mb-3 text-start"><label for="otp" class="form-label">Enter OTP</label><input type="text" class="form-control" id="otp" name="otp" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Verify</button></div>
            </form>
        <?php else: ?>
            <!-- Login/Register Toggle -->
            <div id="login-form">
                <h1 class="text-primary mb-4">Guest Login</h1>
                <?php if ($login_error): ?><p class="alert alert-danger"><?php echo $login_error; ?></p><?php endif; ?>
                <form action="login_user.php" method="POST">
                    <input type="hidden" name="login" value="1">
                    <div class="mb-3 text-start"><label for="login-email" class="form-label">Email</label><input type="email" class="form-control" id="login-email" name="login-email" required></div>
                    <div class="mb-3 text-start"><label for="login-password" class="form-label">Password</label><input type="password" class="form-control password-input" id="login-password" name="login-password" required></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check"><input type="checkbox" class="form-check-input show-password-cb"><label class="form-check-label">Show Password</label></div>
                        <a href="reset_password.php" class="text-decoration-none">Forgot Password?</a>
                    </div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Login</button></div>
                </form>
                <p class="mt-4">Don't have an account? <a href="#" class="fw-bold text-decoration-none" onclick="toggleForms(event)">Register now</a></p>
            </div>
            <div id="registration-form" style="display:none;">
                <h1 class="text-primary mb-4">Guest Registration</h1>
                <?php if ($register_message): ?><p class="alert alert-danger"><?php echo $register_message; ?></p><?php endif; ?>
                <form action="login_user.php" method="POST">
                    <input type="hidden" name="register" value="1">
                    <div class="mb-3 text-start"><label for="reg-name" class="form-label">Full Name</label><input type="text" class="form-control" id="reg-name" name="reg-name" required></div>
                    <div class="mb-3 text-start"><label for="reg-email" class="form-label">Email</label><input type="email" class="form-control" id="reg-email" name="reg-email" required></div>
                    <div class="mb-3 text-start"><label for="reg-phone" class="form-label">Phone Number</label><input type="tel" class="form-control" id="reg-phone" name="reg-phone" required></div>
                    <div class="mb-3 text-start"><label for="reg-password" class="form-label">Password (min. 6 characters)</label><input type="password" class="form-control password-input" id="reg-password" name="reg-password" required></div>
                    <div class="mb-3 text-start"><label for="reg-confirm-password" class="form-label">Confirm Password</label><input type="password" class="form-control" id="reg-confirm-password" name="reg-confirm-password" required></div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Register</button></div>
                </form>
                <p class="mt-4">Already have an account? <a href="#" class="fw-bold text-decoration-none" onclick="toggleForms(event)">Login</a></p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        function toggleForms(event) {
            event.preventDefault();
            document.getElementById('login-form').style.display = document.getElementById('login-form').style.display === 'none' ? 'block' : 'none';
            document.getElementById('registration-form').style.display = document.getElementById('registration-form').style.display === 'none' ? 'block' : 'none';
        }

        document.querySelectorAll('.show-password-cb').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const passwordInput = this.closest('form').querySelector('.password-input');
                passwordInput.type = this.checked ? 'text' : 'password';
            });
        });
    </script>
</body>
</html>
