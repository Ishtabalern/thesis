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
$result = $conn->query("SELECT COUNT(*) AS total FROM scanned_receipts");
if ($result && $row = $result->fetch_assoc()) {
    $receipt_count = $row['total'];
}

// Fetch clients for dropdown
$clients = [];
$clientResult = $conn->query("SELECT id, name FROM clients");
while ($clientRow = $clientResult->fetch_assoc()) {
    $clients[] = $clientRow;
}

// Handle AJAX request for new client creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_client'])) {
    $newClient = trim($_POST['new_client']);

    // Check if client already exists
    $checkStmt = $conn->prepare("SELECT id FROM clients WHERE name = ?");
    $checkStmt->bind_param("s", $newClient);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Client already exists."]);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    // Insert new client
    $stmt = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
    $stmt->bind_param("s", $newClient);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "client_id" => $stmt->insert_id, "client_name" => $newClient]);
    } else {
        echo json_encode(["success" => false, "message" => "Error adding client."]);
    }
    $stmt->close();
    exit();
}

// Handle data updates (for editing receipts)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updatedData'])) {
    $updatedData = json_decode($_POST['updatedData'], true);

    foreach ($updatedData as $row) {
        $id = $row['id'];
        $date = $row['date'];
        $vendor = $row['vendor'];
        $category = $row['category'];
        $type = $row['type'];
        $total = $row['total'];

        $stmt = $conn->prepare("UPDATE scanned_receipts SET date = ?, vendor = ?, category = ?, type = ?, total = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $date, $vendor, $category, $type, $total, $id);
        $stmt->execute();
        $stmt->close();
    }

    echo "Data updated successfully!";
    exit();
}

