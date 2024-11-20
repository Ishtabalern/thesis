<?php
session_start();
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "cskdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_user = $_POST['username'];
    $admin_pass = $_POST['password'];

    // Validate admin credentials (this example uses a simple check)
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($admin_pass, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true && $_SESSION['logged_in'] = true;
        header("Location: admin/adminhome.php");
        exit();
    } else {
        $message = "Invalid username or password.";
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
