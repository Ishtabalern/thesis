<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "admin";
$password = "123";
$dbname = "cskdb";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle updating employee details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $employee_id = $_POST['employee_id'];
    $new_password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Update query: Only update if a new password is provided
    if ($new_password) {
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE employee_id = ?");
        $stmt->bind_param("si", $new_password, $employee_id);

        if ($stmt->execute()) {
            $message = "Password updated successfully.";
        } else {
            $message = "Error updating password: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "No changes made. Password field was empty.";
    }
}

if (isset($_GET['id'])) {
   $request_id = $_GET['id'];

   // Prepare SQL query to delete the reset request
   $stmt = $conn->prepare("DELETE FROM password_reset_requests WHERE id = ?");
   $stmt->bind_param("i", $request_id);

   // Execute the query
   if ($stmt->execute()) {
       // Successful deletion, notify the admin
       $message = "Request deleted successfully.";
   } else {
       // Handle error if the query fails
       $message = "Error: Could not delete the request.";
   }

   $stmt->close();
}

// Fetch all employees
$employees = $conn->query("SELECT id, username, employee_id, created_at FROM user");

// Fetch pending password reset requests
$sql = "SELECT r.id, r.employee_id, e.username, r.request_date FROM password_reset_requests r JOIN user e ON r.employee_id = e.employee_id WHERE r.status = 'pending'";
$result = $conn->query($sql);


//------------ financial records
  //  - Fetch the count of receipts
  $receipt_count = 0;
  $result = $conn->query("SELECT COUNT(*) AS total FROM receipts");
  if ($result && $row = $result->fetch_assoc()) {
      $receipt_count = $row['total'];
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

          $stmt = $conn->prepare("UPDATE receipts SET date = ?, vendor = ?, category = ?, type = ?, total = ? WHERE id = ?");
          $stmt->bind_param("ssssdi", $date, $vendor, $category, $type, $total, $id);
          $stmt->execute();
          $stmt->close();
      }

      echo "Data updated successfully!";
      exit();
  }
//------------ generate report

// Fetch sales and expense data grouped by date
$query = "
    SELECT date, 
           SUM(CASE WHEN type = 'Sales' THEN total ELSE 0 END) AS totalSales,
           SUM(CASE WHEN type = 'Expense' THEN total ELSE 0 END) AS totalExpenses
    FROM receipts 
    GROUP BY date 
    ORDER BY date
";
$result = $conn->query($query);

$dates = [];
$sales = [];
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date'];
    $sales[] = $row['totalSales'] ?? 0;
    $expenses[] = $row['totalExpenses'] ?? 0;
}

// Fetch records for "Sales" and "Expenses"
$salesRecords = $conn->query("SELECT date, vendor, total FROM receipts WHERE type = 'Sales'");
$expenseRecords = $conn->query("SELECT date, vendor, total FROM receipts WHERE type = 'Expense'");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSK - Admin Home</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <section class="sidebar">
        <div class="company-logo">
            <img src="../imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <button class="btn-tabs" data-tab="home"><i class="fa-solid fa-house"></i> Home</button>
            <button class="btn-tabs" data-tab="capture-documents"><i class="fa-solid fa-camera"></i>Capture Documents</button>
            <button class="btn-tabs" data-tab="financial-records"><i class="fa-solid fa-file"></i>Financial Records</button>
            <button class="btn-tabs" data-tab="generate-report"><i class="fa-solid fa-file-export"></i>Generate Report</button>
            <button class="btn-tabs" data-tab="client"><i class="fa-solid fa-file-export"></i>Client</button>
            <button class="btn-tabs" data-tab="manage-users"><i class="fa-solid fa-users-gear"></i> Manage Users</button>
            <button class="btn-tabs" data-tab="system-logs"><i class="fa-solid fa-file-circle-check"></i> Audit Logs</button>
            <button class="btn-tabs" data-tab="settings"><i class="fa-solid fa-gear"></i> Settings</button>
        </div>
    </section>

    <main class="content">
        <section id="home" class="tab-content">
