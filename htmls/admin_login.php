<?php
// Start the session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "admin"; // Your database username
$password = "123";   // Your database password
$dbname = "cskdb";   // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
$message = ""; // To store login status message
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_username = $_POST['username'];
    $admin_password = $_POST['password'];

    // Prepare and execute query to check if the admin exists
    $stmt = $conn->prepare("SELECT username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_username, $db_password);
        $stmt->fetch();

        if (password_verify($admin_password, $db_password)) {
            // Login successful: Set session variables
            $_SESSION['username'] = $db_username;
            $_SESSION['logged_in'] = true;

            // Redirect to admin home page
            header("Location: admin/AdminHome.php");
            exit();
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "Username not found.";
    }

    $stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles/login.css"> <!-- Link to your CSS file -->
</head>
<body>
    <!-- Navbar Section -->
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

    <!-- Login Container -->
    <div class="container">
        <h1>Login</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <form action="" method="POST">
            <input type="text" name="username" class="input-field" placeholder="Username" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" class="submit">Log In</button>
        </form>
    </div>

    <script src='./script/admin_login.js'>
        
    </script>
</body>
</html>
