<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Get client dropdown
$clients = $pdo->query("SELECT id, name FROM clients")->fetchAll(PDO::FETCH_ASSOC);

// Get selected client and date
$clientId = $_GET['client_id'] ?? ($_SESSION['client_id'] ?? 1);
$asOfDate = $_GET['as_of'] ?? date('Y-m-d');

// Query journal entry lines grouped by account
$sql = "
SELECT 
    a.name AS account_name,
    SUM(jel.debit) AS total_debit,
    SUM(jel.credit) AS total_credit
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN chart_of_accounts a ON jel.account_id = a.id
WHERE je.client_id = ? AND je.date <= ?
GROUP BY a.name
ORDER BY a.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$clientId, $asOfDate]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>🧾 Trial Balance</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 40px;
            background-color: #e3e6e9;
        }

        .receipt {
            width: 400px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border: 2px dashed #333;
        }

        .receipt h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt .line {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #ccc;
        }

        .receipt .bold {
            font-weight: bold;
        }

        .form-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-bar form {
            display: inline-block;
        }

        .totals {
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        button {
            margin-top: 10px;
        }
    </style>
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
            <h1>Trial Balance</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <div class="form-bar">
            <form method="GET">
                <label>Client:
                    <select name="client_id">
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $client['id'] == $clientId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>As of:
                    <input type="date" name="as_of" value="<?= htmlspecialchars($asOfDate) ?>">
                </label>

                <button type="submit">📄 View</button>
            </form>
        </div>

        <div class="receipt">
            <h2>📜 Trial Balance</h2>
            <p style="text-align:center">As of <?= htmlspecialchars($asOfDate) ?></p>
            <hr>

            <?php
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($entries as $entry):
                $debit = floatval($entry['total_debit']);
                $credit = floatval($entry['total_credit']);
                $totalDebit += $debit;
                $totalCredit += $credit;
            ?>
                <div class="line">
                    <span><?= htmlspecialchars($entry['account_name']) ?></span>
                    <span>
                        <?= $debit > 0 ? '₱' . number_format($debit, 2) : '' ?>
                        <?= $credit > 0 ? ' | ₱' . number_format($credit, 2) : '' ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="totals line bold">
                <span>Total</span>
                <span>₱<?= number_format($totalDebit, 2) ?> | ₱<?= number_format($totalCredit, 2) ?></span>
            </div>

            <p style="text-align:center; margin-top: 10px;">
                <?= $totalDebit == $totalCredit ? '✅ Balanced' : '⚠️ Not Balanced' ?>
            </p>
        </div>
    </div>
    <script src="script/dashboard.js"></script>

</body>
</html>
