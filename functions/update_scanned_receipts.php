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

// Check if updatedData is received
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updatedData'])) {
    $updatedData = json_decode($_POST['updatedData'], true);

    foreach ($updatedData as $row) {
        $id = $row['id'];
        $date = $row['date'];
        $vendor = $row['vendor'];
        $category = $row['category'];
        $type = $row['type'];
        $total = $row['total'];

        // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE scanned_receipts SET date = ?, vendor = ?, category = ?, type = ?, total = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $date, $vendor, $category, $type, $total, $id);
        $stmt->execute();
        $stmt->close();
    }

    echo "Data updated successfully!";
} else {
    echo "No data received.";
}

$conn->close();
?>
