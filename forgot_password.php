<?php
// Start the session
session_start();

// If the user is already logged in, redirect to the dashboard
if (isset($_SESSION['employee_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "admin";
$password = "123";
$dbname = "cskdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the employee ID
    $employee_id = $_POST['employee_id'];

    // Insert password reset request into the database
    $stmt = $conn->prepare("INSERT INTO password_reset_requests (employee_id) VALUES (?)");
    $stmt->bind_param("i", $employee_id);

    if ($stmt->execute()) {
        $message = "Password reset request has been submitted. The admin will review it shortly.";
    } else {
        $message = "Failed to submit request. Please try again.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>

        <!-- Display success or error message -->
        <?php if (isset($message)): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <input type="text" class="input-field" name="employee_id" placeholder="Enter your Employee ID" required>
            <button type="submit" class="submit">Request Password Reset</button>
        </form>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>
