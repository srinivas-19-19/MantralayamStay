<?php
require 'db_connect.php';

// Get the number of guests from the URL, default to 1 if not set
$searched_guests = isset($_GET['guests']) && is_numeric($_GET['guests']) ? (int)$_GET['guests'] : 1;

// UPDATED: The SQL query now filters rooms by max_guests
$sql = "SELECT r.*, h.hotel_name FROM rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        JOIN owners o ON h.owner_id = o.id 
        WHERE o.status = 'approved' AND r.status = 'available' AND r.max_guests >= ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $searched_guests);
$stmt->execute();
$result = $stmt->get_result();

$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms - Mantralayam Rooms Booking</title>
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
        .room-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .room-card:hover {
            transform: translateY(-5px);
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
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
                            <li class="nav-item"><a class="nav-link" href="dashboard/rooms.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'guest'): ?>
                            <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="login_portal.php">Login / Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header text-center">
        <h1 class="display-5 fw-bold">Find Your Perfect Room</h1>
    </section>

    <!-- Rooms Grid -->
    <section class="container py-5">
        <div class="row g-4">
            <?php if (!empty($rooms)): ?>
                <?php foreach($rooms as $room): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm room-card">
                        <img src="<?php echo htmlspecialchars($room['image']); ?>" class="card-img-top" alt="Room Image" style="height: 220px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($room['hotel_name']); ?></h6>
                            <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <p class="card-text text-secondary"><i class="bi bi-people-fill"></i> Up to <?php echo htmlspecialchars($room['max_guests']); ?> Guests</p>
                            <div class="mt-auto">
                                <p class="h5 fw-bold text-success mb-3">₹<?php echo number_format($room['price'], 2); ?> <span class="h6 fw-normal text-muted">/ night</span></p>
                                <a href="booking_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary w-100">View Details & Book</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No rooms were found that can accommodate <?php echo $searched_guests; ?> guest(s). Please try searching again with a different number of guests.
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
