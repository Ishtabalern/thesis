<?php
$servername = "localhost";
$username = "admin";
$password = "123";
$dbname = "cskdb";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Check if the request is POST and new_client is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_client'])) {
    $newClient = trim($_POST['new_client']);

    if (!empty($newClient)) {
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
        $stmt->bind_param("s", $newClient);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "client_id" => $stmt->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to add client."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Client name cannot be empty."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

// Close connection
$conn->close();
?>
