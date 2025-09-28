<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login_user.php?error=login_required");
    exit();
}

// UPDATED: Receive data from $_POST
if (!isset($_POST['id']) || !isset($_POST['checkin']) || !isset($_POST['checkout']) || !isset($_POST['guests']) || !isset($_POST['phone'])) {
    header("Location: booking.php");
    exit();
}

$room_id = $_POST['id'];
$checkin_date_str = $_POST['checkin'];
$checkout_date_str = $_POST['checkout'];
$guests = $_POST['guests'];
$guest_phone = $_POST['phone'];

// Fetch room details to get the price
$stmt = $conn->prepare("SELECT name, price FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    header("Location: booking.php");
    exit();
}
$stmt->close();

// Fetch logged-in guest's details for pre-filling the payment form
$guest_id = $_SESSION['user_id'];
$stmt_guest = $conn->prepare("SELECT full_name, email FROM guests WHERE id = ?");
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
$platform_fee = $base_price * 0.02;
$gst_on_fee = $platform_fee * 0.18;
$total_price = $base_price + $platform_fee + $gst_on_fee;

// --- FIX: Robust Price Calculation in Paise ---
$base_price_paise = $base_price * 100;
$platform_fee_paise = $base_price_paise * 0.02;
$gst_on_fee_paise = $platform_fee_paise * 0.18;
$total_price_paise = round($base_price_paise) + round($platform_fee_paise) + round($gst_on_fee_paise);
$total_price_for_display = $total_price_paise / 100;

// --- Razorpay Configuration ---
$razorpay_key_id = 'rzp_test_KuzHcApwGC87hl'; // Your Live Key ID

$razorpay_options = [
    "key"               => $razorpay_key_id,
    "amount"            => (int)$total_price_paise,
    "currency"          => "INR",
    "name"              => "Mantralayam Rooms Booking",
    "description"       => "Room Booking Transaction",
    "prefill"           => [
        "name"              => $guest['full_name'],
        "email"             => $guest['email'],
        "contact"           => $guest_phone
    ],
    "notes"             => [
        "room_id"           => $room_id,
        "guest_id"          => $guest_id,
        "checkin_date"      => $checkin_date_str,
        "checkout_date"     => $checkout_date_str,
        "number_of_guests"  => $guests,
        "phone"             => $guest_phone
    ],
    "theme"             => [
        "color"             => "#0d6efd"
    ]
];

$razorpay_json_options = json_encode($razorpay_options);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h1 class="h3 mb-0">Final Step: Payment</h1>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between"><span>Room:</span> <strong><?php echo htmlspecialchars($room['name']); ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Check-in:</span> <strong><?php echo htmlspecialchars($checkin_date_str); ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Check-out:</span> <strong><?php echo htmlspecialchars($checkout_date_str); ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between h4 fw-bold mt-3"><span>Total Price:</span> <span class="text-success">₹<?php echo number_format($total_price_for_display, 2); ?></span></li>
                        </ul>
                        <div class="d-grid mt-4">
                            <button id="rzp-button1" class="btn btn-success btn-lg">Pay Securely</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var options = <?php echo $razorpay_json_options; ?>;

        options.handler = function (response){
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'payment_handler.php';

            var paymentIdInput = document.createElement('input');
            paymentIdInput.type = 'hidden';
            paymentIdInput.name = 'razorpay_payment_id';
            paymentIdInput.value = response.razorpay_payment_id;
            form.appendChild(paymentIdInput);

            var notes = options.notes;
            for (var key in notes) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = notes[key];
                form.appendChild(input);
            }
            
            var totalPriceInput = document.createElement('input');
            totalPriceInput.type = 'hidden';
            totalPriceInput.name = 'total_price';
            totalPriceInput.value = options.amount / 100;
            form.appendChild(totalPriceInput);

            document.body.appendChild(form);
            form.submit();
        };
        
        var rzp = new Razorpay(options);

        document.getElementById('rzp-button1').onclick = function(e){
            rzp.open();
            e.preventDefault();
        }
    </script>
</body>
</html>
