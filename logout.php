<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear the remember me cookie if it exists
if (isset($_COOKIE['email'])) {
    setcookie('email', '', time() - 3600, '/');
}

// Redirect to login page
header("Location: patient_login.php");
exit();
?>
