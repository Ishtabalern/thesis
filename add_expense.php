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

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $vendor = $_POST['vendor'];
    $category = $_POST['category'];
    $type = $_POST['type'];
    $total = $_POST['total'];
    $img_url = $_POST['img_url'];

    $stmt = $conn->prepare("INSERT INTO scanned_receipts (date, vendor, category, type, total, img_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssds", $date, $vendor, $category, $type, $total, $img_url);

    if ($stmt->execute()) {
        echo "Receipt added successfully!";
    } else {
        echo "Error adding receipt.";
    }

    $stmt->close();
}
$conn->close();
?>
