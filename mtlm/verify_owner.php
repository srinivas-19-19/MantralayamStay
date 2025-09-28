<?php
require 'db_connect.php';

$message = '';
$message_type = 'danger'; // Default to error

// Check if form was submitted with email and OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['otp'])) {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Find an owner with the given email who is not yet verified
    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM owners WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $current_time = date("Y-m-d H:i:s");
        
        // Check if OTP has expired
        if ($user['otp_expires_at'] < $current_time) {
            $message = "OTP has expired. Please register again to get a new one.";
        } elseif ($user['otp_code'] == $otp) {
            // OTP is correct, update user to verified
            $update_stmt = $conn->prepare("UPDATE owners SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                $message = "Verification successful! Your account is now pending admin approval. You will be able to log in once an admin approves your account.";
                $message_type = 'success';
            } else {
                $message = "An error occurred. Please try again.";
            }
            $update_stmt->close();
        } else {
            // Incorrect OTP
            $message = "Invalid OTP. Please try again.";
        }
    } else {
        $message = "Invalid email or this account is already verified.";
    }
    $stmt->close();
} else {
    $message = "Invalid request. Please submit the form.";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Account Verification - Mantralayam Rooms Booking</title>
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
        .card h1 {
            font-weight: 600;
            color: #198754; /* Bootstrap success green for owner theme */
        }
    </style>
</head>
<body>
    <div class="card text-center border-0 shadow-lg">
        <div class="card-body">
            <h1 class="h3 mb-4">Owner Account Verification</h1>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <div class="d-grid">
                <a href="login_owner.php" class="btn btn-success btn-lg">Go to Owner Login</a>
            </div>
        </div>
    </div>
</body>
</html>
