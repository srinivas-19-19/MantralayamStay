<?php
require 'db_connect.php';

// Check if a booking ID was stored in the session
if (!isset($_SESSION['last_booking_id'])) {
    // If not, redirect to the home page to prevent direct access
    header("Location: index.php");
    exit();
}

$booking_id = $_SESSION['last_booking_id'];
// Unset the session variable so this page can't be refreshed with old data
unset($_SESSION['last_booking_id']);

// Fetch all booking details from the database using the ID
$sql = "SELECT 
            b.id as booking_id,
            b.checkin_date,
            b.checkout_date,
            b.total_price,
            b.payment_id,
            r.name as room_name,
            h.hotel_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN hotels h ON r.hotel_id = h.id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking_details = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$booking_details) {
    die("Could not retrieve booking details.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed!</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card text-center border-0 shadow-lg">
                    <div class="card-body p-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h1 class="h2 fw-bold mt-3">Booking Confirmed!</h1>
                        <p class="lead text-muted">Your payment was successful and your room is booked. A confirmation has been sent to your email.</p>
                        <hr class="my-4">
                        <div class="text-start">
                            <p><strong>Booking ID:</strong> #<?php echo htmlspecialchars($booking_details['booking_id']); ?></p>
                            <p><strong>Hotel:</strong> <?php echo htmlspecialchars($booking_details['hotel_name']); ?></p>
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($booking_details['room_name']); ?></p>
                            <p><strong>Check-in:</strong> <?php echo date('d M, Y', strtotime($booking_details['checkin_date'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('d M, Y', strtotime($booking_details['checkout_date'])); ?></p>
                            <p><strong>Total Paid:</strong> ₹<?php echo number_format($booking_details['total_price'], 2); ?></p>
                        </div>
                        <div class="d-grid mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
