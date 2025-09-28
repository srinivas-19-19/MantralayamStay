<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login_user.php?error=login_required");
    exit();
}

// Validate that all required booking details are present in the URL
if (!isset($_GET['id']) || !isset($_GET['checkin']) || !isset($_GET['checkout']) || !isset($_GET['guests'])) {
    header("Location: booking.php");
    exit();
}

$room_id = $_GET['id'];
$checkin_date_str = $_GET['checkin'];
$checkout_date_str = $_GET['checkout'];
$guests = $_GET['guests'];

// Fetch room and hotel details
$stmt = $conn->prepare("SELECT r.*, h.hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    header("Location: booking.php");
    exit();
}
$stmt->close();

// Fetch logged-in guest's details for pre-filling the form
$guest_id = $_SESSION['user_id'];
$stmt_guest = $conn->prepare("SELECT full_name, email, phone_number FROM guests WHERE id = ?");
$stmt_guest->bind_param("i", $guest_id);
$stmt_guest->execute();
$result_guest = $stmt_guest->get_result();
$guest = $result_guest->fetch_assoc();
$stmt_guest->close();

// --- Dynamic Price Calculation ---
$checkin_date = new DateTime($checkin_date_str);
$checkout_date = new DateTime($checkout_date_str);
$number_of_nights = ($checkout_date->diff($checkin_date))->days;
if ($number_of_nights <= 0) $number_of_nights = 1;

$price_overrides = [];
$stmt_overrides = $conn->prepare("SELECT price_date, price FROM room_price_overrides WHERE room_id = ?");
$stmt_overrides->bind_param("i", $room_id);
$stmt_overrides->execute();
$result_overrides = $stmt_overrides->get_result();
while ($row = $result_overrides->fetch_assoc()) {
    $price_overrides[$row['price_date']] = $row['price'];
}
$stmt_overrides->close();
$conn->close();

$base_price = 0;
$current_date = clone $checkin_date;
for ($i = 0; $i < $number_of_nights; $i++) {
    $date_string = $current_date->format('Y-m-d');
    if (isset($price_overrides[$date_string])) {
        $base_price += $price_overrides[$date_string];
    } else {
        $base_price += $room['price'];
    }
    $current_date->modify('+1 day');
}

// --- Platform Fee and GST Calculation ---
$platform_fee = $base_price * 0.02; // 2% platform fee
$gst_on_fee = $platform_fee * 0.18; // 18% GST on the platform fee
$total_price = $base_price + $platform_fee + $gst_on_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Your Booking</title>
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
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">Mantralayam Rooms Booking</a>
        </div>
    </nav>

    <main class="container my-4">
        <form action="payments.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $room_id; ?>">
            <input type="hidden" name="checkin" value="<?php echo $checkin_date_str; ?>">
            <input type="hidden" name="checkout" value="<?php echo $checkout_date_str; ?>">
            <input type="hidden" name="guests" value="<?php echo $guests; ?>">

            <div class="row g-4">
                <!-- Left Column: Booking Details -->
                <div class="col-lg-8">
                    <!-- Hotel Details Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 fw-bold"><?php echo htmlspecialchars($room['hotel_name']); ?></h2>
                            <p class="text-muted"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($room['location']); ?></p>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><strong class="text-muted d-block">CHECK-IN</strong><span class="fw-bold"><?php echo date("d M Y, D", $checkin_date->getTimestamp()); ?></span><br><small class="text-muted">from 6:00 PM</small></div>
                                <div class="badge bg-light text-dark rounded-pill"><?php echo $number_of_nights; ?> Night(s)</div>
                                <div><strong class="text-muted d-block">CHECK-OUT</strong><span class="fw-bold"><?php echo date("d M Y, D", $checkout_date->getTimestamp()); ?></span><br><small class="text-muted">before 5:00 PM</small></div>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel Policies Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent py-3"><h5 class="mb-0 fw-bold">Hotel Policies</h5></div>
                        <div class="card-body p-4">
                            <h6 class="fw-bold">Properties Policy</h6>
                            <ul class="list-unstyled text-muted">
                                <li>• Primary guest must be at least 18 years of age.</li>
                                <li>• A 2-cot bed allows a maximum of 2 adults.</li>
                                <li>• Unmarried couples are not allowed.</li>
                                <li>• A valid government-issued ID is required at check-in.</li>
                                <li>• Pets are not allowed on the property.</li>
                            </ul>
                            <h6 class="fw-bold mt-4">Cancellation Policy</h6>
                            <ul class="list-unstyled text-muted">
                                <li>• Cancellations made more than 24 hours before check-in will receive an 80% refund.</li>
                                <li>• Cancellations made within 24 hours of check-in are non-refundable.</li>
                            </ul>
                             <h6 class="fw-bold mt-4">Extra Bed Policy</h6>
                            <ul class="list-unstyled text-muted">
                                <li>• Provision of an extra bed is subject to availability and will incur additional charges.</li>
                            </ul>
                            <h6 class="fw-bold mt-4">Smoking/Alcohol Consumption Rules</h6>
                            <ul class="list-unstyled text-muted">
                                <li>• Smoking within the premises is strictly prohibited.</li>
                                <li>• Consumption of alcohol is not permitted on the property.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Guest Details Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3"><h5 class="mb-0 fw-bold">Enter Guest Details</h5></div>
                        <div class="card-body p-4">
                            <div class="mb-3"><label for="fullName" class="form-label">Full Name</label><input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($guest['full_name']); ?>" required></div>
                            <div class="mb-3"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($guest['email']); ?>" required></div>
                            <div class="mb-3"><label for="phone" class="form-label">Phone Number</label><input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($guest['phone_number'] ?? ''); ?>" required></div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Price & Payment Button -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                        <div class="card-header bg-transparent py-3"><h5 class="mb-0 fw-bold">Price Break-Up</h5></div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between mb-2"><span>Room Price (<?php echo $number_of_nights; ?> Night(s))</span> <span>₹<?php echo number_format($base_price, 2); ?></span></div>
                            <div class="d-flex justify-content-between mb-2"><span>Platform Fee (2%)</span> <span>₹<?php echo number_format($platform_fee, 2); ?></span></div>
                            <div class="d-flex justify-content-between mb-2 text-muted"><small>+ GST on Platform Fee (18%)</small> <small>₹<?php echo number_format($gst_on_fee, 2); ?></small></div>
                            <hr>
                            <div class="d-flex justify-content-between h5 fw-bold"><span>Total Amount</span> <span>₹<?php echo number_format($total_price, 2); ?></span></div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg">Proceed to Payment</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
