<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    header("Location: login_user.php");
    exit();
}

// Validate that a booking ID is provided and is numeric
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id = $_GET['booking_id'];
$guest_id = $_SESSION['user_id'];

// Fetch all details for the invoice, including owner contact information
$sql = "SELECT 
            b.id as booking_id, b.checkin_date, b.checkout_date, b.total_price, b.payment_id, b.booked_at,
            r.name as room_name, h.hotel_name,
            g.full_name as guest_name, g.email as guest_email, g.phone_number as guest_phone,
            o.full_name as owner_name, o.mobile_number as owner_phone
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN hotels h ON r.hotel_id = h.id
        JOIN guests g ON b.guest_id = g.id
        JOIN owners o ON h.owner_id = o.id
        WHERE b.id = ? AND b.guest_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
$conn->close();

// If no booking is found, stop execution
if (!$booking) {
    die("Booking not found or you do not have permission to view it.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Invoice - #<?php echo $booking['booking_id']; ?></title>
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
        }
        .invoice-header {
            background-color: #343a40;
            color: white;
        }
        @media print {
            body {
                background-color: white;
            }
            .no-print {
                display: none;
            }
            .invoice-container {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-light bg-white shadow-sm no-print">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">Mantralayam Rooms Booking</a>
            <div>
                <a href="my_bookings.php" class="btn btn-outline-secondary">My Bookings</a>
                <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill"></i> Print Invoice</button>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="card border-0 shadow-lg invoice-container">
            <div class="card-header invoice-header text-center py-4">
                <h1 class="mb-0">Booking Invoice</h1>
                <p class="mb-0">Booking ID: #<?php echo htmlspecialchars($booking['booking_id']); ?></p>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold">Billed To:</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['guest_email']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <h5 class="fw-bold">Property Contact:</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['owner_name']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['owner_phone']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['hotel_name']); ?></p>
                    </div>
                </div>

                <div class="row mb-4 pb-4 border-bottom">
                    <div class="col-md-6">
                        <h5 class="fw-bold">Booking Date & Time:</h5>
                        <p class="mb-0"><?php echo date('d M, Y, h:i A', strtotime($booking['booked_at'])); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <h5 class="fw-bold">Payment ID:</h5>
                        <p class="mb-0"><?php echo htmlspecialchars($booking['payment_id']); ?></p>
                    </div>
                </div>

                <table class="table table-borderless">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col">Description</th>
                            <th scope="col">Dates</th>
                            <th scope="col" class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($booking['checkin_date'])); ?> - <?php echo date('d M, Y', strtotime($booking['checkout_date'])); ?></td>
                            <td class="text-end">₹<?php echo number_format($booking['total_price'], 2); ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold fs-5">
                            <td colspan="2" class="text-end border-top pt-3">Total Paid</td>
                            <td class="text-end border-top pt-3">₹<?php echo number_format($booking['total_price'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
