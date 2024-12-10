<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Capture Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/scan.css">
    <link rel="stylesheet" href="styles/sidebar.css">
    <style>
        /* Include the CSS below directly in your HTML for simplicity or link to an external CSS file */
    </style>
</head>
<body>
<div class="sidebar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <a class="btn-tabs" href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="#"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Capture Documents</h1>
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Scan and Scanner dropdown side by side -->
            <div class="scan-options">
                <button class="scan-btn">
                    <i class="fa-solid fa-qrcode"></i><br>
                    Scan
                </button>

                <div class="scanner-dropdown">
                    <select>
                        <option value="epson">Raspberry Pi 4</option>
                    </select>
                </div>
            </div>

            <!-- Receipts viewer -->
            <div class="receipt-card">
                <div class="document-placeholder">
                    <i class="fa-solid fa-plus"></i>
                    <p>Scanned Documents / Receipt Here</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.scan-btn').addEventListener('click', () => {
            fetch('http://raspberrypi:5000/run-script', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Script ran successfully:\n' + data.output);
                } else {
                    alert('Error running script:\n' + data.error);
                }
            })
            .catch(error => {
                alert('Failed to connect to the server:\n' + error);
            });
        });
    </script>

</body>
</html>
