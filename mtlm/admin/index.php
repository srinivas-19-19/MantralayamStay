<?php
require '../db_connect.php';

// --- Admin Security Check ---
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- Handle owner management actions (Approve, Suspend, Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $owner_id = $_GET['id'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE owners SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $owner_id);
    } elseif ($action == 'suspend') {
        $stmt = $conn->prepare("UPDATE owners SET status = 'suspended' WHERE id = ?");
        $stmt->bind_param("i", $owner_id);
    } elseif ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM owners WHERE id = ?");
        $stmt->bind_param("i", $owner_id);
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
        header("Location: index.php#manage-owners"); 
        exit();
    }
}


// --- 1. Fetch Owners' Personal & Payment Details ---
$owner_details = [];
$sql_owner_details = "SELECT o.*, h.hotel_name 
                      FROM owners o 
                      LEFT JOIN hotels h ON o.id = h.owner_id 
                      ORDER BY o.created_at DESC";
$result_owner_details = $conn->query($sql_owner_details);
if ($result_owner_details) {
    while ($row = $result_owner_details->fetch_assoc()) {
        $owner_details[] = $row;
    }
}

// --- 2. Fetch Owners List with NEW Earnings Breakdown ---
$owners_list = [];
$sql_owners_list = "SELECT 
                        o.id, o.full_name, o.email, 
                        SUM(b.total_price) as total_amount_paid
                    FROM owners o
                    LEFT JOIN hotels h ON o.id = h.owner_id
                    LEFT JOIN rooms r ON h.id = r.hotel_id
                    LEFT JOIN bookings b ON r.id = b.room_id
                    GROUP BY o.id
                    ORDER BY o.full_name ASC";
$result_owners_list = $conn->query($sql_owners_list);
if ($result_owners_list) {
    while ($row = $result_owners_list->fetch_assoc()) {
        $total_paid = $row['total_amount_paid'] ?? 0;
        $razorpay_fee = $total_paid * 0.0236; // Approx 2.36%
        $after_razorpay = $total_paid - $razorpay_fee;
        $owner_payout = $after_razorpay * 0.90;
        
        $row['amount_after_razorpay'] = $after_razorpay;
        $row['owner_payout'] = $owner_payout;
        
        $owners_list[] = $row;
    }
}

// --- 3. Fetch and Calculate Admin Earnings (Commission) ---
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

