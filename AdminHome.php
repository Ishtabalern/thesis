<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSK - Admin Home</title>
    <link rel="stylesheet" href="styles/admin-home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <button class="btn-tabs" data-tab="manage-users"><i class="fa-solid fa-users-gear"></i> Manage Users</button>
            <button class="btn-tabs" data-tab="system-logs"><i class="fa-solid fa-file-circle-check"></i> Audit Logs</button>
            <button class="btn-tabs" data-tab="settings"><i class="fa-solid fa-gear"></i> Settings</button>
        </div>
    </section>

    <main class="content">
        <section id="home" class="tab-content">
            <?php include('F:/xampp/htdocs/thesis/dashboard.php'); ?>
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
                    <a href="logout.php"><button style="background-color: #BB2727; font-weight: bold; color: #fff;">Log out</button></a>
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

    <script src="script/admin.js"></script>
</body>
</html>

<?php $conn->close(); ?>
