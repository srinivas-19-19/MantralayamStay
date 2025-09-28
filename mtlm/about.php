<?php require 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Mantralayam Rooms Booking</title>
    <link rel="stylesheet" href="style.css">
    <style>

        /* Additional styles specific to the About page */
        .page-header {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 4rem 1rem;
        }
        .page-header h1 {
            font-size: 3rem;
            margin: 0;
        }
        .about-content {
            background: white;
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: -2rem;
            position: relative;
            z-index: 10;
        }
        .about-content p {
            font-size: 1.1rem;
            color: var(--text-muted);
            line-height: 1.8;
        }
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 3rem;
        }
        .mission-vision h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .mission-vision {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">Mantralayam Rooms Booking</a>
            <nav>
                <ul class="nav-links">
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
                        <li><a href="dashboard.php" class="btn btn-primary">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'guest'): ?>
                        <li><a href="booking.php" class="btn btn-primary">My Bookings</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login_user.php">Guest Login</a></li>
                        <li><a href="login_owner.php">Owner Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="page-header">
            <h1>About Our Mission</h1>
        </section>

        <section class="container">
            <div class="about-content">
                <p>
                    Welcome to Mantralayam Rooms Booking, your trusted partner for finding comfortable and convenient accommodations in the holy town of Mantralayam. Our platform was born from a desire to simplify the process of booking a stay for pilgrims and tourists visiting this sacred destination.
                </p>
                <p>
                    We connect travelers with a wide variety of local hotels, guesthouses, and lodges, providing a seamless and secure booking experience. For property owners, we offer a robust platform to showcase their rooms to a larger audience, manage bookings efficiently, and grow their business with the help of our dedicated admin-approval system.
                </p>
                <div class="mission-vision">
                    <div>
                        <h3>Our Mission</h3>
                        <p>
                            To provide a reliable, easy-to-use, and comprehensive booking service that caters to the unique needs of visitors to Mantralayam, ensuring a peaceful and pleasant stay.
                        </p>
                    </div>
                    <div>
                        <h3>Our Vision</h3>
                        <p>
                            To become the leading and most trusted online portal for room bookings in Mantralayam, fostering a strong community of travelers and verified property owners.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
