<?php
// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';
require 'mail_config.php';
require 'whatsapp_config.php';
require 'fcm_handler.php';
require 'razorpay-php/Razorpay.php'; // Include the Razorpay SDK

use Razorpay\Api\Api;

// Security Check: Ensure only logged-in guests can create bookings
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'guest') {
    die("Access Denied: Only registered guests can book rooms.");
}

// Check if all required data has been received from Razorpay
if (isset($_POST['razorpay_payment_id']) && isset($_POST['room_id']) && isset($_POST['phone'])) {
    
    // --- Razorpay API Credentials ---
    // IMPORTANT: Replace with your LIVE keys from the Razorpay dashboard
    $key_id = 'rzp_test_KuzHcApwGC87hl';
    $key_secret = 'KekrcVcUxbfgPyQETsDOJkAF'; // **REPLACE THIS**
    $api = new Api($key_id, $key_secret);

    $payment_id = $_POST['razorpay_payment_id'];
    $total_price = $_POST['total_price'];
    $total_price_paise = $total_price * 100; // Amount in paise

    try {
        // --- STEP 1: CAPTURE THE PAYMENT ---
        $payment = $api->payment->fetch($payment_id);
        $payment->capture(['amount' => $total_price_paise, 'currency' => 'INR']);
        
    } catch (Exception $e) {
        // If capture fails, log the error and stop.
        error_log("Razorpay Capture Error: " . $e->getMessage());
        die("Payment failed. Please try again. Error: " . $e->getMessage());
    }

    // --- If capture is successful, proceed with the rest of the booking logic ---
    $guest_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'];
    $checkin = $_POST['checkin_date'];
    $checkout = $_POST['checkout_date'];
    $guest_phone = $_POST['phone'];

    $conn->begin_transaction();
    try {
        // Step 2: Update the guest's phone number in the database
        $stmt_phone = $conn->prepare("UPDATE guests SET phone_number = ? WHERE id = ?");
        $stmt_phone->bind_param("si", $guest_phone, $guest_id);
        $stmt_phone->execute();
        $stmt_phone->close();

        // Step 3: Save the new booking to the 'bookings' table
        $stmt_booking = $conn->prepare("INSERT INTO bookings (guest_id, room_id, checkin_date, checkout_date, total_price, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_booking->bind_param("iissds", $guest_id, $room_id, $checkin, $checkout, $total_price, $payment_id);
        $stmt_booking->execute();
        $booking_id = $conn->insert_id;
        $stmt_booking->close();

        // Step 4: Update the room's status to 'hidden' to prevent double-booking
        $stmt_room = $conn->prepare("UPDATE rooms SET status = 'hidden' WHERE id = ?");
        $stmt_room->bind_param("i", $room_id);
        $stmt_room->execute();
        $stmt_room->close();

        // Step 5: Fetch All Details for All Notifications
        $sql = "SELECT 
                    g.full_name as guest_name, g.email as guest_email, g.phone_number as guest_phone,
                    r.name as room_name, h.hotel_name,
                    o.id as owner_id, o.full_name as owner_name, o.email as owner_email, o.mobile_number as owner_phone
                FROM bookings b
                JOIN guests g ON b.guest_id = g.id
                JOIN rooms r ON b.room_id = r.id
                JOIN hotels h ON r.hotel_id = h.id
                JOIN owners o ON h.owner_id = o.id
                WHERE b.id = ?";
        $stmt_details = $conn->prepare($sql);
        $stmt_details->bind_param("i", $booking_id);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_assoc();
        $stmt_details->close();
        
        if ($details) {
            // 5a. Send Notifications to Guest
            $guest_email_details = [ 'booking_id' => $booking_id, 'guest_name' => $details['guest_name'], 'hotel_name' => $details['hotel_name'], 'room_name' => $details['room_name'], 'checkin' => $checkin, 'checkout' => $checkout, 'total_price' => $total_price, 'owner_name' => $details['owner_name'], 'owner_phone' => $details['owner_phone'] ];
            send_booking_confirmation_email($details['guest_email'], $guest_email_details);

            if (!empty($details['guest_phone'])) {
                $guest_template_name = 'booking_confirmation_customer';
                $guest_template_params = [
                    ['type' => 'text', 'text' => $details['guest_name']],
                    ['type' => 'text', 'text' => $details['room_name']],
                    ['type' => 'text', 'text' => $details['hotel_name']],
                    ['type' => 'text', 'text' => $booking_id],
                    ['type' => 'text', 'text' => date('d M, Y', strtotime($checkin))],
                    ['type' => 'text', 'text' => date('d M, Y', strtotime($checkout))],
                    ['type' => 'text', 'text' => number_format($total_price, 2)],
                    ['type' => 'text', 'text' => $details['owner_name']],
                    ['type' => 'text', 'text' => $details['owner_phone']]
                ];
                send_whatsapp_confirmation($details['guest_phone'], $guest_template_params, $guest_template_name);
            }

            // 5b. Send Notifications to Owner
            if (!empty($details['owner_email'])) {
                $owner_email_details = array_merge($guest_email_details, ['guest_email' => $details['guest_email'], 'guest_phone' => $details['guest_phone']]);
                send_owner_booking_notification_email($details['owner_email'], $owner_email_details);
            }

            if (!empty($details['owner_phone'])) {
                $owner_template_name = 'new_booking_notification_owner';
                $owner_template_params = [
                    ['type' => 'text', 'text' => $details['room_name']],
                    ['type' => 'text', 'text' => $booking_id],
                    ['type' => 'text', 'text' => date('d M, Y', strtotime($checkin))],
                    ['type' => 'text', 'text' => date('d M, Y', strtotime($checkout))],
                    ['type' => 'text', 'text' => number_format($total_price, 2)],
                    ['type' => 'text', 'text' => $details['guest_name']],
                    ['type' => 'text', 'text' => $details['guest_phone'] ?? 'N/A'],
                    ['type' => 'text', 'text' => $details['guest_email']]
                ];
                send_whatsapp_confirmation($details['owner_phone'], $owner_template_params, $owner_template_name);
            }

            // 5c. Send Push Notifications
            $stmt_guest_token = $conn->prepare("SELECT fcm_token FROM user_devices WHERE user_id = ? AND user_type = 'guest'");
            $stmt_guest_token->bind_param("i", $guest_id);
            $stmt_guest_token->execute();
            if ($token_row = $stmt_guest_token->get_result()->fetch_assoc()) {
                send_fcm_notification($token_row['fcm_token'], "Booking Confirmed!", "Your booking for {$details['room_name']} is confirmed.");
            }
            $stmt_guest_token->close();

            $owner_id_for_token = $details['owner_id'];
            $stmt_owner_token = $conn->prepare("SELECT fcm_token FROM user_devices WHERE user_id = ? AND user_type = 'owner'");
            $stmt_owner_token->bind_param("i", $owner_id_for_token);
            $stmt_owner_token->execute();
            if ($token_row = $stmt_owner_token->get_result()->fetch_assoc()) {
                send_fcm_notification($token_row['fcm_token'], "New Booking!", "Your room '{$details['room_name']}' has been booked by {$details['guest_name']}.");
            }
            $stmt_owner_token->close();
        }

        // --- Step 6: Commit all changes ---
        $conn->commit();

        $_SESSION['last_booking_id'] = $booking_id;
        header("Location: confirmation.php");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log("Booking Processing Error: " . $exception->getMessage());
        die("A critical error occurred while processing your booking. Please contact support.");
    }
} else {
    header("Location: index.php");
    exit();
}
?>
