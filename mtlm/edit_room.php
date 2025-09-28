<?php
// =====================
// Bootstrapping & Auth
// =====================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'owner') {
    header("Location: /login_owner.php");
    exit();
}

$owner_id = $_SESSION['user_id'];
$hotel_id = $_SESSION['hotel_id'] ?? null;

// Verify owner is approved
$stmt_status = $conn->prepare("SELECT status FROM owners WHERE id = ?");
$stmt_status->bind_param("i", $owner_id);
$stmt_status->execute();
$owner_details = $stmt_status->get_result()->fetch_assoc();
$stmt_status->close();

if (!$owner_details || $owner_details['status'] !== 'approved') {
    session_destroy();
    header("Location: /login_owner.php?error=not_approved");
    exit();
}

// =====================
// Page State
// =====================
$page_title = 'Add a New Room';
$room = null;
$price_overrides = [];
$form_error = '';
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// =====================
// Handle Dynamic Pricing (Add/Delete)
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_price'])) {
    $override_room_id = (int)$_POST['room_id'];

    // Only allow managing prices for the current owner's hotel/room
    $check = $conn->prepare("SELECT id FROM rooms WHERE id = ? AND hotel_id = ?");
    $check->bind_param("ii", $override_room_id, $hotel_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists && isset($_POST['add_override'])) {
        $price_date = $_POST['price_date']; // YYYY-MM-DD
        $override_price = (float)$_POST['override_price'];

        // NOTE: Last type should be 'd', not 's'
        $stmt_add = $conn->prepare("
            INSERT INTO room_price_overrides (room_id, price_date, price)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE price = ?
        ");
        $stmt_add->bind_param("isdd", $override_room_id, $price_date, $override_price, $override_price);
        $stmt_add->execute();
        $stmt_add->close();
    }

    header("Location: edit_room.php?id=" . $override_room_id);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_override' && isset($_GET['override_id'])) {
    $override_id = (int)$_GET['override_id'];

    // Optional: ensure the override belongs to a room in this hotel
    $stmt_check_ov = $conn->prepare("
        SELECT rpo.id
        FROM room_price_overrides rpo
        JOIN rooms r ON r.id = rpo.room_id
        WHERE rpo.id = ? AND r.hotel_id = ?
    ");
    $stmt_check_ov->bind_param("ii", $override_id, $hotel_id);
    $stmt_check_ov->execute();
    $ok = $stmt_check_ov->get_result()->fetch_assoc();
    $stmt_check_ov->close();

    if ($ok) {
        $stmt_delete = $conn->prepare("DELETE FROM room_price_overrides WHERE id = ?");
        $stmt_delete->bind_param("i", $override_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }

    header("Location: edit_room.php?id=" . $room_id);
    exit();
}

// =====================
// Fetch Room Data (if editing)
// =====================
if ($room_id) {
    $page_title = 'Edit Room Details';
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND hotel_id = ?");
    $stmt->bind_param("ii", $room_id, $hotel_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$room) {
        header("Location: /dashboard/rooms.php");
        exit();
    }

    $stmt_overrides = $conn->prepare("SELECT * FROM room_price_overrides WHERE room_id = ? ORDER BY price_date ASC");
    $stmt_overrides->bind_param("i", $room_id);
    $stmt_overrides->execute();
    $result_overrides = $stmt_overrides->get_result();
    while ($row = $result_overrides->fetch_assoc()) {
        $price_overrides[] = $row;
    }
    $stmt_overrides->close();
}

// =====================
// Handle Save (Add/Update Room)
// =====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_room_details'])) {
    $room_name = trim($_POST['room-name']);
    $room_description = trim($_POST['room-description']);
    $room_price = (float)$_POST['room-price'];
    $max_guests = (int)$_POST['max_guests'];
    $room_type = trim($_POST['room-type']);
    $facilities_string = isset($_POST['facilities']) ? implode(',', $_POST['facilities']) : '';
    $room_location = trim($_POST['room-location']);
    $submitted_room_id = !empty($_POST['room-id']) ? (int)$_POST['room-id'] : null;

    // image
    $image_path = $_POST['current_image'] ?? '';
    if (isset($_FILES['room-image']) && $_FILES['room-image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['room-image']['type'];

        if (in_array($file_type, $allowed_types)) {
            $image_name = uniqid() . '-' . basename($_FILES['room-image']['name']);
            $target_file = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['room-image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $form_error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $form_error = "Sorry, only JPG, PNG, & GIF files are allowed.";
        }
    }

    if (empty($form_error)) {
        if (empty($submitted_room_id)) {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO rooms
                    (hotel_id, name, description, facilities, price, max_guests, `type`, location, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "isssdisss",
                $hotel_id,
                $room_name,
                $room_description,
                $facilities_string,
                $room_price,
                $max_guests,
                $room_type,
                $room_location,
                $image_path
            );
        } else {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE rooms
                SET name = ?, description = ?, facilities = ?, price = ?, max_guests = ?, `type` = ?, location = ?, image = ?
                WHERE id = ? AND hotel_id = ?
            ");
            // NOTE: fixed types — 10 placeholders => 10 vars
            $stmt->bind_param(
                "sssdisssii",
                $room_name,
                $room_description,
                $facilities_string,
                $room_price,
                $max_guests,
                $room_type,
                $room_location,
                $image_path,
                $submitted_room_id,
                $hotel_id
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: /dashboard/rooms.php?status=success");
            exit();
        } else {
            $form_error = "Database error: " . $stmt->error;
            $stmt->close();
        }
    }
}

$conn->close();

// For UI
$all_facilities = [
    'Wi-Fi'        => 'bi-wifi',
    'AC'           => 'bi-snow',
    'TV'           => 'bi-tv',
    'Parking'      => 'bi-car-front',
    'Room Service' => 'bi-bell',
    'Pool'         => 'bi-water'
];
$current_facilities = isset($room['facilities']) ? explode(',', $room['facilities']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .facility-toggle input[type="checkbox"] { display: none; }
        .facility-toggle label {
            cursor: pointer;
            user-select: none;
            border: 1px solid #e9ecef;
            border-radius: .75rem;
            padding: .65rem .9rem;
            display: flex; align-items: center; gap: .5rem;
            background-color: #fff;
            transition: all .15s ease-in-out;
        }
        .facility-toggle input[type="checkbox"]:checked + label {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 .25rem rgba(13,110,253,.15);
        }
        .img-preview {
            max-width: 220px; border-radius: .5rem; border: 1px solid #eaeaea;
        }
        .card > .card-header h5 { margin: 0; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="/dashboard/rooms.php">Mantralayam Rooms Booking</a>
        <a href="/dashboard/rooms.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</nav>

<main class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 m-0"><?= htmlspecialchars($page_title) ?></h1>
        <div>
            <a href="/dashboard/rooms.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success">Room details saved successfully!</div>
    <?php endif; ?>
    <?php if (!empty($form_error)): ?>
        <div class="alert alert-danger"><?= $form_error ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left: Main Room Details -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3">
                    <h5 class="fw-semibold">Room Information</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="edit_room.php<?= $room_id ? '?id=' . (int)$room_id : '' ?>" enctype="multipart/form-data" id="roomForm">
                        <input type="hidden" name="save_room_details" value="1">
                        <input type="hidden" name="room-id" value="<?= htmlspecialchars($room['id'] ?? '') ?>">
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($room['image'] ?? '') ?>">

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="room-name" class="form-label">Room Name / Title</label>
                                <input type="text" id="room-name" name="room-name" class="form-control"
                                       value="<?= htmlspecialchars($room['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_guests" class="form-label">Max Guests</label>
                                <input type="number" id="max_guests" name="max_guests" class="form-control"
                                       value="<?= htmlspecialchars($room['max_guests'] ?? '2') ?>" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="room-type" class="form-label">Room Type</label>
                            <select id="room-type" name="room-type" class="form-select" required>
                                <?php
                                $types = ['Single','Double','Suite','Deluxe'];
                                $curType = $room['type'] ?? '';
                                foreach ($types as $t) {
                                    $sel = ($curType === $t) ? 'selected' : '';
                                    echo "<option value=\"{$t}\" {$sel}>{$t}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="room-description" class="form-label">Description</label>
                            <textarea id="room-description" name="room-description" class="form-control" rows="4" required><?= htmlspecialchars($room['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="room-price" class="form-label">Default Price per Night (₹)</label>
                            <input type="number" id="room-price" name="room-price" class="form-control"
                                   step="0.01" value="<?= htmlspecialchars($room['price'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="room-location" class="form-label">Location / Address</label>
                            <input type="text" id="room-location" name="room-location" class="form-control"
                                   value="<?= htmlspecialchars($room['location'] ?? '') ?>" required>
                        </div>

                        <!-- Facilities with Bootstrap Icons -->
                        <div class="mb-3">
                            <label class="form-label">Facilities</label>
                            <div class="row g-2">
                                <?php foreach ($all_facilities as $label => $icon): 
                                    $id = 'facility_' . preg_replace('/\W+/', '_', strtolower($label));
                                    $checked = in_array($label, $current_facilities) ? 'checked' : '';
                                ?>
                                    <div class="col-6 col-md-4">
                                        <div class="facility-toggle">
                                            <input type="checkbox" id="<?= $id ?>" name="facilities[]" value="<?= $label ?>" <?= $checked ?>>
                                            <label for="<?= $id ?>">
                                                <i class="bi <?= $icon ?>"></i>
                                                <span><?= $label ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Image upload + preview -->
                        <div class="mb-3">
                            <label for="room-image" class="form-label">Room Image</label>
                            <input type="file" id="room-image" name="room-image" class="form-control" accept="image/png, image/jpeg, image/gif">
                            <?php if (!empty($room['image'])): ?>
                                <img src="<?= htmlspecialchars($room['image']) ?>" alt="Current Image" class="img-preview mt-2">
                            <?php endif; ?>
                            <img id="livePreview" class="img-preview mt-2 d-none" alt="Preview">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Save Room Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Dynamic Pricing -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent py-3">
                    <h5 class="fw-semibold">Dynamic Pricing</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($room): ?>
                        <p class="text-muted small">Set special prices for specific dates. If no price is set for a date, the default price will be used.</p>

                        <form method="POST" action="edit_room.php?id=<?= (int)$room_id ?>" class="mb-3">
                            <input type="hidden" name="manage_price" value="1">
                            <input type="hidden" name="room_id" value="<?= (int)$room_id ?>">
                            <div class="row g-2 align-items-end">
                                <div class="col-5">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="price_date" class="form-control" required>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Price (₹)</label>
                                    <input type="number" name="override_price" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-2">
                                    <button type="submit" name="add_override" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <h6 class="mb-2">Existing Price Overrides</h6>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($price_overrides as $override): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="me-3">
                                            <?= date('d M, Y', strtotime($override['price_date'])) ?>
                                        </span>
                                        <span class="badge text-bg-primary">₹<?= number_format((float)$override['price'], 2) ?></span>
                                    </div>
                                    <a href="edit_room.php?id=<?= (int)$room_id ?>&action=delete_override&override_id=<?= (int)$override['id'] ?>"
                                       class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($price_overrides)): ?>
                                <li class="list-group-item px-0 text-muted">No overrides set.</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-warning">Save the room first to enable dynamic pricing.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS + Image live preview -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('room-image')?.addEventListener('change', function (e) {
    const file = e.target.files && e.target.files[0];
    const preview = document.getElementById('livePreview');
    if (!file) { preview?.classList.add('d-none'); return; }
    const reader = new FileReader();
    reader.onload = function (ev) {
        preview.src = ev.target.result;
        preview.classList.remove('d-none');
    }
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
