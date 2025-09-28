<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login_user.php?error=login_required");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: booking.php");
    exit();
}

$room_id = $_GET['id'];
// Fetch room details along with the hotel name
$stmt = $conn->prepare("SELECT r.*, h.hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    header("Location: booking.php");
    exit();
}

// Explode the comma-separated facilities string into an array
$room_facilities = !empty($room['facilities']) ? explode(',', $room['facilities']) : [];

// --- UPDATED: Map facility names to new Bootstrap Icons ---
$facility_icons = [
    'Wi-Fi' => 'bi-wifi',
    'AC' => 'bi-wind', // A minimalist icon for air conditioning
    'TV' => 'bi-tv', // Line-art version
    'Parking' => 'bi-p-circle', // Line-art version
    'Room Service' => 'bi-bell',
    'Pool' => 'bi-droplet', // A simpler icon for water/pool
    'Luggage Assistance' => 'bi-check-lg' // Icon from your example
];


$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($room['name']); ?> - Mantralayam Rooms Booking</title>
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
        .booking-form-card {
            position: sticky;
            top: 100px;
        }
        /* --- UPDATED: Advanced Facilities Styling --- */
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1.5rem;
        }
        .facility-item {
            text-align: center;
        }
        .facility-item i {
            font-size: 1.8rem;
            color: #6c757d; /* Muted gray color */
            margin-bottom: 0.5rem;
        }
        .facility-item span {
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
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
                        <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row g-4">
            <!-- Left Column: Room Details -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <img src="<?php echo htmlspecialchars($room['image']); ?>" class="card-img-top" alt="Room Image">
                    <div class="card-body p-4">
                        <h5 class="card-subtitle text-primary fw-bold"><?php echo htmlspecialchars($room['hotel_name']); ?></h5>
                        <h1 class="card-title display-6 fw-bold"><?php echo htmlspecialchars($room['name']); ?></h1>
                        <p class="text-muted"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($room['location']); ?></p>
                        <hr>
                        <h5 class="fw-bold">About this room</h5>
                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                        
                        <?php if (!empty($room_facilities)): ?>
                        <hr>
                        <h5 class="fw-bold mb-3">Facilities</h5>
                        <div class="facilities-grid">
                            <?php foreach ($room_facilities as $facility): ?>
                                <?php 
                                    $trimmed_facility = trim($facility);
                                    $icon_class = $facility_icons[$trimmed_facility] ?? 'bi-check-circle'; // Get icon, with fallback
                                ?>
                                <div class="facility-item">
                                    <i class="bi <?php echo $icon_class; ?>"></i>
                                    <span><?php echo htmlspecialchars($trimmed_facility); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Booking Form -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm booking-form-card">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3">Confirm Your Dates</h3>
                        <p class="h4 fw-bold text-success mb-4">
                            ₹<?php echo number_format($room['price'], 2); ?>
                            <span class="h6 fw-normal text-muted">/ night</span>
                        </p>
                        <form id="booking-form" action="review_booking.php" method="GET">
                            <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                            <div class="mb-3">
                                <label for="checkin-date" class="form-label">Check-in</label>
                                <input type="date" class="form-control" id="checkin-date" name="checkin" required>
                            </div>
                            <div class="mb-3">
                                <label for="checkout-date" class="form-label">Check-out</label>
                                <input type="date" class="form-control" id="checkout-date" name="checkout" required>
                            </div>
                            <div class="mb-3">
                                <label for="guests" class="form-label">Guests</label>
                                <input type="number" class="form-control" id="guests" name="guests" min="1" value="2" max="<?php echo htmlspecialchars($room['max_guests']); ?>" required>
                                <div class="form-text">Max <?php echo htmlspecialchars($room['max_guests']); ?> guests allowed.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Review and Book</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const today = new Date().toISOString().split('T')[0];
        const checkinInput = document.getElementById('checkin-date');
        const checkoutInput = document.getElementById('checkout-date');
        checkinInput.setAttribute('min', today);
        checkinInput.addEventListener('change', () => {
            if (checkinInput.value) {
                const nextDay = new Date(checkinInput.value);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.setAttribute('min', nextDay.toISOString().split('T')[0]);
                if (checkoutInput.value <= checkinInput.value) {
                    checkoutInput.value = '';
                }
            }
        });
    </script>
</body>
</html>
