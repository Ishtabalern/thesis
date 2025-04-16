<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Fetch clients
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle new entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("REPLACE INTO owners_equity 
        (client_id, year, beginning_capital, additional_investment, withdrawals) 
        VALUES (:client_id, :year, :beginning_capital, :additional_investment, :withdrawals)
    ");
    $stmt->execute([
        ':client_id' => $_POST['client_id'],
        ':year' => $_POST['year'],
        ':beginning_capital' => $_POST['beginning_capital'],
        ':additional_investment' => $_POST['additional_investment'],
        ':withdrawals' => $_POST['withdrawals'],
    ]);
    header("Location: owners_equity.php?success=1");
    exit();
}

// Filters
$filterClient = $_GET['client_id'] ?? '';
$filterYear = $_GET['year'] ?? date('Y');

$where = [];
$params = [];

if ($filterClient) {
    $where[] = "oe.client_id = :client_id";
    $params[':client_id'] = $filterClient;
}
if ($filterYear) {
    $where[] = "oe.year = :year";
    $params[':year'] = $filterYear;
}

$sql = "SELECT oe.*, c.name AS client_name
        FROM owners_equity oe
        JOIN clients c ON oe.client_id = c.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY oe.year DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Net Income from income_statements (optional auto-calc)
function getNetIncome($pdo, $clientId, $year) {
    $stmt = $pdo->prepare("SELECT 
        SUM(sales_revenue + other_income - cogs - salaries - rent - utilities - other_expenses) AS net_income
        FROM income_statements 
        WHERE client_id = :client_id AND YEAR(statement_date) = :year
    ");
    $stmt->execute([':client_id' => $clientId, ':year' => $year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? floatval($row['net_income']) : 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statement of Owner's Equity</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        .success { color: green; margin-bottom: 10px; }
    </style>
    <link rel="stylesheet" href="styles/sidebar.css">
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
            <a class="btn-tabs" href="income_statement.php"><i class="fa-solid fa-file"></i>Income Statement (Manual)</a>
            <a class="btn-tabs" href="auto_income_statement.php"><i class="fa-solid fa-file"></i>Income Statement (auto)</a>
            <a class="btn-tabs" href="owners_equity.php"><i class="fa-solid fa-file"></i>Owner's Equity</a>
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Owner's Equity</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">Entry saved successfully!</div>
        <?php endif; ?>

        <h2>Add / Update Entry</h2>
        <form method="POST">
            <label>Client:
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Year: <input type="number" name="year" value="<?= date('Y') ?>" required></label><br><br>

            <label>Beginning Capital: <input type="number" step="0.01" name="beginning_capital"></label>
            <label>Additional Investment: <input type="number" step="0.01" name="additional_investment"></label>
            <label>Withdrawals: <input type="number" step="0.01" name="withdrawals"></label><br><br>

            <button type="submit">Save Entry</button>
        </form>

        <hr>
        <h2>Owner's Equity Report</h2>
        <form method="GET">
            <label>Filter by Client:
                <select name="client_id">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClient == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Year: <input type="number" name="year" value="<?= htmlspecialchars($filterYear) ?>"></label>
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Year</th>
                    <th>Beginning Capital</th>
                    <th>Additional Investment</th>
                    <th>Net Income</th>
                    <th>Withdrawals</th>
                    <th>Ending Capital</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): 
                    $netIncome = getNetIncome($pdo, $e['client_id'], $e['year']);
                    $endingCapital = $e['beginning_capital'] + $e['additional_investment'] + $netIncome - $e['withdrawals'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($e['client_name']) ?></td>
                    <td><?= htmlspecialchars($e['year']) ?></td>
                    <td><?= number_format($e['beginning_capital'], 2) ?></td>
                    <td><?= number_format($e['additional_investment'], 2) ?></td>
                    <td><strong><?= number_format($netIncome, 2) ?></strong></td>
                    <td><?= number_format($e['withdrawals'], 2) ?></td>
                    <td><strong><?= number_format($endingCapital, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="script/dashboard.js"></script>
</body>
</html>
