<?php
require '../db_connect.php';

// --- Security Check & Fetch Data ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'owner') { header("Location: ../login_owner.php"); exit(); }
$hotel_id = $_SESSION['hotel_id'];

$booking_history = [];
$sql_bookings = "SELECT r.name as room_name, g.full_name as guest_name, b.checkin_date, b.checkout_date, b.total_price, b.booked_at 
                 FROM bookings b
                 JOIN rooms r ON b.room_id = r.id
                 JOIN guests g ON b.guest_id = g.id
                 WHERE r.hotel_id = ? 
                 ORDER BY b.booked_at DESC";
$stmt_bookings = $conn->prepare($sql_bookings);
$stmt_bookings->bind_param("i", $hotel_id);
$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();
while ($row = $result_bookings->fetch_assoc()) {
    $booking_history[] = $row;
}
$stmt_bookings->close();
$conn->close();

$page = 'bookings';
include 'header.php';
?>

<h1 class="h2 mb-4 pb-2 border-bottom">Booking History</h1>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Price</th><th>Booked On</th></tr></thead>
        <tbody>
            <?php foreach($booking_history as $booking): ?>
            <tr>
                <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                <td><?php echo date('d M, Y', strtotime($booking['checkin_date'])); ?></td>
                <td><?php echo date('d M, Y', strtotime($booking['checkout_date'])); ?></td>
                <td>₹<?php echo number_format($booking['total_price'], 2); ?></td>
                <td><?php echo date('d M, Y', strtotime($booking['booked_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($booking_history)): ?><tr><td colspan="6" class="text-center">No bookings found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
