<?php
// Start session to destroy the session
session_start();
session_unset(); // Clear session variables
session_destroy(); // Destroy session and redirect to login page
header("Location: sign-in.php");
exit();
?>
