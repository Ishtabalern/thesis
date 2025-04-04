<?php
session_start();
$servername = "localhost";
$db_username = "admin";
$db_password = "123";
$dbname = "cskdb";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['login_type'] === "admin") {
        $admin_username = $_POST['username'];
        $admin_password = $_POST['password'];

        $stmt = $conn->prepare("SELECT username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_username, $db_password);
            $stmt->fetch();

            if (password_verify($admin_password, $db_password)) {
                $_SESSION['username'] = $db_username;
                $_SESSION['logged_in'] = true;
                header("Location: admin/AdminHome.php");
                exit();
            } else $message = "Invalid admin password.";
        } else $message = "Admin username not found.";

        $stmt->close();
    } elseif ($_POST['login_type'] === "employee") {
        $employee_id = $_POST['employee_id'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT username, password FROM user WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_username, $db_password);
            $stmt->fetch();

            if (password_verify($password, $db_password)) {
                $_SESSION['employee_id'] = $employee_id;
                $_SESSION['username'] = $db_username;
                $_SESSION['logged_in'] = true;
                header("Location: dashboard.php");
                exit();
            } else $message = "Invalid employee password.";
        } else $message = "Employee ID not found.";

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link rel="stylesheet" href="styles/sign-in.css">
</head>
<body class="admin-mode">
<img src="./imgs/csk_logo.png" alt="" class="logo">
<img src="./imgs/csk_logo.png" alt="" class="logo active">

<div class="switch-container">
  <label class="switch">
    <input type="checkbox" id="toggleLogin">
    <span class="slider"></span>
  </label>
  <span id="modeLabel" class="label">Admin Mode</span>
</div>

<?php if (!empty($message)): ?>
  <p class="error"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="center-container">
    <!-- Admin Login Form -->
    <div id="adminForm" class="login-form active">
    <form method="POST" class="form">
        <div class="title active">Welcome,<br><span>please sign in to continue</span></div>
        <input type="hidden" name="login_type" value="admin">
        <input class="input" type="text" name="username" placeholder="Admin Username" required><br>
        <input class="input" type="password" name="password" placeholder="Password" required><br>
        <button type="submit" class="button-confirm">Login as Admin</button>
    </form>
    </div>

    <!-- Employee Login Form -->
    <div id="employeeForm" class="login-form">
    <form method="POST" class="form">
        <div class="title">Welcome,<br><span>please sign in to continue</span></div>
        <input type="hidden" name="login_type" value="employee">
        <input class="input" type="text" name="employee_id" placeholder="Employee ID" required><br>
        <input class="input" type="password" name="password" placeholder="Password" required><br>
        <button type="submit" class="button-confirm">Login as Employee</button>
    </form>
    </div>
</div>

<script>
  const toggle = document.getElementById('toggleLogin');
  const adminForm = document.getElementById('adminForm');
  const employeeForm = document.getElementById('employeeForm');
  const label = document.getElementById('modeLabel');
  const body = document.body;

  function switchToEmployee() {
    adminForm.classList.remove('active');
    employeeForm.classList.add('active');
    body.classList.remove('admin-mode');
    body.classList.add('employee-mode');
    label.textContent = "Employee Mode";
  }

  function switchToAdmin() {
    employeeForm.classList.remove('active');
    adminForm.classList.add('active');
    body.classList.remove('employee-mode');
    body.classList.add('admin-mode');
    label.textContent = "Admin Mode";
  }

  toggle.addEventListener('change', () => {
    if (toggle.checked) {
      switchToEmployee();
    } else {
      switchToAdmin();
    }
  });

  // Optional: make sure correct mode is set on page reload
  window.onload = () => {
    if (toggle.checked) {
      switchToEmployee();
    } else {
      switchToAdmin();
    }
  };
</script>
</body>
</html>
