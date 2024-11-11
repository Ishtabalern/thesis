<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Financial Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/records.css">
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <h2>CSK - (LOGO)</h2>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-house"></i>Home</a>
        <a href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
        <a href="records.php" class="active"><i class="fa-solid fa-file"></i>Financial Records</a>
        <a href="#"><i class="fa-solid fa-file-export"></i>Generate Report</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Financial Records</h1>
            <div class="user-controls">
                <button class="logout-btn">Log out</button>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="category-buttons">
                <button class="btn add-category-btn">Add Category</button>
                <button class="btn delete-category-btn">Delete Category</button>
            </div>

            <div class="record-summary">
                <button class="summary-btn">All Receipt<br>20</button>
                <button class="summary-btn">Sales<br>5</button>
                <button class="summary-btn">Expense<br>15</button>
                <input type="text" class="search-bar" placeholder="Search">
            </div>

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Oct 24, 2024</td>
                            <td>CVSU - Bacoor</td>
                            <td>Fan</td>
                            <td>Sales</td>
                            <td>₱45</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Oct 24, 2024</td>
                            <td>CVSU - Imus</td>
                            <td>Fan</td>
                            <td>Expense</td>
                            <td>₱45</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Oct 24, 2024</td>
                            <td>CVSU - Indang</td>
                            <td>Fan</td>
                            <td>Sales</td>
                            <td>₱45</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
