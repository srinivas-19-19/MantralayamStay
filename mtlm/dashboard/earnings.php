<?php
require '../db_connect.php';

// --- Security Check & Fetch Owner Data ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') { header("Location: ../login_owner.php"); exit(); }
$owner_id = $_SESSION['user_id'];
$hotel_id = $_SESSION['hotel_id'];
// Add a full status check for extra security on every page
$stmt_status_check = $conn->prepare("SELECT status FROM owners WHERE id = ?");
$stmt_status_check->bind_param("i", $owner_id);
$stmt_status_check->execute();
$owner_status_result = $stmt_status_check->get_result()->fetch_assoc();
if (!$owner_status_result || $owner_status_result['status'] !== 'approved') {
    session_destroy();
    header("Location: ../login_owner.php?error=not_approved");
    exit();
}
$stmt_status_check->close();


// --- Fetch and Calculate Earnings ---
$period_filter = " AND DATE(b.booked_at) = CURDATE()"; // Default to Today
$period_label = "Today's";
if (isset($_GET['period'])) {
    if ($_GET['period'] == 'weekly') {
        $period_filter = " AND WEEK(b.booked_at, 1) = WEEK(CURDATE(), 1) AND YEAR(b.booked_at) = YEAR(CURDATE())";
        $period_label = "This Week's";
    } elseif ($_GET['period'] == 'monthly') {
        $period_filter = " AND MONTH(b.booked_at) = MONTH(CURDATE()) AND YEAR(b.booked_at) = YEAR(CURDATE())";
        $period_label = "This Month's";
    }
}
if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $period_filter = " AND DATE(b.booked_at) BETWEEN ? AND ?";
    $period_label = "Earnings from " . date("M d, Y", strtotime($start_date)) . " to " . date("M d, Y", strtotime($end_date));
}

$total_gross_revenue = 0;
$sql_earnings = "SELECT SUM(b.total_price) as total_gross 
                 FROM bookings b 
                 JOIN rooms r ON b.room_id = r.id 
                 WHERE r.hotel_id = ? {$period_filter}";
$stmt_earnings = $conn->prepare($sql_earnings);
if (isset($start_date)) {
    $stmt_earnings->bind_param("iss", $hotel_id, $start_date, $end_date);
} else {
    $stmt_earnings->bind_param("i", $hotel_id);
}
$stmt_earnings->execute();
$result_earnings = $stmt_earnings->get_result();
if($row = $result_earnings->fetch_assoc()) {
    $total_gross_revenue = $row['total_gross'] ?? 0;
}

// --- NEW Detailed Earning Calculation ---
$razorpay_fee = $total_gross_revenue * 0.0236; // Approximate Razorpay fee (2% + 18% GST)
$amount_after_razorpay = $total_gross_revenue - $razorpay_fee;
$owner_net_payout = $amount_after_razorpay * 0.90; // 90% of the amount after fees

$stmt_earnings->close();
$conn->close();

$page = 'earnings'; // Set the active page for the header
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <h1 class="h2">Earnings Report</h1>
</div>

<!-- Earnings Filters Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="earnings.php" class="d-flex flex-wrap align-items-end gap-3">
            <div>
                <a href="earnings.php?period=today" class="btn btn-outline-secondary">Today</a>
                <a href="earnings.php?period=weekly" class="btn btn-outline-secondary">This Week</a>
                <a href="earnings.php?period=monthly" class="btn btn-outline-secondary">This Month</a>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
                <div>
                    <label for="start_date" class="form-label">From</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                </div>
                <div>
                    <label for="end_date" class="form-label">To</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary align-self-end">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Earnings Summary Cards -->
<h3 class="h4 mb-3"><?php echo $period_label; ?> Summary</h3>
<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Amount Paid</h5>
                <p class="card-text h2 fw-bold">₹<?php echo number_format($total_gross_revenue, 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Razorpay Fee (Est.)</h5>
                <p class="card-text h2 fw-bold text-danger">- ₹<?php echo number_format($razorpay_fee, 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Amount to be Split</h5>
                <p class="card-text h2 fw-bold">₹<?php echo number_format($amount_after_razorpay, 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card text-center shadow-sm border-success border-2 h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Your Payout (90%)</h5>
                <p class="card-text h2 fw-bold text-success">₹<?php echo number_format($owner_net_payout, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
