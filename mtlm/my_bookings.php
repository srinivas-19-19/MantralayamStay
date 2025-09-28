<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    header("Location: login_user.php");
    exit();
}

$guest_id = $_SESSION['user_id'];
$bookings = [];

// Fetch all booking history for the logged-in guest
$sql = "SELECT b.id as booking_id, b.checkin_date, b.checkout_date, b.total_price, b.booked_at, r.name as room_name, r.image as room_image, h.hotel_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN hotels h ON r.hotel_id = h.id
        WHERE b.guest_id = ?
        ORDER BY b.booked_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Mantralayam Rooms Booking</title>
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
        .page-header {
            background-color: #343a40;
            color: white;
            padding: 3rem 1rem;
        }
        .booking-card {
            transition: box-shadow 0.3s ease;
        }
        .booking-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .footer {
            background-color: #343a40;
            color: #adb5bd;
        }
        .footer a {
            color: #fff;
            text-decoration: none;
        }
        .footer a:hover {
            color: #0d6efd;
        }
        .mobile-footer {
            display: none;
        }
        @media (max-width: 768px) {
            .mobile-footer { display: block; position: fixed; bottom: 0; left: 0; width: 100%; background-color: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1001; }
            .mobile-nav { display: flex; justify-content: space-around; list-style: none; margin: 0; padding: 0.5rem 0; }
            .mobile-nav a { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: #6c757d; font-size: 0.75rem; }
            body { padding-bottom: 70px; }
            .footer { display: none; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">Mantralayam Rooms Booking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar">
                <div class="offcanvas-header"><h5 class="offcanvas-title">Menu</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header text-center">
        <h1 class="display-5 fw-bold">My Bookings</h1>
    </section>

    <!-- Bookings List -->
    <section class="container py-5">
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <a href="invoice.php?booking_id=<?php echo $booking['booking_id']; ?>" class="card mb-4 text-decoration-none text-dark border-0 shadow-sm booking-card">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" class="img-fluid rounded-start" alt="Room Image" style="height: 100%; object-fit: cover;">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($booking['room_name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($booking['hotel_name']); ?></h6>
                                <hr>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <strong>Check-in:</strong><br><?php echo date('d M, Y', strtotime($booking['checkin_date'])); ?>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <strong>Check-out:</strong><br><?php echo date('d M, Y', strtotime($booking['checkout_date'])); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Total Paid:</strong><br>₹<?php echo number_format($booking['total_price'], 2); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Booked On:</strong><br><?php echo date('d M, Y', strtotime($booking['booked_at'])); ?>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <span class="text-primary fw-bold">View Invoice &rarr;</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-x" style="font-size: 4rem; color: #6c757d;"></i>
                <h2 class="mt-3">You have no bookings yet.</h2>
                <p class="lead text-muted">Start by finding the perfect room for your stay.</p>
                <a href="booking.php" class="btn btn-primary mt-3">Browse Rooms</a>
            </div>
        <?php endif; ?>
    </section>
    
    <!-- Desktop Footer -->
    <footer class="footer py-4">
        <!-- Footer content from index.php -->
    </footer>

    <!-- Mobile Footer Navigation -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'guest'): ?>
    <footer class="mobile-footer">
        <ul class="mobile-nav">
            <li><a href="index.php"><i class="bi bi-house-door-fill fs-4"></i><span>Home</span></a></li>
            <li><a href="booking.php"><i class="bi bi-search fs-4"></i><span>Book Now</span></a></li>
            <li><a href="my_bookings.php"><i class="bi bi-journal-text fs-4"></i><span>My Bookings</span></a></li>
            <li><a href="profile.php"><i class="bi bi-person-circle fs-4"></i><span>Profile</span></a></li>
        </ul>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
