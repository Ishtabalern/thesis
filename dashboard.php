<?php
// Start session to check if the user is logged in
session_start();

// Check if the user is logged in as an employee
if (!isset($_SESSION['logged_in']) && !isset($_SESSION['logged_in'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "admin";  // Change if necessary
$password = "123";     // Change if necessary
$dbname = "cskdb";     // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Get the username from the session
$username = $_SESSION['username'];

// Fetch the count of Expense
$expense_count = 0;
$result = $conn->query("SELECT SUM(total) AS total_expense FROM receipts WHERE type='Expense'");
if ($result && $row = $result->fetch_assoc()) {
    $expense_count = $row['total_expense'] ?? 0;
}

// Fetch the count of Sales
$sales_count = 0;
$result = $conn->query("SELECT SUM(total) AS total_sales FROM receipts WHERE type='Sales'");
if ($result && $row = $result->fetch_assoc()) {
    $sales_count = $row['total_sales'] ?? 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <a class="btn-tabs" href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="#"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Home</h1>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2> <!-- Display employee's username -->
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <div class="subcontainer">
            <div class="report-card">
                <h2>Total Expenses</h2>
                <h3>P<?php echo $expense_count; ?></h3>
            </div>
            <div class="report-card">
                <h2>Latest Income</h2>
                <h3>P<?php echo $sales_count; ?></h3>
            </div>
        </div>

        <div class="transaction-container">
            <h2>Transaction History</h2>
            <ul class="transaction-list">
                <li>
                    <span class="id">1</span>
                    <span class="date">Oct 24, 2024</span>
                    <span class="location">CVSU - Bacoor</span>
                    <span class="item">Fan</span>
                    <span class="price">₱45</span>
                </li>
                <li>
                    <span class="id">2</span>
                    <span class="date">Oct 24, 2024</span>
                    <span class="location">CVSU - Bacoor</span>
                    <span class="item">Fan</span>
                    <span class="price">₱45</span>
                </li>
                <!-- Repeat for more transactions -->
            </ul>
        </div>
    </div>
</body>
</html>
