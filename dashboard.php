<?php
// Start session to check if the user is logged in
session_start();

// Check if the user is logged in as an employee
if (!isset($_SESSION['logged_in']) && !isset($_SESSION['logged_in'])) {
    // If not logged in, redirect to login page
    header("Location: sign-in.php");
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

// Fetch distinct client names for dropdown
$clients = [];
$clientResult = $conn->query("SELECT DISTINCT clients.id, clients.name 
                              FROM receipts 
                              LEFT JOIN clients ON receipts.client_id = clients.id");

if ($clientResult->num_rows > 0) {
    while ($row = $clientResult->fetch_assoc()) {
        $clients[] = ['id' => $row['id'], 'name' => $row['name']];
    }
}

// Filter by client if selected
$clientFilter = $_GET['client_id'] ?? null;

$sql = "SELECT receipts.id, COALESCE(clients.name, 'Unknown') AS client_name, receipts.date, receipts.vendor, 
               receipts.category, receipts.type, receipts.total 
        FROM receipts 
        LEFT JOIN clients ON receipts.client_id = clients.id";

if ($clientFilter !== null && $clientFilter !== "") {
    $sql .= " WHERE receipts.client_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $clientFilter);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
} else {
    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=scan" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=receipt_long" />
</head>
<body>

    <div class="modal-overlay"></div>
        <!-- The Modal -->
        <div id="newModal" class="newModal">
            <!-- Modal content -->
            <div class="modal-content">
                <span class="close"></span>
                <div class="flyoutColumn">
                    <h3>Customer</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Invoice</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Receive payment</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Statement</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Estimate</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Credit note</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Sales receipt</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Refund receipt</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Delayed credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Delayed charge</a></li>
                        <li><a class="btn-tabs" href="client_form.php" class="active">Add customer</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Suppliers</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Expense</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Cheque</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Bill</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Pay bills</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Purchase order</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Supply credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Credit card credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add supplier</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Team</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Single time activity</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Weekly timesheet</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Other</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Bank deposit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Transfer</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Journal entry</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Pay down credit card</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add product/service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    
    <div class="sidebar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <button id="myBtn" class="modalBtn"><i class="fa-solid fa-plus"></i>New</button>
            <a class="btn-tabs" href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-wallet"></i>Record Expense</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Home</h1>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2> <!-- Display employee's username -->
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                    <button class="dropbtn">Employee</button>
                </div>
            </div>
        </div>
        <div class="client-dropdown">
            <label for="clientFilter">Choose Client: </label>
                <select id="clientFilter">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client) { ?>
                        <option value="<?php echo htmlspecialchars($client['id']); ?>" 
                            <?php echo ($client['id'] == $clientFilter) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name'] ?? "Unknown"); ?>
                        </option>
                    <?php } ?>
                </select>
        </div>

        <div class="second-container">
            <div class="shortcut-container">
                <h2>Shortcuts</h2>
                <div class="shortcut-list">
                    <div class="shortcut-a">
                        <button class="button-a" onclick="location.href='scan.php'">
                            <i class="fa-solid fa-qrcode" style="font-size: 50px;"></i>
                        </button>
                        <h4>Scan receipts</h4>
                    </div>
                    <div class="shortcut-b">
                        <button class="button-b">
                            <i class="fa-solid fa-file-invoice" style="font-size: 50px;"></i>
                        </button>
                        <h4>Create invoice</h4>
                    </div>
                    <div class="shortcut-c">
                        <button class="button-c">
                            <i class="fa-solid fa-piggy-bank" style="font-size: 50px;"></i>
                        </button>
                        <h4>Add bank account</h4>
                    </div>
                    <div class="shortcut-d">
                        <button class="button-d">
                            <i class="fa-solid fa-money-check-dollar" style="font-size: 50px;"></i>
                        </button>
                        <h4>Create cheque</h4>
                    </div>
                </div>
            </div>
            <div class="tasks-container">
                <i class="fa-solid fa-list-check" style="font-size: 100px;"></i>
                <span>No Tasks Yet!</span>
            </div>
        </div>

        <div class="third-container">
            <div class="bank-account">
                <h2>bank</h2>
            </div>
            <div class="profit-loss">
                <h2>profits and loss</h2>
            </div>
            <div class="report-card">
                <h2>Expenses</h2>
                <h3>₱<?php echo number_format($expense_count, 2); ?></h3>
            </div>
            <div class="report-card">
                <h2>Invoices</h2>
                <h3>₱<?php echo number_format($sales_count, 2); ?></h3>
            </div>
        </div>

    </div>
    <script src="script/dashboard.js"></script>
</body>
</html>