$total_booking_value = 0;
$sql_admin_earnings = "SELECT SUM(total_price) as total_value FROM bookings b WHERE 1=1 {$period_filter}";
$stmt_admin_earnings = $conn->prepare($sql_admin_earnings);
if (isset($start_date)) {
    $stmt_admin_earnings->bind_param("ss", $start_date, $end_date);
}
$stmt_admin_earnings->execute();
$result_admin_earnings = $stmt_admin_earnings->get_result();
if ($row = $result_admin_earnings->fetch_assoc()) {
    $total_booking_value = $row['total_value'] ?? 0;
}
$admin_razorpay_fee = $total_booking_value * 0.0236;
$admin_amount_after_razorpay = $total_booking_value - $admin_razorpay_fee;
$admin_net_profit = $admin_amount_after_razorpay * 0.10; // 10% of the amount after fees
$stmt_admin_earnings->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar { width: 250px; background-color: #212529; position: fixed; height: 100%; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar text-white d-none d-lg-flex flex-column">
            <h2 class="text-center py-3">Admin Panel</h2>
            <ul class="nav flex-column flex-grow-1">
                <li class="nav-item"><a class="nav-link text-white-50 menu-item active" href="#manage-owners">Manage Owners</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 menu-item" href="#owner-details">Payment Details</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 menu-item" href="#owner-list">Owner Earnings</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 menu-item" href="#admin-earnings">Admin Earnings</a></li>
            </ul>
            <div class="p-3 border-top border-secondary"><a class="nav-link text-white-50" href="../logout.php">Logout</a></div>
        </aside>

        <main class="main-content">
            <!-- Manage Owners Section -->
            <section id="manage-owners" class="content-section active">
                <h1 class="h2 mb-4 pb-2 border-bottom">Manage Owner Registrations</h1>
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark"><tr><th>Full Name</th><th>Hotel Name</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach($owner_details as $owner): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($owner['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($owner['hotel_name']); ?></td>
                                        <td><span class="badge bg-<?php if($owner['status'] == 'approved') echo 'success'; elseif($owner['status'] == 'pending') echo 'warning'; else echo 'secondary'; ?>"><?php echo ucfirst($owner['status']); ?></span></td>
                                        <td class="text-end">
                                            <?php if ($owner['status'] == 'pending' || $owner['status'] == 'suspended'): ?>
                                                <a href="index.php?action=approve&id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                            <?php endif; ?>
                                            <?php if ($owner['status'] == 'approved'): ?>
                                                <a href="index.php?action=suspend&id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-warning">Suspend</a>
                                            <?php endif; ?>
                                            <a href="index.php?action=delete&id=<?php echo $owner['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($owner_details)): ?><tr><td colspan="4" class="text-center">No owners have registered yet.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Owners' Payment Details Section -->
            <section id="owner-details" class="content-section">
                <h1 class="h2 mb-4 pb-2 border-bottom">Owners' Payment Details</h1>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>Full Name</th><th>Lodge Name</th><th>Mobile</th><th>Bank Name</th><th>Account No.</th><th>IFSC</th><th>UPI ID</th></tr></thead>
                        <tbody>
                            <?php foreach($owner_details as $owner): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($owner['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($owner['hotel_name']); ?></td>
                                <td><?php echo htmlspecialchars($owner['mobile_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($owner['bank_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($owner['account_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($owner['ifsc_code'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($owner['upi_id'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($owner_details)): ?><tr><td colspan="7" class="text-center">No owners found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Owners List & Earnings Section -->
            <section id="owner-list" class="content-section">
                <h1 class="h2 mb-4 pb-2 border-bottom">Owners List & Payouts</h1>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Owner Name</th>
                                <th>Total Amount Paid by Customer</th>
                                <th>Amount After Razorpay Fee</th>
                                <th class="text-success">Owner Payout (90%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($owners_list as $owner): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($owner['full_name']); ?></td>
                                <td>₹<?php echo number_format($owner['total_amount_paid'] ?? 0, 2); ?></td>
                                <td>₹<?php echo number_format($owner['amount_after_razorpay'] ?? 0, 2); ?></td>
                                <td class="fw-bold text-success">₹<?php echo number_format($owner['owner_payout'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($owners_list)): ?><tr><td colspan="4" class="text-center">No owners found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Admin Earnings Section -->
            <section id="admin-earnings" class="content-section">
                <h1 class="h2 mb-4 pb-2 border-bottom">Admin Earnings Report</h1>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex flex-wrap align-items-end gap-3">
                            <div>
                                <a href="index.php?period=today#admin-earnings" class="btn btn-outline-secondary">Today</a>
                                <a href="index.php?period=weekly#admin-earnings" class="btn btn-outline-secondary">This Week</a>
                                <a href="index.php?period=monthly#admin-earnings" class="btn btn-outline-secondary">This Month</a>
                            </div>
                            <div class="ms-auto d-flex gap-2">
                                <div><label class="form-label">From</label><input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? ''; ?>"></div>
                                <div><label class="form-label">To</label><input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? ''; ?>"></div>
                                <button type="submit" class="btn btn-primary" onclick="this.form.action='index.php#admin-earnings'">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                <h3 class="h4 mb-3"><?php echo $period_label; ?> Summary</h3>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3"><div class="card text-center shadow-sm h-100"><div class="card-body"><h5 class="card-title text-muted">Total Amount Paid</h5><p class="card-text h2 fw-bold">₹<?php echo number_format($total_booking_value, 2); ?></p></div></div></div>
                    <div class="col-md-6 col-lg-3"><div class="card text-center shadow-sm h-100"><div class="card-body"><h5 class="card-title text-muted">Razorpay Fee (Est.)</h5><p class="card-text h2 fw-bold text-danger">- ₹<?php echo number_format($admin_razorpay_fee, 2); ?></p></div></div></div>
                    <div class="col-md-6 col-lg-3"><div class="card text-center shadow-sm h-100"><div class="card-body"><h5 class="card-title text-muted">Amount to be Split</h5><p class="card-text h2 fw-bold">₹<?php echo number_format($admin_amount_after_razorpay, 2); ?></p></div></div></div>
                    <div class="col-md-6 col-lg-3"><div class="card text-center shadow-sm border-success border-2 h-100"><div class="card-body"><h5 class="card-title text-muted">Your Profit (10%)</h5><p class="card-text h2 fw-bold text-success">₹<?php echo number_format($admin_net_profit, 2); ?></p></div></div></div>
                </div>
            </section>
        </main>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item');
            const sections = document.querySelectorAll('.content-section');
            
            function showSection(hash) {
                let targetHash = hash || '#manage-owners';
                
                sections.forEach(section => section.classList.toggle('active', '#' + section.id === targetHash));
                menuItems.forEach(item => item.classList.toggle('active', item.getAttribute('href') === targetHash));
            }

            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.getAttribute('href').startsWith('#')) {
                        e.preventDefault();
                        window.location.hash = this.getAttribute('href');
                    }
                });
            });

            window.addEventListener('hashchange', () => showSection(window.location.hash));
            showSection(window.location.hash);
        });
    </script>
</body>
</html>
