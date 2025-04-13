<?php
$servername = "localhost";
$username = "admin";
$password = "123";
$dbname = "cskdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['client'])) {
    $selectedClient = $_POST['client'];

    if (!is_numeric($selectedClient) || empty($selectedClient)) {
        echo json_encode(["success" => false, "message" => "Invalid client selected."]);
        exit();
    }

    $sql = "INSERT INTO receipts (date, vendor, category, type, total, img_url, client_id) 
            SELECT date, vendor, category, type, total, img_url, ? 
            FROM scanned_receipts";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selectedClient);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->query("DELETE FROM scanned_receipts"); // Clear scanned receipts table
        echo json_encode(["success" => true, "message" => "Report generated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: No data moved."]);
    }

    $stmt->close();
    exit();
}

$conn->close();
?>
