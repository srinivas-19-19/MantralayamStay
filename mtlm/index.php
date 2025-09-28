<?php
require 'db_connect.php'; 
$featured_rooms = [];
$sql = "SELECT r.*, h.hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id JOIN owners o ON h.owner_id = o.id WHERE o.status = 'approved' AND r.status = 'available' ORDER BY r.created_at DESC ";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_rooms[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantralayam Rooms Booking - Professional Hotel Booking</title>
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
        .hero {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 8rem 0;
            color: white;
        }
        .search-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            /* FIX: Changed margin-top to a positive value */
            margin-top: 2rem; 
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
            .hero { padding: 6rem 0; }
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

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Your Sacred Stay Awaits</h1>
            <p class="lead">Find and book the perfect room for your pilgrimage to Mantralayam.</p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="container">
        <div class="search-card">
            <form action="booking.php" method="GET" class="row g-3 align-items-end">
                <div class="col-lg"><label for="checkin" class="form-label fw-bold">Check-in</label><input type="date" class="form-control form-control-lg" id="checkin" name="checkin"></div>
                <div class="col-lg"><label for="checkout" class="form-label fw-bold">Check-out</label><input type="date" class="form-control form-control-lg" id="checkout" name="checkout"></div>
                <div class="col-lg-2"><label for="guests" class="form-label fw-bold">Guests</label><input type="number" class="form-control form-control-lg" id="guests" name="guests" value="2" min="1"></div>
                <div class="col-lg-2 d-grid"><button type="submit" class="btn btn-primary btn-lg">Search</button></div>
            </form>
        </div>
    </section>

    <!-- Featured Properties Section -->
    <section class="container py-5">
        <h2 class="text-center fw-bold mb-5">Featured Properties</h2>
        <div class="row g-4">
            <?php if (!empty($featured_rooms)): ?>
                <?php foreach($featured_rooms as $room): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm room-card">
                        <img src="<?php echo htmlspecialchars($room['image']); ?>" class="card-img-top" alt="Room Image" style="height: 220px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($room['hotel_name']); ?></h6>
                            <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <p class="card-text text-secondary"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($room['location']); ?></p>
                            <div class="mt-auto"><a href="booking_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary w-100">View Details</a></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">No featured properties are available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Desktop Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="d-flex justify-content-center mb-3">
                <a href="about.php" class="mx-2">About Us</a> |
                <a href="contact.php" class="mx-2">Contact Us</a> |
                <a href="terms_and_conditions.php" class="mx-2">Terms & Conditions</a> |
                <a href="privacy_policy.php" class="mx-2">Privacy Policy</a> |
                <a href="cancellation_policy.php" class="mx-2">Cancellation Policy</a>
            </div>
            <p class="text-center mb-0">&copy; <?php echo date("Y"); ?> Mantralayam Rooms Booking. All rights reserved.</p>
        </div>
    </footer>

    <!-- Mobile Footer Navigation -->
    <footer class="mobile-footer">
        <ul class="mobile-nav">
            <li><a href="index.php"><i class="bi bi-house-door-fill fs-4"></i><span>Home</span></a></li>
            <li><a href="booking.php"><i class="bi bi-search fs-4"></i><span>Book Now</span></a></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'guest'): ?>
                <li><a href="my_bookings.php"><i class="bi bi-journal-text fs-4"></i><span>My Bookings</span></a></li>
                <li><a href="profile.php"><i class="bi bi-person-circle fs-4"></i><span>Profile</span></a></li>
            <?php else: ?>
                <li><a href="login_portal.php"><i class="bi bi-person-circle fs-4"></i><span>Profile</span></a></li>
            <?php endif; ?>
        </ul>
    </footer>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
