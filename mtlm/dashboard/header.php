<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar { width: 250px; background-color: #212529; position: fixed; height: 100%; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .mobile-footer { display: none; }
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .mobile-footer { display: block; position: fixed; bottom: 0; left: 0; width: 100%; background-color: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1001; }
            .mobile-nav { display: flex; justify-content: space-around; list-style: none; margin: 0; padding: 0.5rem 0; }
            .mobile-nav a { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: #6c757d; font-size: 0.75rem; }
            .mobile-nav a .icon-wrapper { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; }
            .mobile-nav a.active { color: #0d6efd; }
            .mobile-nav a.active .icon-wrapper { background-color: #e7f3ff; }
            body { padding-bottom: 70px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar text-white d-none d-lg-flex flex-column">
            <h2 class="text-center py-3">Dashboard</h2>
            <ul class="nav flex-column flex-grow-1">
                <li class="nav-item"><a class="nav-link text-white-50 <?php if($page == 'rooms') echo 'active'; ?>" href="rooms.php">Manage Rooms</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 <?php if($page == 'bookings') echo 'active'; ?>" href="bookings.php">Booking History</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 <?php if($page == 'earnings') echo 'active'; ?>" href="earnings.php">Earnings</a></li>
                <li class="nav-item"><a class="nav-link text-white-50 <?php if($page == 'profile') echo 'active'; ?>" href="profile.php">Profile</a></li>
            </ul>
            <div class="p-3 border-top border-secondary">
                <a class="nav-link text-white-50" href="../index.php" target="_blank">View Website</a>
                <a class="nav-link text-white-50" href="../logout.php">Logout</a>
            </div>
        </aside>
        <main class="main-content">
