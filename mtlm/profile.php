<?php
require 'db_connect.php';

// Security Check: Ensure a guest is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    header("Location: login_user.php");
    exit();
}

$guest_id = $_SESSION['user_id'];
$update_message = '';
$message_type = 'error';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = htmlspecialchars($_POST['full_name']);
    $phone_number = htmlspecialchars($_POST['phone_number']);

    $stmt_update = $conn->prepare("UPDATE guests SET full_name = ?, phone_number = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $full_name, $phone_number, $guest_id);
    if ($stmt_update->execute()) {
        $update_message = "Profile updated successfully!";
        $message_type = 'success';
    } else {
        $update_message = "Error updating profile. Please try again.";
    }
    $stmt_update->close();
}

// Fetch latest guest details for display
$stmt = $conn->prepare("SELECT full_name, email, phone_number FROM guests WHERE id = ?");
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$result = $stmt->get_result();
$guest = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Mantralayam Rooms Booking</title>
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
            .mobile-nav a.active { color: #0d6efd; }
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
                        <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header text-center">
        <h1 class="display-5 fw-bold">My Profile</h1>
    </section>

    <!-- Profile Form -->
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <?php if ($update_message): ?>
                            <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?>">
                                <?php echo $update_message; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="mb-3">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" name="full_name" value="<?php echo htmlspecialchars($guest['full_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($guest['email']); ?>" readonly disabled>
                                <div class="form-text">Your email address cannot be changed.</div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone_number" value="<?php echo htmlspecialchars($guest['phone_number'] ?? ''); ?>">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                        <hr>
                        <div class="text-center">
                            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Desktop Footer -->
    <footer class="footer py-4">
        <!-- Footer content from index.php -->
    </footer>

    <!-- Mobile Footer Navigation -->
    <footer class="mobile-footer">
        <ul class="mobile-nav">
            <li><a href="index.php"><i class="bi bi-house-door fs-4"></i><span>Home</span></a></li>
            <li><a href="booking.php"><i class="bi bi-search fs-4"></i><span>Book Now</span></a></li>
            <li><a href="my_bookings.php"><i class="bi bi-journal-text fs-4"></i><span>My Bookings</span></a></li>
            <li><a href="profile.php" class="active"><i class="bi bi-person-circle fs-4"></i><span>Profile</span></a></li>
        </ul>
    </footer>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
