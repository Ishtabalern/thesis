<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO balance_sheets (
            client_id, sheet_date, cash, receivables, inventory, equipment,
            other_assets, accounts_payable, loans, taxes_payable, other_liabilities
        ) VALUES (
            :client_id, :sheet_date, :cash, :receivables, :inventory, :equipment,
            :other_assets, :accounts_payable, :loans, :taxes_payable, :other_liabilities
        )
    ");
    $stmt->execute([
        ':client_id' => $_POST['client_id'],
        ':sheet_date' => $_POST['sheet_date'],
        ':cash' => $_POST['cash'] ?? 0,
        ':receivables' => $_POST['receivables'] ?? 0,
        ':inventory' => $_POST['inventory'] ?? 0,
        ':equipment' => $_POST['equipment'] ?? 0,
        ':other_assets' => $_POST['other_assets'] ?? 0,
        ':accounts_payable' => $_POST['accounts_payable'] ?? 0,
        ':loans' => $_POST['loans'] ?? 0,
        ':taxes_payable' => $_POST['taxes_payable'] ?? 0,
        ':other_liabilities' => $_POST['other_liabilities'] ?? 0,
    ]);
    header("Location: balance_sheet.php?success=1");
    exit();
}


// Filters
$filterClient = $_GET['client_id'] ?? '';
$filterMonth = $_GET['month'] ?? '';

$where = [];
$params = [];

if ($filterClient) {
    $where[] = "bs.client_id = :client_id";
    $params[':client_id'] = $filterClient;
}
if ($filterMonth) {
    $where[] = "DATE_FORMAT(bs.sheet_date, '%Y-%m') = :month";
    $params[':month'] = $filterMonth;
}

