<?php

// Start session to check if the user is logged in
session_start();

// Check if the user is logged in as an employee
if (!isset($_SESSION['logged_in']) && !isset($_SESSION['logged_in'])) {
    // If not logged in, redirect to login page
    header("Location: sign-in.php");
    exit();
}

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

// Fetch the count of receipts
$receipt_count = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM receipts");
if ($result && $row = $result->fetch_assoc()) {
    $receipt_count = $row['total'];
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
               receipts.category, receipts.type, receipts.payment_method, receipts.total 
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
    <title>Dashboard - Financial Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/records.css">
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add customer</a></li>
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
            <a class="btn-tabs" href="reports.php"><i class="fa-solid fa-file"></i>Reports</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="#"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Financial Records</h1>
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="record-summary">
                <button class="summary-btn">All Receipt<br><?php echo $receipt_count; ?></button>
                <button class="summary-btn">Sales<br>5</button>
                <button class="summary-btn">Expense<br>15</button>

                <!-- Client Dropdown Filter -->
                <label for="clientFilter">Filter by Client:</label>
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

            <div class="data-table">
                <table id="recordsTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Payment Method</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["id"] . "</td>"; 
                                echo "<td>" . $row["client_name"] . "</td>";  
                                echo "<td>" . $row["date"] . "</td>";
                                echo "<td>" . $row["vendor"] . "</td>";
                                echo "<td>" . $row["category"] . "</td>";
                                echo "<td>" . $row["type"] . "</td>";
                                echo "<td>" . $row["payment_method"] . "</td>";
                                echo "<td>" . $row["total"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script/dashboard.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable();
            $('#clientFilter').on('change', function () {
                window.location.href = '?client_id=' + encodeURIComponent($(this).val());
            });
        });
    </script>
</body>
</html>
