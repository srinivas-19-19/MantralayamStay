<?php
// db_connect.php

// --- NEW: Define the Base URL for the entire website ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain_name = $_SERVER['HTTP_HOST'];
// This creates the full base path, e.g., "http://mantralayamroomsbooking.com/"
define('BASE_URL', $protocol . $domain_name . '/');

// Start the session on every page that includes this file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Your existing database credentials ---
$servername = "localhost";
$username = "u326043007_admin";
$password = "Mtlm@518345";
$dbname   = "u326043007_hotel_booking";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
