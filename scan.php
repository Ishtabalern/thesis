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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['client'])) {
    $selectedClient = intval($_POST['client']);

    $sql = "SELECT date, vendor, category, type, total, img_url FROM scanned_receipts";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $insertStmt = $conn->prepare("INSERT INTO receipts (date, vendor, category, type, total, img_url, client_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

        while ($row = $result->fetch_assoc()) {
            // Convert the date using PHP
            $dateFormats = ['n/j/Y', 'm/d/Y', 'Y-m-d', 'd/m/Y'];
            $formattedDate = null;

            foreach ($dateFormats as $format) {
                $tryDate = DateTime::createFromFormat($format, $row['date']);
                if ($tryDate !== false) {
                    $formattedDate = $tryDate;
                    break;
                }
            }

            $mysqlDate = $formattedDate ? $formattedDate->format('Y-m-d') : null;

            if (!$mysqlDate) {
                error_log("Failed to parse date: " . $row['date']);
            }
            
            $insertStmt->bind_param(
                "ssssdsi",
                $mysqlDate,
                $row['vendor'],
                $row['category'],
                $row['type'],
                $row['total'],
                $row['img_url'],
                $selectedClient
            );

            $insertStmt->execute();
        }

        $insertStmt->close();
        $conn->query("DELETE FROM scanned_receipts");

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "No receipts to move."]);
    }

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-wallet"></i>Record Expense</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="reports.php"><i class="fa-solid fa-file"></i>Reports</a>
            <a class="btn-tabs" href="balance_sheet.php"><i class="fa-solid fa-file"></i>Balance Sheet</a>
            <a class="btn-tabs" href="income_statement.php"><i class="fa-solid fa-file"></i>Income Statement</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Record Expense</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            
            <!-- Receipts viewer -->
            <div class="receipt-table">
                <div class="data-table">
                    <table id="recordsTable">
                        <thead>
                        <form id="clientForm" method="POST">
                            <select id="clientSelect" name="client" class="client-dropdown" style="margin-bottom: 10px;">
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
                            <tr>
                                <th>Id</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Payment Method</th>
                                <th>Total Price</th>
                                <th>Receipt Image</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sql = "SELECT id, date, vendor, category, type, payment_method, total, img_url FROM scanned_receipts";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row["id"] . "</td>";
                                    echo "<td contenteditable='true' data-column='date'>" . $row["date"] . "</td>";
                                    echo "<td contenteditable='true' data-column='vendor'>" . $row["vendor"] . "</td>";
                                    echo "<td contenteditable='true' data-column='category'>" . $row["category"] . "</td>";
                                    echo "<td contenteditable='true' data-column='type'>" . $row["type"] . "</td>";
                                    echo "<td contenteditable='true' data-column='type'>" . $row["payment_method"] . "</td>";
                                    echo "<td contenteditable='true' data-column='total'>₱" . number_format($row["total"], 2) . "</td>";
                                    echo "<td>" . $row["img_url"] . "</td>";
                                    echo "</tr>";
                                    }
                                } else {
                                            
                                        }
                            ?>
                        </tbody>
                    </table>

                    <!-- Add Receipt Modal -->
                    <div id="addReceiptModal" class="modal-overlay" style="display: none;">
                        <div class="modal-content" style="max-width: 400px; margin: 10% auto; background: white; padding: 20px; border-radius: 10px;">
                            <span id="closeAddModal" style="float: right; cursor: pointer;">&times;</span>
                            <h3>Add New Receipt</h3>
                            <form id="addReceiptForm">
                                <label>Date:</label>
                                <input type="text" name="date" id="receiptDate" required placeholder="yyyy/mm/dd" autocomplete="off">
                                <br><br>
                                <label>Vendor:</label>
                                <input type="text" name="vendor" required><br><br>
                                <label>Category:</label>
                                <input type="text" name="category"><br><br>
                                <label>Type:</label>
                                <input type="text" name="type"><br><br>
                                <label>Payment Method:</label>
                                <input type="text" name="payment_method"><br><br>
                                <label>Total:</label>
                                <input type="number" name="total" step="0.01"><br><br>
                                <label>Image URL:</label>
                                <input type="text" name="img_url"><br><br>
                                <button type="submit" class="btn save-btn">Add Receipt</button>
                            </form>
                        </div>
                    </div>


                    <button id="openAddModal" class="btn save-btn" style="background-color: #062335; color: #fff;">Add Receipt</button>
                    <button id="saveChanges" class="btn save-btn" style="background-color: #00AF7E; color: #fff;">Save Changes</button>
                    <!-- New Client Modal -->
                    <div id="newClientModal" style="display: none;">
                        <label for="new_client_name">New Client Name:</label>
                        <input type="text" id="new_client_name">
                        <button id="confirmNewClient" class="btn save-btn">Confirm</button>
                    </div>
                    <button type="submit" name="generateReport" class="btn save-btn" style="background-color: #E74C3C; color: #fff;">Generate Report</button>
                </div>
            </div>

            <!-- Scan and Scanner dropdown side by side -->
            <div class="scan">
                <h2>Do you have an image of your receipt? click here to automatically record your expenses!</h2>
                <div class="scan-options">
                    <button class="scan-btn">
                        <i class="fa-solid fa-qrcode"></i><br>
                        Scan on Raspberry Pi
                    </button>
                    <button class="scan-btn">
                        <i class="fa-solid fa-folder"></i><br>
                        Scan on device
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="script/dashboard.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable(); // Initialize DataTable

            // Modal toggle
            $('#openAddModal').click(() => $('#addReceiptModal').show());
            $('#closeAddModal').click(() => $('#addReceiptModal').hide());

            // Handle form submission
            $('#addReceiptForm').submit(function (e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: 'add_expense.php',
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        alert(response);
                        $('#addReceiptModal').hide();
                        location.reload(); // Optional: reload to reflect new data
                    },
                    error: function () {
                        alert("Failed to add receipt.");
                    }
                });
            });

            flatpickr("#receiptDate", {
                dateFormat: "Y/m/d", // yyyy/mm/dd
                allowInput: true,    // still allows manual typing
            });


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
                    url: 'functions/update_scanned_receipts.php', // PHP script to update scanned_receipts
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
                    url: 'functions/create_client.php', // PHP script to add new client
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
                    url: 'functions/generate_report.php', // PHP script to handle report generation
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
