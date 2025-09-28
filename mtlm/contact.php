<?php 
require 'db_connect.php'; 
require 'mail_config.php';

$message_text = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($_POST['subject']);
    $message_body = htmlspecialchars($_POST['message']);
    
    $email_sent_result = send_contact_email($name, $email, $subject, $message_body);
    
    if ($email_sent_result === true) {
        $message_text = "Thank you for your message! We will get back to you shortly.";
        $message_type = 'success';
    } else {
        $message_text = $email_sent_result;
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Mantralayam Rooms Booking</title>
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
        .contact-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
                        <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                        <!-- Other nav links -->
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header text-center">
        <h1 class="display-5 fw-bold">Get in Touch</h1>
        <p class="lead">We're here to help with any questions you may have.</p>
    </section>

    <main class="container py-5">
        <div class="row g-4">
            <!-- Left Column: Contact Details -->
            <div class="col-lg-5">
                <h2 class="h3 fw-bold mb-4">Meet the Team</h2>
                <!-- Contact Card 1 -->
                <div class="card border-0 shadow-sm mb-4 contact-card">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold">Srinivas G.</h5>
                        <p class="card-text mb-1"><i class="bi bi-envelope-fill text-primary me-2"></i>info@mantralayamroomsbooking.com</p>
                        <p class="card-text"><i class="bi bi-telephone-fill text-primary me-2"></i> +91 8309188159</p>
                    </div>
                </div>
                <!-- Contact Card 2 -->
                <div class="card border-0 shadow-sm contact-card">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold">Manikanta Reddy K.</h5>
                        <p class="card-text mb-1"><i class="bi bi-envelope-fill text-primary me-2"></i>mantralayamroomsbooking@gmail.com</p>
                        <p class="card-text"><i class="bi bi-telephone-fill text-primary me-2"></i>+91 7893007772</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h3 fw-bold mb-4">Send us a Message</h2>
                        <?php if (!empty($message_text)): ?>
                            <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?>">
                                <?php echo $message_text; ?>
                            </div>
                        <?php else: ?>
                            <form action="contact.php" method="POST">
                                <div class="mb-3"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" required></div>
                                <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" name="email" required></div>
                                <div class="mb-3"><label for="subject" class="form-label">Subject</label><input type="text" class="form-control" id="subject" name="subject" required></div>
                                <div class="mb-3"><label for="message" class="form-label">Message</label><textarea class="form-control" id="message" name="message" rows="5" required></textarea></div>
                                <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Send Message</button></div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
