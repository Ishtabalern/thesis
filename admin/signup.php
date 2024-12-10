<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "admin"; 
$password = "123"; 
$dbname = "cskdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $employee_id = $_POST['employee_id'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($pass === $confirm_pass) {
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user (username, employee_id, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $user, $employee_id, $hashed_password);

        if ($stmt->execute()) {
            $message = "Employee account created successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Passwords do not match.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSK - Create Employee Account</title>
    <link rel="stylesheet" href="signup.css">
</head>
<body>
    
<div class="content">
        <h2>Create Employee Account</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="employee_id" placeholder="Employee ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Re Enter Password" required>
            <button type="submit">Create Account</button>
            <a href="AdminHome.php" class="back">Back to Home</a>
        </form>
</div>
    
</body>
</html>