$sql = "
    SELECT bs.*, c.name AS client_name
    FROM balance_sheets bs
    JOIN clients c ON bs.client_id = c.id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY bs.sheet_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Balance Sheet Report</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="styles/balance_sheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">


    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        form { margin-bottom: 30px; }
        .success { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">
        Balance sheet updated for client ID <?= htmlspecialchars($_GET['client_id']) ?>!
        </div>
        <?php endif; ?>
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
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Balance Sheet</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
                <div class="dropdown">
                    <button class="dropbtn">Employee ▼</button>
                </div>
            </div>
        </div>

        <h2>Manual Balance Sheet Input</h2>
        <?php if (isset($_GET['success'])): ?>
            <div class="success">Balance sheet entry saved!</div>
        <?php endif; ?>
        <form method="POST">
            <label>Client:
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date: <input type="date" name="sheet_date" required></label><br><br>

            <label>Cash: <input type="number" step="0.01" name="cash"></label>
            <label>Receivables: <input type="number" step="0.01" name="receivables"></label>
            <label>Inventory: <input type="number" step="0.01" name="inventory"></label>
            <label>Equipment: <input type="number" step="0.01" name="equipment"></label>
            <label>Other Assets: <input type="number" step="0.01" name="other_assets"></label><br><br>

            <label>Accounts Payable: <input type="number" step="0.01" name="accounts_payable"></label>
            <label>Loans: <input type="number" step="0.01" name="loans"></label>
            <label>Taxes Payable: <input type="number" step="0.01" name="taxes_payable"></label>
            <label>Other Liabilities: <input type="number" step="0.01" name="other_liabilities"></label><br><br>

            <button type="submit">Save Balance Sheet</button>
        </form>

        <hr>
        <h2>Balance Sheet Summary</h2>

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

            <label>Month:
                <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>">
            </label>
            <button type="submit">Filter</button>
        </form>

        <table id="balanceTable">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Cash</th>
                    <th>Receivables</th>
                    <th>Inventory</th>
                    <th>Equipment</th>
                    <th>Other Assets</th>
                    <th>Total Assets</th>
                    <th>Accounts Payable</th>
                    <th>Loans</th>
                    <th>Taxes Payable</th>
                    <th>Other Liabilities</th>
                    <th>Total Liabilities</th>
                    <th>Equity</th>
                    <th>Net Worth</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): 
                    $totalAssets = $e['cash'] + $e['receivables'] + $e['inventory'] + $e['equipment'] + $e['other_assets'];
                    $totalLiabilities = $e['accounts_payable'] + $e['loans'] + $e['taxes_payable'] + $e['other_liabilities'];
                    $equity = $totalAssets - $totalLiabilities;
                    $netWorth = $equity; // or any custom logic you want
                ?>
                <tr>
                    <td><?= htmlspecialchars($e['client_name']) ?></td>
                    <td><?= htmlspecialchars($e['sheet_date']) ?></td>
                    <td><?= number_format($e['cash'], 2) ?></td>
                    <td><?= number_format($e['receivables'], 2) ?></td>
                    <td><?= number_format($e['inventory'], 2) ?></td>
                    <td><?= number_format($e['equipment'], 2) ?></td>
                    <td><?= number_format($e['other_assets'], 2) ?></td>
                    <td><strong><?= number_format($totalAssets, 2) ?></strong></td>
                    <td><?= number_format($e['accounts_payable'], 2) ?></td>
                    <td><?= number_format($e['loans'], 2) ?></td>
                    <td><?= number_format($e['taxes_payable'], 2) ?></td>
                    <td><?= number_format($e['other_liabilities'], 2) ?></td>
                    <td><strong><?= number_format($totalLiabilities, 2) ?></strong></td>
                    <td><strong><?= number_format($equity, 2) ?></strong></td>
                    <td><strong><?= number_format($netWorth, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form action="functions/update_balance_sheet.php" method="post" style="display:inline;">
        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
        <button type="submit" class="btn btn-sm btn-primary">Update Balance Sheet</button>
        </form>

        <button onclick="openModal('classicBalanceSheetModal')">
        📄 View Classic Balance Sheet
        </button>

    </div>
    <div id="classicBalanceSheetModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Balance Sheet - XYZ Company</h5>
                <button class="btn-close" onclick="closeModal('classicBalanceSheetModal')">&times;</button>
            </div>
            <div class="modal-body">
                <style>
                .balance-box {
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: auto;
                    padding: 20px;
                    border: 1px solid #000;
                    background-color: #fff;
                }
                .balance-box h4, .balance-box h5 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .balance-box table {
                    width: 100%;
                    font-size: 14px;
                }
                .balance-box td {
                    padding: 4px;
                }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                </style>

                <div class="balance-box">
                <h4>XYZ COMPANY</h4>
                <h5>Balance Sheet<br>12/31/2017</h5>

                <table>
                    <tr><td colspan="2" class="bold">ASSETS</td></tr>
                    <tr><td colspan="2" class="bold">Current Assets:</td></tr>
                    <tr><td>Cash</td><td class="right">$12,000</td></tr>
                    <tr><td>Accounts Receivable</td><td class="right">$25,000</td></tr>
                    <tr><td>Inventory</td><td class="right">$13,000</td></tr>
                    <tr><td>Prepaid Rent</td><td class="right">$20,000</td></tr>
                    <tr><td class="bold">Total Current Assets</td><td class="right bold">$70,000</td></tr>

                    <tr><td colspan="2" class="bold">Long-Term Assets:</td></tr>
                    <tr><td>Land</td><td class="right">$126,000</td></tr>
                    <tr><td>Buildings & Improvements</td><td class="right">$300,000</td></tr>
                    <tr><td>Furniture & Fixtures</td><td class="right">$150,000</td></tr>
                    <tr><td>General Equipment</td><td class="right">$30,000</td></tr>
                    <tr><td class="bold">Total Fixed Assets</td><td class="right bold">$606,000</td></tr>

                    <tr><td class="bold">TOTAL ASSETS</td><td class="right bold">$776,000</td></tr>

                    <tr><td colspan="2" class="bold">LIABILITIES</td></tr>
                    <tr><td colspan="2" class="bold">Current Liabilities:</td></tr>
                    <tr><td>Accounts Payable</td><td class="right">$50,000</td></tr>
                    <tr><td>Taxes Payable</td><td class="right">$25,000</td></tr>
                    <tr><td>Salaries/Wages Payable</td><td class="right">$30,000</td></tr>
                    <tr><td>Interest Payable</td><td class="right">$7,000</td></tr>
                    <tr><td class="bold">Total Current Liabilities</td><td class="right bold">$112,000</td></tr>

                    <tr><td colspan="2" class="bold">Long-Term Liabilities:</td></tr>
                    <tr><td>Loan</td><td class="right">$350,000</td></tr>
                    <tr><td class="bold">Total Long-Term Liabilities</td><td class="right bold">$350,000</td></tr>

                    <tr><td class="bold">TOTAL LIABILITIES</td><td class="right bold">$462,000</td></tr>

                    <tr><td colspan="2" class="bold">OWNER'S EQUITY</td></tr>
                    <tr><td>Paid in Capital</td><td class="right">$84,000</td></tr>
                    <tr><td>Retained Earnings</td><td class="right">$230,000</td></tr>
                    <tr><td class="bold">TOTAL OWNER'S EQUITY</td><td class="right bold">$314,000</td></tr>

                    <tr><td class="bold">TOTAL LIABILITIES & OWNER'S EQUITY</td><td class="right bold">$776,000</td></tr>
                </table>
            </div>

        </div>
        </div>
    </div>
    </div>


<script src="script/dashboard.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(() => {
        $('#balanceTable').DataTable();
    });

    function openModal(id) {
    document.getElementById(id).classList.add('show');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    // Optional: Close on background click
    window.onclick = function(event) {
        const modal = document.getElementById('classicBalanceSheetModal');
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    }
</script>

</body>
</html>