<<<<<<< Updated upstream
        <div class="dashboard">
        <div class="top-bar">
            <h1>Home</h1>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2> <!-- Display employee's username -->
            <div class="user-controls">
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">Log out</button>
            </form>
            </div>
        </div>
=======
            <div class="dashboard">
                <div class="top-bar">
                    <h1>Home</h1>
                    <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2> <!-- Display employee's username -->
                    <div class="user-controls">
                        <a href="logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                    </div>
                </div>
>>>>>>> Stashed changes

                <div class="subcontainer">
                    <div class="report-card">
                        <h2>Total Expenses</h2>
                        <h3>₱ 69</h3>
                    </div>
                    <div class="report-card">
                        <h2>Latest Income</h2>
                        <h3>₱ 69</h3>
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
        </section>
        
        <section id="capture-documents" class="tab-content">
            <header class="header">
                <h2>Capture Documents</h2>
                <div class="buttons">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
                    <button>Admin</button>
                </div>
            </header>

            <div class="scan-content">
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
        </section>

        <section id="financial-records" class="tab-content">
            <header class="header">
                <h2>Financial Records</h2>
                <div class="buttons">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
                    <button>Admin</button>
                </div>
            </header>

            <div class="records-content">
                <div class="record-summary">
                    <button class="summary-btn">All Receipt<br><?php echo $receipt_count; ?></button>
                    <button class="summary-btn">Sales<br>5</button>
                    <button class="summary-btn">Expense<br>15</button>
                </div>

                <div class="data-table">
                    <table id="recordsTable">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Receipt</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, date, vendor, category, type, total FROM receipts";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                    echo "<td contenteditable='true' data-column='date'>" . htmlspecialchars($row["date"]) . "</td>";
                                    echo "<td contenteditable='true' data-column='vendor'>" . htmlspecialchars($row["vendor"]) . "</td>";
                                    echo "<td contenteditable='true' data-column='category'>" . htmlspecialchars($row["category"]) . "</td>";
                                    echo "<td contenteditable='true' data-column='type'>" . htmlspecialchars($row["type"]) . "</td>";
                                    echo "<td><button onclick='showModal()'>View</button></td>";
                                    echo "<td contenteditable='true' data-column='total'>" . htmlspecialchars($row["total"]) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <button id="saveChanges" class="btn save-btn">Save Changes</button>
                    
                    <div class="reciept-modal" id="modal">
                        <section class="modal-content">
                            <h2>Reciept</h2>
                            <div class="reciept-container">
                                Reciept Here
                            </div>
                        </section>
                    </div>
                   
                </div>
            </div>

            
        </section>

        <section id="generate-report" class="tab-content">
            <header class="header">
                <h2>Generate Report</h2>
                <div class="buttons">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
                    <button>Admin</button>
                </div>
            </header>
            

            <section class="client-dropdown-section">
                <div class="dropdown-client-category">
                    <button class="buttons"><span class="label-client">Client</span> <span>▼</span></button>
                    <div class="dropdown-content">
                    <button class="btn-client" data-id="client-one">7/11 - Salawag Branch</button>
                    <button class="btn-client" data-id="client-two">7/11 - Paliparan Branch</button>
                    </div>     
                </div>
            </section>
            
            <section class="generate-report-content">
                <div class="generate-report-header">Monthly Financial Report</div>

                <section class="chart-section">
                    <div class="generate-report-header">Sales vs Expenses Chart</div>
                    <canvas id="salesExpensesChart" width="400" height="200"></canvas>
                </section>

         
                <div class="overall">
                    <div class="client-monthly">
                    <span>Client Balance (Last Month)</span>
                    <span>₱ 20</span>
                    </div>
                    <div class="client-monthly">
                    <span>Monthly Balance</span>
                    <span>₱ 20000</span>
                    </div>
                </div>

                <div class="tables-container">
                    <div class="table" style="border-right: 1px solid #919191;">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($salesRecords->num_rows > 0) {
                                    $index = 1;
                                    while ($row = $salesRecords->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>{$index}.</td>";
                                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['vendor']) . "</td>";
                                        echo "<td>₱" . htmlspecialchars($row['total']) . "</td>";
                                        echo "</tr>";
                                        $index++;
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No sales records found</td></tr>";
                                }
                                ?>
                            </tbody>                
                        </table>
                    </div>

                    <div class="table">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($expenseRecords->num_rows > 0) {
                                    $index = 1;
                                    while ($row = $expenseRecords->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>{$index}.</td>";
                                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['vendor']) . "</td>";
                                        echo "<td>₱" . htmlspecialchars($row['total']) . "</td>";
                                        echo "</tr>";
                                        $index++;
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No expense records found</td></tr>";
                                }
                                ?>
                            </tbody>                
                        </table>
                    </div>
                </div>
            </section>     
        </section>

        <section id="client" class="tab-content">

            <header class="header">
                <h2>Client</h2>
                <div class="buttons">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
                    <button>Admin</button>
                </div> 
            </header>

            <div class="add-client">
                <button>Add Client</button>
            </div>

            <div class="client-container">
                <div class="clients">
                    <div class="header-search">
                    <h3>Company</h3>
                    <div class="search-bar">
                        <input type="text" placeholder="Search" />
                    </div>
                    </div>
                    
                    <ol>
                    <li><a href="company.html">7 - Eleven</a></li>
                    </ol>
                </div>

                <div class="clients">
                    <div class="header-search">
                    <h3>Independent</h3>
                    <div class="search-bar">
                        <input type="text" placeholder="Search" />
                    </div>
                    </div>
                    
                    <ol>
                    <li><a href="independent.html">Jan D</a></li>
                    </ol>
                </div>
            </div>

            <!-- Company View -->
            <div class="company-view" style="display:none;">
                <div class="forms-container">
                    <div class="company-buttons">
                        
                        <div class="client-company">
                            <h3>Company</h3>
                            <p id="client-name">7 - Eleven</p>
                            <button class="back-btn" onclick="goBack()">Back</button>
                        </div>
                       
                        <div class="forms-btns">
                            <button onclick="openTab(event, 'Trial Balance')">Trial Balance</button>
                            <button onclick="openTab(event, 'Income Statement')">Income Statement</button>
                            <button onclick="openTab(event, 'Owner\'s Equity')">Owner's Equity</button>
                            <button onclick="openTab(event, 'Balance Sheet')">Balance Sheet</button>
                            <button onclick="openTab(event, 'Statement of Cash Flow')">Statement of Cash Flow</button>
                        </div>
            
                    </div>
                    
                    <div class="company-contents">
                         <h3 id="tab-content">Click a tab to see the content here.</h3>
                    </div>
                </div>     
            </div>

        </section>

        <section id="manage-users" class="tab-content">         
            <header class="header">
                <h2>Manage Users</h2>
                <div class="buttons">
                    <a href="logout.php"><button style="background-color: #BB2727; font-weight: bold; color: #fff;">Log out</button></a>
                    <button>Admin</button>
                </div>
            </header>

            <section class="manage-users-content">
                <div class="accounts">
                    <div class="account-request-create-section">
                        <div class="active-account" style="border-right:1px solid #2323;">
                            <h4>Account</h4>
                        </div>
                        <div class="active-account">
                            <h4>Request</h4>
                        </div>
                        <div class="create-account">
                            <a href="signup.php"><button class="create">Create Account</button></a>
                        </div>
                    </div>

                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search Username">
                    </div>
                </div>

                

                <table id="account" class="account-table active">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees->fetch_assoc()): ?>
                            <tr>
                                <td>1</td>
                                <form action="" method="POST">
                                    <td>
                                        <?php echo htmlspecialchars($employee['username']); ?>
                                        </p>
                                        <p style="color:#B8B8B8; font-size:12px; margin-top:10px">
                                            <?php echo htmlspecialchars($employee['employee_id']); ?>
                                        </p>
                                    </td>

                                    <td>
                                        <input class="password" type="password" name="password" placeholder="New Password (leave blank if unchanged)">
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($employee['created_at']); ?>
                                    </td>
                                    <td>
                                        <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                                        <button type="submit" name="update" >Update</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
            <!-- Password reset requests section -->
            <section class="password-reset-requests">
                    <h2>Password Reset Requests</h2>
                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Employee ID</th>
                                <th>Username</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                                    <td>
                                        <a href="AdminHome.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this request?');">Delete Request</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>No password reset requests at the moment.</p>
                    <?php endif; ?>

                    <?php if (isset($message)): ?>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                </section>
        </section>

        <section id="system-logs" class="tab-content">
            <header class="header">
                <h2>Audit Logs</h2>
                <div class="buttons">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
                    <button>Admin</button>
                </div>
            </header>

            <section class="transaction-history">
                <div class="documents-captured-container">
                    <h3>Documents Captured</h3>

                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search Username" id="search-bar">
                    </div>
                </div>
                
                <table id="account" class="documents-captured-table active">
                    <thead class="table-header">
                        <tr>
                            <th></th>
                            <th>Timestamp</th>
                            <th>Name</th>
                            <th>Action</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody class="table-data">
                        <tr>
                            <td>1</td>
                            <td>13:43 PM, 11/11/24</td>
                            <td>
                                <div class="data-employee-name">
                                    <p>Jan dela Cruz</p>
                                    <p style="font-size:11px; color:#7a7a7a">Employee</p>
                                </div>                                  
                            </td>
                            <td>Scanned a Document</td>
                            <td>Expense</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>14:15 PM, 11/11/24</td>
                            <td>
                                <div class="data-employee-name">
                                    <p>Maria Santos</p>
                                    <p style="font-size:11px; color:#7a7a7a">Manager</p>
                                </div>                                  
                            </td>
                            <td>Approved a Request</td>
                            <td>Approval</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>09:22 AM, 11/12/24</td>
                            <td>
                                <div class="data-employee-name">
                                    <p>Juan dela Cruz</p>
                                    <p style="font-size:11px; color:#7a7a7a">Employee</p>
                                </div>                                  
                            </td>
                            <td>Updated a Record</td>
                            <td>Update</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </section>


        <!-- Other sections here... -->

    </main>

    
    <!-- client tablink -->
    <script>
        function openTab(event, tabName) {
        const content = document.getElementById('tab-content');
        content.textContent = `You have selected: ${tabName}`;
        }

        function goBack() {
        alert('Going back!');
        }
    </script>

    <!-- Client Script -->
    <script>
        function goBack() {
        document.querySelector('.company-view').style.display = 'none';
        document.querySelector('.client-container').style.display = 'flex';
        }

        document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            document.querySelector('.client-container').style.display = 'none';
            document.querySelector('.company-view').style.display = 'block';
            document.getElementById('client-name').innerText = this.textContent;
        });
        });
    </script>

    <!-- Modal-->  

    <script>

     var modal = document.getElementById("reciept-modal");

       function showModal() {
        document.getElementById("modal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("modal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        };
    </script>

    <script src="admin.js"></script>
    
    <!-- Financial Records modal-->                    
        
    <!-- Include jQuery -->
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
    </script>      


     <!-- generate report JS -->
    <script>
        // Pass PHP data to JavaScript
        const labels = <?php echo json_encode($dates); ?>; // Dates from PHP
        const salesData = <?php echo json_encode($sales); ?>; // Sales data
        const expenseData = <?php echo json_encode($expenses); ?>; // Expenses data

        // Create a Chart.js line chart
        const ctx = document.getElementById('salesExpensesChart').getContext('2d');
        const salesExpensesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels, // Dates
                datasets: [
                    {
                    label: 'Sales (₱)',
                    data: salesData, // Sales amounts
                    borderColor: 'rgba(54, 162, 235, 1)', // Blue line
                    backgroundColor: 'rgba(54, 162, 235, 0.1)', // Light blue area
                    tension: 0.3,
                    fill: true
                    },
                    {
                    label: 'Expenses (₱)',
                    data: expenseData, // Expense amounts
                    borderColor: 'rgba(255, 99, 132, 1)', // Red line
                    backgroundColor: 'rgba(255, 99, 132, 0.1)', // Light red area
                    tension: 0.3,
                    fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                    position: 'top',
                    }
                },
                scales: {
                    x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                    },
                    y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                    }
                }
            }
        });
    </script>

    
</body>
</html>

<?php $conn->close(); ?>
