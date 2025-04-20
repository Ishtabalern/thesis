<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Fetch clients
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$selected_client = $_GET['client_id'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');

// Fetch clients
$client_stmt = $pdo->query("SELECT id, name FROM clients");
$clients = $client_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Net Income
$net_income = 0;
if ($selected_client && $selected_year) {
    $stmt = $pdo->prepare("SELECT net_income FROM income_statements WHERE client_id = ? AND YEAR(statement_date) = ?");
    $stmt->execute([$selected_client, $selected_year]);
    $net_income = $stmt->fetchColumn() ?: 0;
}

// Fetch Owner's Equity (Investment & Withdrawals)
$investment = $withdrawal = 0;
if ($selected_client && $selected_year) {
    $stmt = $pdo->prepare("SELECT additional_investment, withdrawals FROM owners_equity WHERE client_id = ? AND year = ?");
    $stmt->execute([$selected_client, $selected_year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $investment = $row['additional_investment'] ?? 0;
        $withdrawal = $row['withdrawals'] ?? 0;
    }
}

$cash_from_operating = $net_income;
$cash_from_financing = $investment - $withdrawal;
$net_cash_flow = $cash_from_operating + $cash_from_financing;
?>


<!DOCTYPE html>
<html>
<head>
    <title>Cash Flow Statement</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/cash_flow.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>

        <div class="modal-overlay"></div>
        <!-- The Modal -->
        <div id="newModal" class="newModal">
            <!-- Modal content -->
            <div class="modal-content">
                <span class="close"></span>
                <div class="flyoutColumn">
                    <h3>Customer</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Invoice</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Receive payment</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Statement</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Estimate</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Credit note</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Sales receipt</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Refund receipt</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Delayed credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Delayed charge</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add customer</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Suppliers</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Expense</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Cheque</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Bill</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Pay bills</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Purchase order</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Supply credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Credit card credit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add supplier</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Team</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Single time activity</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Weekly timesheet</a></li>
                    </ul>
                </div>
                <div class="flyoutColumn">
                <h3>Other</h3>
                    <ul>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Bank deposit</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Transfer</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Journal entry</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Pay down credit card</a></li>
                        <li><a class="btn-tabs" href="dashboard.php" class="active">Add product/service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    
    <div class="sidebar">
        <div class="company-logo">
            <img src="./imgs/csk_logo.png" alt="">
        </div>
        <div class="btn-container">
            <button id="myBtn" class="modalBtn"><i class="fa-solid fa-plus"></i>New</button>
            <a class="btn-tabs" href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
            <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-wallet"></i>Record Expense</a>
            <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
            <a class="btn-tabs" href="reports.php"><i class="fa-solid fa-file"></i>Reports</a>
            <a class="btn-tabs" href="balance_sheet.php"><i class="fa-solid fa-file"></i>Balance Sheet</a>
            <a class="btn-tabs" href="income_statement.php"><i class="fa-solid fa-file"></i>Income Statement</a>
            <a class="btn-tabs" href="auto_income_statement.php"><i class="fa-solid fa-file"></i>Income Statement (auto)</a>
            <a class="btn-tabs" href="owners_equity.php"><i class="fa-solid fa-file"></i>Owner's Equity</a>
            <a class="btn-tabs" href="trial_balance.php"><i class="fa-solid fa-file"></i>Trial Balance</a>
            <a class="btn-tabs" href="cash_flow.php"><i class="fa-solid fa-file"></i>Cash Flow</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Cash Flow Statement</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <div class="cash-flow-box">
            <h2>Cash Flow Statement - <?= htmlspecialchars($selected_year) ?></h2>

            <form method="GET">
                <label>Client:</label>
                <select name="client_id" onchange="this.form.submit()">
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $row): ?>
                        <option value="<?= $row['id'] ?>" <?= ($selected_client == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label style="margin-left: 20px;">Year:</label>
                <input type="number" name="year" value="<?= htmlspecialchars($selected_year) ?>" onchange="this.form.submit()" />
            </form>

            <?php if ($selected_client && $selected_year): ?>
            <table>
                <tr><td colspan="2" class="label">Cash Flows from Operating Activities</td></tr>
                <tr>
                    <td>Net Income</td>
                    <td class="amount">$<?= number_format($net_income, 2) ?></td>
                </tr>
                <tr><td colspan="2" class="label">Cash Flows from Financing Activities</td></tr>
                <tr>
                    <td>Owner Investment</td>
                    <td class="amount">$<?= number_format($investment, 2) ?></td>
                </tr>
                <tr>
                    <td>Owner Withdrawals</td>
                    <td class="amount">($<?= number_format($withdrawal, 2) ?>)</td>
                </tr>
                <tr class="label">
                    <td>Net Cash Flow</td>
                    <td class="amount">$<?= number_format($net_cash_flow, 2) ?></td>
                </tr>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <script src="script/dashboard.js"></script>

</body>
</html>
