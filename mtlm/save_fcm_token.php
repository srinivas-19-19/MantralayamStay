<?php
require 'db_connect.php';

// This will create an 'fcm_log.txt' file in your main directory with any errors for debugging.
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/fcm_log.txt');

// Check if the request is a POST request and if a token is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    
    // Check if the user is logged in by looking at the session
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_role']; // 'guest' or 'owner'

        // --- NEW & MORE ROBUST SQL QUERY ---
        // This query will INSERT a new token for the user. If that user already
        // has a token, it will UPDATE the existing row with the new token.
        // This is the correct way to handle logins from multiple devices.
        $stmt = $conn->prepare("
            INSERT INTO user_devices (user_id, user_type, fcm_token) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE fcm_token = ?
        ");
        $stmt->bind_param("isss", $user_id, $user_type, $token, $token);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo "Token for {$user_type} ID {$user_id} saved successfully.";
        } else {
            error_log("FCM Save DB Error: " . $stmt->error);
            http_response_code(500);
            echo "Database error.";
        }
        $stmt->close();

    } else {
        error_log("FCM Save Error: Session data (user_id or user_role) not found.");
        http_response_code(401); // Unauthorized
        echo "Error: User not logged in.";
    }
} else {
    error_log("FCM Save Error: Invalid request.");
    http_response_code(400); // Bad Request
    echo "Error: Invalid request.";
}
$conn->close();
?>
