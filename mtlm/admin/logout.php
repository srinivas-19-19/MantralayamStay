<?php
// Start the session so we can access it
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect to the admin login page
header("Location: login.php");
exit();
