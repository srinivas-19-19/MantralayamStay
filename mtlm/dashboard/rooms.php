<?php
require '../db_connect.php';

// --- Security Check & Fetch Owner Data ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'owner') { header("Location: ../login_owner.php"); exit(); }
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


// --- Room Management Logic (Toggle Status & Delete) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $room_id_action = $_GET['id'];
    if ($_GET['action'] == 'toggle_status') {
        // First, get the current status to flip it
        $stmt_get = $conn->prepare("SELECT status FROM rooms WHERE id = ? AND hotel_id = ?");
        $stmt_get->bind_param("ii", $room_id_action, $hotel_id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        if ($room = $result_get->fetch_assoc()) {
            $new_status = ($room['status'] == 'available') ? 'hidden' : 'available';
            // Now, update the room with the new status
            $stmt_update = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_status, $room_id_action);
            $stmt_update->execute();
            $stmt_update->close();
        }
        $stmt_get->close();
    } elseif ($_GET['action'] == 'delete') {
        $stmt_delete = $conn->prepare("DELETE FROM rooms WHERE id = ? AND hotel_id = ?");
        $stmt_delete->bind_param("ii", $room_id_action, $hotel_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    // Redirect back to this page to show the changes
    header("Location: rooms.php");
    exit();
}


// --- Fetch all rooms for display ---
$rooms = [];
$stmt_rooms = $conn->prepare("SELECT * FROM rooms WHERE hotel_id = ? ORDER BY created_at DESC");
$stmt_rooms->bind_param("i", $hotel_id);
$stmt_rooms->execute();
$result_rooms = $stmt_rooms->get_result();
while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
}
$stmt_rooms->close();
$conn->close();

$page = 'rooms'; // Set the active page for the header
include 'header.php';
?>

<!-- Main Content -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <h1 class="h2">Manage Your Rooms</h1>
    <a href="../edit_room.php" class="btn btn-success">
        <i class="bi bi-plus-circle-fill me-2"></i>Add New Room
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Room Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['name']); ?></td>
                            <td>₹<?php echo number_format($room['price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $room['status'] == 'available' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="rooms.php?action=toggle_status&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-<?php echo $room['status'] == 'available' ? 'warning' : 'info'; ?>">
                                    <?php echo $room['status'] == 'available' ? 'Hide' : 'Show'; ?>
                                </a>
                                <a href="../edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="rooms.php?action=delete&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center p-4">You haven't added any rooms yet. Click "Add New Room" to get started!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
