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
</section>

<?php $conn->close(); ?>
