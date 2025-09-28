<?php
require 'db_connect.php';
require 'mail_config.php';

$login_error = '';
$register_message = '';
$show_otp_form = false;
$registration_email = '';
$message_type = 'error';

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $fullName = $_POST['reg-name'];
    $hotelName = $_POST['reg-hotel-name'];
    $email = $_POST['reg-email'];
    $password = $_POST['reg-password'];

    if (strlen($password) < 6) {
        $register_message = "Password must be at least 6 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM owners WHERE email = ? AND is_verified = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $register_message = "A verified owner account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $otp_expires_at = date("Y-m-d H:i:s", strtotime('+10 minutes'));
            $email_sent_result = send_otp_email($email, $otp);

            if ($email_sent_result === true) {
                $conn->begin_transaction();
                try {
                    $stmt_owner = $conn->prepare("INSERT INTO owners (full_name, email, password, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name=?, password=?, otp_code=?, otp_expires_at=?");
                    $stmt_owner->bind_param("sssssssss", $fullName, $email, $hashed_password, $otp, $otp_expires_at, $fullName, $hashed_password, $otp, $otp_expires_at);
                    $stmt_owner->execute();
                    $owner_id = $stmt_owner->insert_id > 0 ? $stmt_owner->insert_id : $conn->query("SELECT id FROM owners WHERE email = '$email'")->fetch_assoc()['id'];
                    
                    $stmt_hotel = $conn->prepare("INSERT INTO hotels (owner_id, hotel_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE hotel_name=?");
                    $stmt_hotel->bind_param("iss", $owner_id, $hotelName, $hotelName);
                    $stmt_hotel->execute();
                    
                    $conn->commit();
                    $register_message = "Registration initiated! An OTP has been sent to your email address.";
                    $show_otp_form = true;
                    $registration_email = $email;
                    $message_type = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $register_message = "Database error. Please try again.";
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
    $stmt = $conn->prepare("SELECT o.id, o.password, o.status, o.is_verified, h.id as hotel_id FROM owners o JOIN hotels h ON o.id = h.owner_id WHERE o.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 0) {
                $login_error = "Your email is not verified. Please complete the registration process to verify.";
            } elseif ($user['status'] == 'approved') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'owner';
                $_SESSION['hotel_id'] = $user['hotel_id'];

                // --- FIX: Use JavaScript to redirect AFTER saving the token ---
                echo <<<HTML
                <script>
                    // First, try to call the Android function to save the token.
                    if (window.Android && typeof window.Android.saveFcmToken === 'function') {
                        window.Android.saveFcmToken();
                    }
                    // Then, redirect the user after a short delay.
                    setTimeout(function() {
                        window.location.href = 'dashboard/rooms.php';
                    }, 500);
                </script>
HTML;
                exit(); // Stop the script from continuing

            } elseif ($user['status'] == 'pending') {
                $login_error = "Your account is pending approval from an administrator.";
            } else {
                $login_error = "Your account has been suspended.";
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
    <title>Owner Access - Mantralayam Rooms Booking</title>
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
            color: #198754; /* Bootstrap success green */
        }
    </style>
</head>
<body>
    <div class="form-card">
        <?php if ($show_otp_form): ?>
            <!-- OTP Verification Form -->
            <h1 class="mb-4">Verify Your Email</h1>
            <p class="alert alert-<?php echo $message_type; ?>"><?php echo $register_message; ?></p>
            <form action="verify_owner.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($registration_email); ?>">
                <div class="mb-3 text-start"><label for="otp" class="form-label">Enter OTP</label><input type="text" class="form-control" id="otp" name="otp" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Verify Email</button></div>
            </form>
        <?php else: ?>
            <!-- Login/Register Toggle -->
            <div id="login-form">
                <h1 class="mb-4">Owner Portal</h1>
                <?php if ($login_error): ?><p class="alert alert-danger"><?php echo $login_error; ?></p><?php endif; ?>
                <form action="login_owner.php" method="POST">
                    <input type="hidden" name="login" value="1">
                    <div class="mb-3 text-start"><label for="login-email" class="form-label">Email</label><input type="email" class="form-control" id="login-email" name="login-email" required></div>
                    <div class="mb-3 text-start"><label for="login-password" class="form-label">Password</label><input type="password" class="form-control password-input" id="login-password" name="login-password" required></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check"><input type="checkbox" class="form-check-input show-password-cb"><label class="form-check-label">Show Password</label></div>
                        <a href="reset_password_owner.php" class="text-decoration-none">Forgot Password?</a>
                    </div>
                    <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Login</button></div>
                </form>
                <p class="mt-4">Need an owner account? <a href="#" class="fw-bold text-decoration-none" onclick="toggleForms(event)">Register now</a></p>
            </div>
            <div id="registration-form" style="display:none;">
                <h1 class="mb-4">Owner Registration</h1>
                <?php if ($register_message): ?><p class="alert alert-danger"><?php echo $register_message; ?></p><?php endif; ?>
                <form action="login_owner.php" method="POST">
                    <input type="hidden" name="register" value="1">
                    <div class="mb-3 text-start"><label for="reg-name" class="form-label">Full Name</label><input type="text" class="form-control" id="reg-name" name="reg-name" required></div>
                    <div class="mb-3 text-start"><label for="reg-hotel-name" class="form-label">Hotel/Property Name</label><input type="text" class="form-control" id="reg-hotel-name" name="reg-hotel-name" required></div>
                    <div class="mb-3 text-start"><label for="reg-email" class="form-label">Email</label><input type="email" class="form-control" id="reg-email" name="reg-email" required></div>
                    <div class="mb-3 text-start"><label for="reg-password" class="form-label">Password (min. 6 characters)</label><input type="password" class="form-control password-input" id="reg-password" name="reg-password" required></div>
                    <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Register</button></div>
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
