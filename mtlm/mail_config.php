<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Use a reliable path to load the library files
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// --- CONSTANTS FOR YOUR EMAIL DETAILS ---
define('ADMIN_EMAIL', 'mantralayamroomsbooking@gmail.com');
define('ADMIN_PASSWORD', 'iptoqyuihtijtcif');

/**
 * Sends an OTP email to a new guest or owner.
 */
function send_otp_email($recipient_email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ADMIN_EMAIL;
        $mail->Password   = ADMIN_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(ADMIN_EMAIL, 'Mantralayam Rooms Booking');
        $mail->addAddress($recipient_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Account Verification';
        $mail->Body    = "Your One-Time Password (OTP) is: <b>{$otp}</b>";
        $mail->AltBody = "Your OTP is: {$otp}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * Sends a contact form submission to the admin.
 */
function send_contact_email($sender_name, $sender_email, $subject, $message_body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ADMIN_EMAIL;
        $mail->Password   = ADMIN_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom(ADMIN_EMAIL, 'Contact Form');
        $mail->addAddress(ADMIN_EMAIL); 
        $mail->addReplyTo($sender_email, $sender_name);

        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission: " . $subject;
        $mail->Body    = "Message from {$sender_name} ({$sender_email}):<br><br>" . nl2br($message_body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * Sends a login OTP email to an admin.
 */
function send_admin_otp_email($recipient_email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ADMIN_EMAIL;
        $mail->Password   = ADMIN_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom(ADMIN_EMAIL, 'Admin Security');
        $mail->addAddress($recipient_email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Admin Login OTP';
        $mail->Body    = "Your One-Time Password (OTP) to log in to the admin panel is: <b>{$otp}</b>. It is valid for 10 minutes.";
        $mail->AltBody = "Your Admin Login OTP is: {$otp}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * Sends a booking confirmation email to a guest.
 */
function send_booking_confirmation_email($recipient_email, $booking_details) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ADMIN_EMAIL;
        $mail->Password   = ADMIN_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(ADMIN_EMAIL, 'Mantralayam Rooms Booking');
        $mail->addAddress($recipient_email);

        $owner_phone = !empty($booking_details['owner_phone']) ? htmlspecialchars($booking_details['owner_phone']) : 'N/A';

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Booking is Confirmed! (ID: #' . $booking_details['booking_id'] . ')';
        $mail->Body    = "
            <h2>Booking Confirmed!</h2>
            <p>Dear {$booking_details['guest_name']},</p>
            <p>Thank you for your booking. Here are your details:</p>
            <ul>
                <li><strong>Booking ID:</strong> #{$booking_details['booking_id']}</li>
                <li><strong>Hotel:</strong> {$booking_details['hotel_name']}</li>
                <li><strong>Room:</strong> {$booking_details['room_name']}</li>
                <li><strong>Check-in:</strong> {$booking_details['checkin']}</li>
                <li><strong>Check-out:</strong> {$booking_details['checkout']}</li>
                <li><strong>Total Paid:</strong> ₹" . number_format($booking_details['total_price'], 2) . "</li>
            </ul>
            <hr>
            <h3>Hotel Contact Details:</h3>
            <p>For any questions regarding your stay, please contact the property owner directly:</p>
            <ul>
                <li><strong>Owner Name:</strong> {$booking_details['owner_name']}</li>
                <li><strong>Owner Phone:</strong> {$owner_phone}</li>
            </ul>
            <p>Thank you for choosing us!</p>";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) { 
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * Sends a new booking notification email to the property owner.
 */
function send_owner_booking_notification_email($recipient_email, $booking_details) {
    $mail = new PHPMailer(true);
    try {
        // --- DEBUGGING DISABLED ---
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; 

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ADMIN_EMAIL;
        $mail->Password   = ADMIN_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(ADMIN_EMAIL, 'Mantralayam Rooms Booking');
        $mail->addAddress($recipient_email);

        $guest_phone = !empty($booking_details['guest_phone']) ? htmlspecialchars($booking_details['guest_phone']) : 'N/A';

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Booking Notification! (ID: #' . $booking_details['booking_id'] . ')';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2>New Booking Alert!</h2>
                <p>Congratulations! One of your rooms has been booked. Here are the details:</p>
                <ul>
                    <li><strong>Booking ID:</strong> #{$booking_details['booking_id']}</li>
                    <li><strong>Hotel:</strong> {$booking_details['hotel_name']}</li>
                    <li><strong>Room:</strong> {$booking_details['room_name']}</li>
                    <li><strong>Check-in:</strong> {$booking_details['checkin']}</li>
                    <li><strong>Check-out:</strong> {$booking_details['checkout']}</li>
                    <li><strong>Total Paid:</strong> ₹" . number_format($booking_details['total_price'], 2) . "</li>
                </ul>
                <hr>
                <h3>Guest Details:</h3>
                <ul>
                    <li><strong>Name:</strong> {$booking_details['guest_name']}</li>
                    <li><strong>Email:</strong> {$booking_details['guest_email']}</li>
                    <li><strong>Phone:</strong> {$guest_phone}</li>
                </ul>
            </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // In a real app, you would log this error instead of returning it
        error_log("Owner Notification Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
