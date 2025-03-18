<?php
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
$clientResult = $conn->query("SELECT DISTINCT client_id FROM receipts");
$clients = [];
while ($row = $clientResult->fetch_assoc()) {
    $clients[] = $row['client_id'];
}

$clientFilter = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$sql = "SELECT id, client_id, date, vendor, category, type, total FROM receipts";
if (!empty($clientFilter)) {
    $sql .= " WHERE client_id = '" . $conn->real_escape_string($clientFilter) . "'";
}
$result = $conn->query($sql);
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
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <a class="btn-tabs" href="dashboard.php"><i class="fa-solid fa-house"></i>Home</a>
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
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
        

        <!-- Main Content -->
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
                <option value="<?php echo htmlspecialchars($client); ?>" <?php echo ($client === $clientFilter) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($client); ?>
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
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT id, client_id, date, vendor, category, type, total FROM receipts";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["id"] . "</td>";
                                echo "<td>" . $row["client_id"] . "</td>";
                                echo "<td>" . $row["date"] . "</td>";
                                echo "<td>" . $row["vendor"] . "</td>";
                                echo "<td>" . $row["category"] . "</td>";
                                echo "<td>" . $row["type"] . "</td>";
                                echo "<td>" . $row["total"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable();

            $('#clientFilter').on('change', function () {
                const selectedClient = $(this).val();
                window.location.href = '?client=' + encodeURIComponent(selectedClient);
            });
        });
    </script>
</body>
</html>
