<?php
session_start();
session_unset(); // Clear session variables
session_destroy(); // Destroy session
header("Location: /thesis/sign-in.php"); // Redirect to login page
exit();
?>
