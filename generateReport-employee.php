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
   <title>Generate Report</title>
   <link rel="stylesheet" href="styles/generateReport-employee.css">
   <link rel="stylesheet" href="styles/sidebar.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

   <main class="dashboard">
      <div class="top-bar">
          <h1>Generate Report</h1>
          <div class="user-controls"> 
              <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
              <div class="dropdown">
                  <button class="dropbtn">Employee ▼</button>
              </div>
          </div>
      </div>

      <section class="client-dropdown-section">
         <div class="dropdown-client-category">
            <button class="buttons"><span class="label-client">Client</span> <span>▼</span></button>
            <div class="dropdown-content">
               <button class="btn-client" data-id="client-one">7/11 - Salawag Branch</button>
               <button class="btn-client" data-id="client-two">7/11 - Paliparan Branch</button>
            </div>     
         </div>
      </section>

      <section class="client-content">
         <header>Monthly Financial Report</header>

         <section class="chart-section">
            <header>Sales vs Expenses Chart</header>
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
   </main>
   <script src="script/dashboard.js"></script>
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