// Handle report generation (moving data to receipts table)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['client'])) {
    $selectedClient = $_POST['client'];

    // Ensure selectedClient is a valid ID
    if (!is_numeric($selectedClient) || empty($selectedClient)) {
        echo json_encode(["success" => false, "message" => "Invalid client selected."]);
        exit();
    }

    // Move receipts from scanned_receipts to receipts table
    $sql = "INSERT INTO receipts (date, vendor, category, type, total, img_url, client_id) 
            SELECT date, vendor, category, type, total, img_url, ? 
            FROM scanned_receipts";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selectedClient);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->query("DELETE FROM scanned_receipts"); // Clear scanned receipts table
        echo json_encode(["success" => true, "message" => "Report generated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: No data moved."]);
    }

    $stmt->close();
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Capture Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/scan.css">
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        /* Include the CSS below directly in your HTML for simplicity or link to an external CSS file */
    </style>
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
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="#"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Capture Documents</h1>
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Scan and Scanner dropdown side by side -->
            <div class="scan-options">
                <button class="scan-btn">
                    <i class="fa-solid fa-qrcode"></i><br>
                    Scan
                </button>

                <div class="scanner-dropdown">
                    <select>
                        <option value="epson">Raspberry Pi 4</option>
                    </select>
                </div>
            </div>

            <!-- Receipts viewer -->
            <div class="receipt-card">
                <div class="document-placeholder">
                    <i class="fa-solid fa-plus"></i>
                    <p>Scanned Documents / Receipt Here</p>
                </div>
            </div>
        </div>
    </div>

    <div class="receipt-table">
        <div class="data-table">
            <table id="recordsTable">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Total Price</th>
                        <th>Receipt Image</th>
                        </tr>
                </thead>
                <tbody>
                    <?php
                        $sql = "SELECT id, date, vendor, category, type, total, img_url FROM scanned_receipts";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td contenteditable='true' data-column='date'>" . $row["date"] . "</td>";
                            echo "<td contenteditable='true' data-column='vendor'>" . $row["vendor"] . "</td>";
                            echo "<td contenteditable='true' data-column='category'>" . $row["category"] . "</td>";
                            echo "<td contenteditable='true' data-column='type'>" . $row["type"] . "</td>";
                            echo "<td contenteditable='true' data-column='total'>" . $row["total"] . "</td>";
                            echo "<td>" . $row["img_url"] . "</td>";
                            echo "</tr>";
                            }
                        } else {
                                    
                                }
                    ?>
                </tbody>
            </table>

            <button id="saveChanges" class="btn save-btn">Save Changes</button>
            <!-- Client Dropdown -->
            <form id="clientForm" method="POST">
                <label for="client">Select Client:</label>
                <select id="clientSelect" name="client">
                    <option value="">-- Select Client --</option>
                    <option value="add_client">+ Add New Client</option>
                    <?php
                    $result = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");
                    while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>
            </form>

            <!-- New Client Modal -->
            <div id="newClientModal" style="display: none;">
                <label for="new_client_name">New Client Name:</label>
                <input type="text" id="new_client_name">
                <button id="confirmNewClient">Confirm</button>
            </div>
            <button type="submit" name="generateReport" class="btn save-btn">Generate Report</button>
        </div>
    </div>

    <script src="script/dashboard.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable(); // Initialize DataTable

            // Save Changes (Update scanned_receipts)
            $('#saveChanges').click(function () {
                const updatedData = [];
                $('#recordsTable tbody tr').each(function () {
                    const row = $(this);
                    const id = row.find('td:eq(0)').text(); // Receipt ID
                    const date = row.find('td:eq(1)').text();
                    const vendor = row.find('td:eq(2)').text();
                    const category = row.find('td:eq(3)').text();
                    const type = row.find('td:eq(4)').text();
                    const total = row.find('td:eq(5)').text();

                    updatedData.push({ id, date, vendor, category, type, total });
                });

                $.ajax({
                    url: 'update_scanned_receipts.php', // PHP script to update scanned_receipts
                    method: 'POST',
                    data: { updatedData: JSON.stringify(updatedData) },
                    success: function (response) {
                        alert(response);
                    },
                    error: function () {
                        alert("Error updating receipts.");
                    }
                });
            });

            // Toggle new client modal when "Add New Client" is selected
            $('#clientSelect').change(function () {
                if ($(this).val() === 'add_client') {
                    $('#newClientModal').show();
                } else {
                    $('#newClientModal').hide();
                }
            });

            // Confirm new client via AJAX
            $('#confirmNewClient').click(function () {
                let newClientName = $('#new_client_name').val().trim();
                let clientDropdown = $('#clientSelect');

                if (newClientName === "") {
                    alert("Please enter a valid client name.");
                    return;
                }

                $.ajax({
                    url: 'create_client.php', // PHP script to add new client
                    method: 'POST',
                    data: { new_client: newClientName },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            alert("Client added successfully!");

                            // Add new client to the dropdown
                            let newOption = new Option(newClientName, response.client_id, true, true);
                            clientDropdown.append(newOption);

                            // Hide modal and clear input field
                            $('#newClientModal').hide();
                            $('#new_client_name').val("");
                        } else {
                            alert(response.message || "Error adding client.");
                        }
                    },
                    error: function () {
                        alert("Error communicating with the server.");
                    }
                });
            });

            // Generate Report (Move Data from scanned_receipts to receipts table)
            $('.save-btn[name="generateReport"]').click(function () {
                let selectedClient = $('#clientSelect').val();

                if (!selectedClient) {
                    alert("Please select a client before generating a report.");
                    return;
                }

                $.ajax({
                    url: 'generate_report.php', // PHP script to handle report generation
                    method: 'POST',
                    data: { client: selectedClient },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            alert("Report generated successfully!");
                            location.reload(); // Refresh the page after success
                        } else {
                            alert(response.message || "Error generating report.");
                        }
                    },
                    error: function () {
                        alert("Error communicating with the server.");
                    }
                });
            });

            // Scan button triggers script on Raspberry Pi
            $('.scan-btn').click(() => {
                fetch('http://raspberrypi:5000/run-script', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Script ran successfully:\n' + data.output);
                    } else {
                        alert('Error running script:\n' + data.error);
                    }
                })
                .catch(error => {
                    alert('Failed to connect to the server:\n' + error);
                });
            });
        });
</script>
</body>
</html>
