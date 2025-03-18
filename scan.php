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


// Handle data update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updatedData'])) {
    $updatedData = json_decode($_POST['updatedData'], true);

    foreach ($updatedData as $row) {
        $id = $row['id'];
        $date = $row['date'];
        $vendor = $row['vendor'];
        $category = $row['category'];
        $type =$row['type'];
        $total = $row['total'];

        $stmt = $conn->prepare("UPDATE scanned_receipts SET date = ?, vendor = ?, category = ?, type = ?, total = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $date, $vendor, $category, $type, $total, $id);
        $stmt->execute();
        $stmt->close();
    }

    echo "Data updated successfully!";
    exit();
}
// Handle adding new client & report generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['client'])) {
    $selectedClient = $_POST['client'];

    // If user selects "Add New Client" option
    if ($selectedClient === "add_client" && !empty($_POST['new_client'])) {
        $newClient = $_POST['new_client'];

        // Insert the new client into the database
        $stmt = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
        $stmt->bind_param("s", $newClient);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $newClientId = $stmt->insert_id; // Get the newly inserted client ID
            echo "<script>alert('New client added successfully!');</script>";
            $selectedClient = $newClientId; // Use the new client ID
        } else {
            echo "<script>alert('Error adding new client.');</script>";
        }

        $stmt->close();
    }

    // If a valid client (old or new) is selected, generate the report
    if ($selectedClient !== "add_client") {
        $sql = "INSERT INTO receipts (id, date, vendor, category, type, total, img_url, client_id) 
                SELECT id, date, vendor, category, type, total, img_url, ? 
                FROM scanned_receipts";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $selectedClient);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->query("DELETE FROM scanned_receipts");
            echo "<script>alert('Report generated successfully and data moved to receipts table.');</script>";
        } else {
            echo "<script>alert('Error: No data moved. Check for SQL issues.');</script>";
        }
    }
}

// Handle Generate Report
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generateReport'])) {
    $selectedClient = $_POST['client'];

    // Transfer data to receipts table
    $sql = "INSERT INTO receipts (id, date, vendor, category, type, total, img_url, client_id) 
            SELECT id, date, vendor, category, type, total, img_url, ? 
            FROM scanned_receipts";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedClient);
    $stmt->execute();

    // Clear the scanned_receipts table
    if ($stmt->affected_rows > 0) {
        $conn->query("DELETE FROM scanned_receipts");
        echo "<script>alert('Report generated successfully and data moved to receipts table.');</script>";
    } else {
        echo "<script>alert('Error: No data moved. Check for SQL issues.');</script>";
    }
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
                <form method="POST">
                        <label for="client">Select Client:</label>
                        <select name="client" id="client" required onchange="toggleNewClientInput()">
                            <option value="" disabled selected>Select a client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo htmlspecialchars($client['id']); ?>">
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="add_client">➕ Add New Client</option>
                        </select>

                        <!-- New Client Input (Hidden by Default) -->
                        <input type="text" name="new_client" id="new_client" placeholder="Enter new client name" style="display:none;">

                        <button type="submit" name="generateReport" class="btn save-btn">Generate Report</button>
                    </form>
            </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable();

            $('#saveChanges').click(function () {
                const updatedData = [];
                $('#recordsTable tbody tr').each(function () {
                    const row = $(this);
                    const id = row.find('td:eq(0)').text(); // Id
                    const date = row.find('td:eq(1)').text();
                    const vendor = row.find('td:eq(2)').text();
                    const category = row.find('td:eq(3)').text();
                    const type = row.find('td:eq(4)').text();
                    const total = row.find('td:eq(5)').text();

                    updatedData.push({ id, date, vendor, category, type, total });
                });

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: { updatedData: JSON.stringify(updatedData) },
                    success: function (response) {
                        alert(response);
                    }
                });
            });
        });

        function toggleNewClientInput() {
            var clientDropdown = document.getElementById('client');
            var newClientInput = document.getElementById('new_client');

            if (clientDropdown.value === 'add_client') {
                newClientInput.style.display = 'block';
            } else {
                newClientInput.style.display = 'none';
            }
        }

        document.querySelector('.scan-btn').addEventListener('click', () => {
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
    </script>

</body>
</html>
