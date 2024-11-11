<?php
// Start session to destroy the session
session_start();

// Destroy session and redirect to login page
session_destroy();
header("Location: login.php");
exit();
?>
