<?php
// Start the session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "cskdb"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
$message = ""; // To store login status message
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // Prepare and execute query to check if the user exists
    $stmt = $conn->prepare("SELECT username, password FROM user WHERE employee_id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_username, $db_password);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            // Login successful: Set session variables
            $_SESSION['employee_id'] = $employee_id;
            $_SESSION['username'] = $db_username;

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "Employee ID not found.";
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
    <title>CSK - Login</title>
    <link rel="stylesheet" href="styles/login.css">
    <style>
        .error-message {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="right-section">
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">Login ▼</button>
                <div id="dropdownContent" class="dropdown-content">
                    <a href="admin_login.php">Admin</a>
                    <a href="login.php">Employee</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <h1>Employee Login</h1>

        <!-- Display login feedback message -->
        <?php if (!empty($message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" class="input-field" name="employee_id" placeholder="Employee ID" required>
            <input type="password" class="input-field" name="password" placeholder="Password" required>
            <button type="submit" class="submit">ENTER</button>
        </form>
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>
    <script src='./script/admin_login.js'>
       
    </script>
</body>
</html>
