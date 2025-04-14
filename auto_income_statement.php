<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Get clients
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Filters
$clientId = $_GET['client_id'] ?? '';
$month = $_GET['month'] ?? '';

$filterClause = "";
$params = [];

if ($clientId) {
    $filterClause .= " AND je.client_id = :client_id";
    $params[':client_id'] = $clientId;
}
if ($month) {
    $filterClause .= " AND DATE_FORMAT(je.date, '%Y-%m') = :month";
    $params[':month'] = $month;
}

// Fetch account types for revenue and expenses
$revenueAccounts = [/* Add your revenue account IDs or use a query */];
$expenseAccounts = [/* Add your expense account IDs or use a query */];

// Get revenue and expense totals
$sql = "
    SELECT 
        coa.name AS account_name,
        at.name AS account_type,
        SUM(CASE WHEN jel.debit > 0 THEN jel.debit ELSE -jel.credit END) AS amount
    FROM journal_entries je
    JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
    JOIN chart_of_accounts coa ON jel.account_id = coa.id
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE at.name IN ('Revenue', 'Expense') $filterClause
    GROUP BY coa.id
    ORDER BY at.name, coa.name
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results
$revenues = [];
$expenses = [];
$totalRevenue = 0;
$totalExpenses = 0;

foreach ($lines as $line) {
    if ($line['account_type'] === 'Revenue') {
        $revenues[] = $line;
        $totalRevenue += $line['amount'];
    } elseif ($line['account_type'] === 'Expense') {
        $expenses[] = $line;
        $totalExpenses += $line['amount'];
    }
}

$netIncome = $totalRevenue - $totalExpenses;
// Detailed breakdown of manual entries from income_statements
if ($clientId && $month) {
    $stmtManual = $pdo->prepare("
        SELECT
            SUM(sales_revenue) AS sales_revenue,
            SUM(other_income) AS other_income,
            SUM(cogs) AS cogs,
            SUM(salaries) AS salaries,
            SUM(rent) AS rent,
            SUM(utilities) AS utilities,
            SUM(other_expenses) AS other_expenses
        FROM income_statements
        WHERE client_id = :client_id AND DATE_FORMAT(statement_date, '%Y-%m') = :month
    ");
    $stmtManual->execute([
        ':client_id' => $clientId,
        ':month' => $month
    ]);
    $manual = $stmtManual->fetch(PDO::FETCH_ASSOC);

    // Add manual revenues
    if ($manual['sales_revenue'] > 0) {
        $revenues[] = ['account_name' => 'Sales Revenue (Manual)', 'amount' => $manual['sales_revenue'], 'account_type' => 'Revenue'];
        $totalRevenue += $manual['sales_revenue'];
    }

    if ($manual['other_income'] > 0) {
        $revenues[] = ['account_name' => 'Other Income (Manual)', 'amount' => $manual['other_income'], 'account_type' => 'Revenue'];
        $totalRevenue += $manual['other_income'];
    }

    // Add manual expenses
    $expenseFields = [
        'cogs' => 'Cost of Goods Sold (Manual)',
        'salaries' => 'Salaries (Manual)',
        'rent' => 'Rent (Manual)',
        'utilities' => 'Utilities (Manual)',
        'other_expenses' => 'Other Expenses (Manual)',
    ];

    foreach ($expenseFields as $field => $label) {
        if ($manual[$field] > 0) {
            $expenses[] = ['account_name' => $label, 'amount' => $manual[$field], 'account_type' => 'Expense'];
            $totalExpenses += $manual[$field];
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto Income Statement</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/sidebar.css">
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        h2 { margin-top: 40px; }
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
                        <li><a class="btn-tabs" href="client_form.php" class="active">Add customer</a></li>
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
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Income Statement Report</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                </div>
            </div>
        </div>

        <form method="GET">
            <label>Client:
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $clientId == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Month:
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
            </label>
            <button type="submit">Generate</button>
        </form>

        <?php if ($clientId): ?>
            <h2>Revenue</h2>
            <table id="revenueTable">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenues as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['account_name']) ?></td>
                            <td><?= number_format($r['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong>Total Revenue</strong></td>
                        <td><strong><?= number_format($totalRevenue, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <h2>Expenses</h2>
            <table id="expenseTable">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['account_name']) ?></td>
                            <td><?= number_format($e['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong>Total Expenses</strong></td>
                        <td><strong><?= number_format($totalExpenses, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <h2>Net Income: <?= number_format($netIncome, 2) ?></h2>

            <button onclick="window.print()">Export to PDF</button>
        <?php endif; ?>
    </div>

    <script src="script/dashboard.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#revenueTable, #expenseTable').DataTable({
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
</body>
</html>
