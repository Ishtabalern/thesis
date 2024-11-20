<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cskdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-house"></i>Home</a>
        <a href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
        <a href="records.php" class="active"><i class="fa-solid fa-file"></i>Financial Records</a>
        <a href="#"><i class="fa-solid fa-file-export"></i>Generate Report</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Financial Records</h1>
            <div class="user-controls">
                <button class="logout-btn">Log out</button>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="category-buttons">
                <button class="btn add-category-btn">Add Category</button>
                <button class="btn delete-category-btn">Delete Category</button>
            </div>

            <div class="record-summary">
                <button class="summary-btn">All Receipt<br>20</button>
                <button class="summary-btn">Sales<br>5</button>
                <button class="summary-btn">Expense<br>15</button>
                <input type="text" class="search-bar" placeholder="Search">
            </div>

            <div class="data-table">
                <table id="recordsTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT id, date, vendor, 'N/A' AS category, total FROM receipts";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["id"] . "</td>";
                                echo "<td>" . $row["date"] . "</td>";
                                echo "<td>" . $row["vendor"] . "</td>";
                                echo "<td>" . $row["category"] . "</td>";
                                echo "<td>₱" . $row["total"] . "</td>";
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
            $('#recordsTable').DataTable({
                paging: true,
                searching: true,
                order: [[0, 'asc']], // Default sort by Id
                columnDefs: [
                    { orderable: true, targets: [0, 1, 4] }, // Enable sorting for Id, Date, and Total Price
                    { orderable: false, targets: [2, 3] }   // Disable sorting for Vendor and Category
                ]
            });
        });
    </script>
</body>
</html>
