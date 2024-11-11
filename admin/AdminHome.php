<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cskdb";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle updating employee details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $employee_id = $_POST['employee_id'];
    $username = $_POST['username'];
    $new_password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Update query
    if ($new_password) {
        $stmt = $conn->prepare("UPDATE user SET username = ?, password = ? WHERE employee_id = ?");
        $stmt->bind_param("ssi", $username, $new_password, $employee_id);
    } else {
        $stmt = $conn->prepare("UPDATE user SET username = ? WHERE employee_id = ?");
        $stmt->bind_param("si", $username, $employee_id);
    }
    
    if ($stmt->execute()) {
        $message = "Employee details updated successfully.";
    } else {
        $message = "Error updating employee: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch all employees
$employees = $conn->query("SELECT id, username, employee_id FROM user");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSK - Admin Home</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <section class="sidebar">
        <h1>CSK - Admin</h1>
        <div class="btn-container">
            <button class="btn-tabs" data-tab="home"><i class="fa-solid fa-house"></i> Home</button>
            <button class="btn-tabs" data-tab="manage-users"><i class="fa-solid fa-users-gear"></i> Manage Users</button>
            <button class="btn-tabs" data-tab="system-logs"><i class="fa-solid fa-file-circle-check"></i> System Logs</button>
            <button class="btn-tabs" data-tab="settings"><i class="fa-solid fa-gear"></i> Settings</button>
        </div>
    </section>

    <main class="content">
        <section id="home" class="tab-content">
            <header class="header">
                <h2>Home</h2>
                <div class="buttons">
                    <button style="background-color: #BB2727; font-weight: bold; color: #fff;">Log out</button>
                    <button>Admin</button>
                </div>
            </header>

            <section class="overall-data">
                <div>
                    <p>Total Expenses</p>
                    <h1>23</h1>
                </div>
                <div>
                    <p>Latest Income</p>
                    <h1>23</h1>
                </div>
            </section>

            <section class="transaction-history">
                <h3>Transaction History</h3>
                <div class="home-header">
                    <span>Id</span>
                    <span>Date</span>
                    <span>Customer Name</span>
                    <span>Item</span>
                    <span>Price</span>
                </div>
                <div class="home-data-container">
                    <div class="home-data">
                        <span>1</span>
                        <span>Oct 24, 2024</span>
                        <span>CVSU - Bacoor</span>
                        <span>Fan</span>
                        <span>₱45</span>
                    </div>
                </div>
            </section>
        </section>

        <section id="manage-users" class="tab-content">
            <header class="header">
                <h2>Manage Users</h2>
                <div class="buttons">
                    <button style="background-color: #BB2727; font-weight: bold; color: #fff;">Log out</button>
                    <button>Admin</button>
                </div>
            </header>

            <section class="manage-users-content">
                <div class="accounts">
                    <div class="active-account">
                        <input type="radio" name="account" data-account="account">
                        <span>Account</span>
                    </div>
                    <div class="active-account">
                        <input type="radio" name="account" data-account="pending">
                        <span>Pending</span>
                    </div>
                    <div class="right-side">
                        <a href="signup.php"><button class="create">Create Account</button></a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <table id="account" class="account-table active">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($employee = $employees->fetch_assoc()): ?>
                            <tr>
                                <form action="" method="POST">
                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                    <td>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="password" name="password" placeholder="New Password (leave blank if unchanged)">
                                    </td>
                                    <td>
                                        <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                                        <button type="submit" name="update">Update</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <table id="pending" class="account-table">
                    <!-- Pending Account Table Here -->
                </table>
            </section>
        </section>

        <section id="system-logs" class="tab-content">
            <h2>System Logs</h2>
            <p>View system logs and activity history.</p>
        </section>

        <section id="settings" class="tab-content">
            <h2>Settings</h2>
            <p>Configure system settings here.</p>
        </section>
    </main>

    <script src="admin.js"></script>
</body>
</html>

<?php $conn->close(); ?>
