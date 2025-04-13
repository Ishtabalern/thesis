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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/sign-security.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Settings</h1>
            <h2>Hello, <?php echo htmlspecialchars($username); ?></h2> <!-- Display employee's username -->
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>      
        </div>

         <div class="settings-container">
            <div class="headLogo-container">
                <i class="fa-solid fa-lock"></i>
                <h3>Sign in security</h3>
                <p>We'll use this info to help make sure only you can sign in to your account</p>
            </div>


            <div class="personal-infos-container">
                <div class="infos">
                    <h3>User ID</h3>
                    <button class="edit-btn" data-target="user-id">Edit</button>
                </div>

                <div class="infos">
                    <h3>Email address</h3>
                    <button class="edit-btn" data-target="email">Edit</button>
                </div>

                <div class="infos">
                    <h3>Password</h3>
                    <button class="edit-btn" data-target="password">Edit</button>
                </div>

                <div class="infos">
                    <h3>Phone number</h3>
                    <button class="edit-btn" data-target="phone">Edit</button>
                </div>
            </div>

            <div class="back-btn">
                <a href="settings.php" >Back</a>
            </div>
       
         </div>

         <div id="changeDetailModal" class="modal">
            <div class="modal-content">
                <h3>Change user id</h3>
                <form action="">
                    <div class="edit-form">
                        <label for="current">Current user id</label>
                        <input type="text" id="current" name="current" required>
                    </div>

                    <div class="edit-form">
                        <label for="type-new">Type new user id</label>
                        <input type="text" id="type-new" name="type-new" required>                                                    
                    </div>

                    <div class="edit-form">
                        <label for="re-type">Re-type new user id</label>
                        <input type="text" id="re-type" name="re-type" required>
                    </div>
                    
                    <div class="form-btns">
                        <button class="submit">Submit</button>
                        <button type="button" class="back close-modal">Back</button>
                    </div>  
                </form>
            </div>
            
         </div>

        <!-- Email Modal -->
        <div id="modal-email" class="modal">
            <div class="modal-content">
                <h3>Change Email Address</h3>
                <form>
                    <div class="edit-form">
                        <label for="current-email">Current Email</label>
                        <input type="email" id="current-email" required>
                    </div>
                    <div class="edit-form">
                        <label for="new-email">New Email</label>
                        <input type="email" id="new-email" required>
                    </div>
                    <div class="edit-form">
                        <label for="confirm-email">Confirm New Email</label>
                        <input type="email" id="confirm-email" required>
                    </div>
                    <div class="form-btns">
                        <button class="submit">Submit</button>
                        <button type="button" class="back close-modal">Back</button>
                    </div>
                </form>
            </div>
        </div>

          <!-- Password Modal -->
          <div id="modal-password" class="modal">
            <div class="modal-content">
                <h3>Change Password</h3>
                <form>
                    <div class="edit-form">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" required>
                    </div>
                    <div class="edit-form">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" required>
                    </div>
                    <div class="edit-form">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" required>
                    </div>
                    <div class="form-btns">
                        <button class="submit">Submit</button>
                        <button type="button" class="back close-modal">Back</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Phone Modal -->
        <div id="modal-phone" class="modal">
            <div class="modal-content">
                <h3>Change Phone Number</h3>
                <form>
                    <div class="edit-form">
                        <label for="current-phone">Current Phone Number</label>
                        <input type="tel" id="current-phone" required>
                    </div>
                    <div class="edit-form">
                        <label for="new-phone">New Phone Number</label>
                        <input type="tel" id="new-phone" required>
                    </div>
                    <div class="edit-form">
                        <label for="confirm-phone">Confirm Phone Number</label>
                        <input type="tel" id="confirm-phone" required>
                    </div>
                    <div class="form-btns">
                        <button class="submit">Submit</button>
                        <button type="button" class="back close-modal">Back</button>
                    </div>
                </form>
            </div>
        </div>

      
    </div>

    <script>
        // Modern logic for opening modals
        const editButtons = document.querySelectorAll(".edit-btn");
        const modals = {
            "user-id": document.getElementById("changeDetailModal"),
            "email": document.getElementById("modal-email"),
            "password": document.getElementById("modal-password"),
            "phone": document.getElementById("modal-phone")
        };

        editButtons.forEach(button => {
            button.addEventListener("click", () => {
                const target = button.dataset.target;
                if (modals[target]) {
                    modals[target].style.display = "block";
                }
            });
        });

        // Close modals on 'Back' button or outside click
        const closeButtons = document.querySelectorAll(".close-modal");

        closeButtons.forEach(button => {
            button.addEventListener("click", () => {
                button.closest(".modal").style.display = "none";
                clearInputs(button.closest(".modal"));
            });
        });

        window.onclick = function(event) {
            document.querySelectorAll(".modal").forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                    clearInputs(modal);
                }
            });
        };

        function clearInputs(modal) {
            const inputs = modal.querySelectorAll("input");
            inputs.forEach(input => input.value = "");
        }
    </script>

</body>
</html>
