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
            <a class="btn-tabs" href="trial_balance.php"><i class="fa-solid fa-file"></i>Trial Balance</a>
            <a class="btn-tabs" href="cash_flow.php"><i class="fa-solid fa-file"></i>Cash Flow</a>
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
        <form class="balance-sheet" method="POST">
            <div class="input-container">
                
                <div class="section">
                    
                    <div class="inputs">
                        <label>Client:</label>
                        <select name="client_id" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="inputs">
                        <label>Date:</label> 
                        <input type="date" name="sheet_date" required>
                    </div>
 
                </div>
                
             
                 <div class="section">
                    <div class="inputs">
                        <label>Cash:</label> 
                        <input type="number" step="0.01" name="cash">
                    </div>

                    <div class="inputs">
                        <label>Receivables:</label>
                        <input type="number" step="0.01" name="receivables">
                    </div>

                    <div class="inputs">
                        <label>Inventory:</label>
                        <input type="number" step="0.01" name="inventory">
                    </div>

                    <div class="inputs">
                        <label>Equipment: </label>
                        <input type="number" step="0.01" name="equipment">
                    </div>

                    <div class="inputs">
                        <label>Other Assets:</label>
                        <input type="number" step="0.01" name="other_assets">
                    </div>            
                </div>

                <div class="section">         
                    <div class="inputs">
                        <label>Accounts Payable:</label> 
                        <input type="number" step="0.01" name="accounts_payable">
                    </div> 

                    <div class="inputs">
                        <label>Loans:</label>
                        <input type="number" step="0.01" name="loans">
                    </div> 

                    <div class="inputs">
                        <label>Taxes Payable:</label> 
                        <input type="number" step="0.01" name="taxes_payable">
                    </div> 

                    <div class="inputs">
                        <label>Other Liabilities:</label> 
                        <input type="number" step="0.01" name="other_liabilities">
                    </div>            
                </div>           
                            
                
                  <!--- -->
                

            </div>
    
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

<<<<<<< HEAD
        <div class="balance-container">
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
        </div>
       

=======
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
                    <td>₱<?= number_format($e['cash'], 2) ?></td>
                    <td>₱<?= number_format($e['receivables'], 2) ?></td>
                    <td>₱<?= number_format($e['inventory'], 2) ?></td>
                    <td>₱<?= number_format($e['equipment'], 2) ?></td>
                    <td>₱<?= number_format($e['other_assets'], 2) ?></td>
                    <td><strong>₱<?= number_format($totalAssets, 2) ?></strong></td>
                    <td>₱<?= number_format($e['accounts_payable'], 2) ?></td>
                    <td>₱<?= number_format($e['loans'], 2) ?></td>
                    <td>₱<?= number_format($e['taxes_payable'], 2) ?></td>
                    <td>₱<?= number_format($e['other_liabilities'], 2) ?></td>
                    <td><strong>₱<?= number_format($totalLiabilities, 2) ?></strong></td>
                    <td><strong>₱<?= number_format($equity, 2) ?></strong></td>
                    <td><strong>₱<?= number_format($netWorth, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($filterClient): ?>
>>>>>>> 527e52dedfbadcc0859c2b3aadd6ec726f597773
        <form action="functions/update_balance_sheet.php" method="post" style="display:inline;">
            <input type="hidden" name="client_id" value="<?= htmlspecialchars($filterClient) ?>">
            <button type="submit" class="btn btn-sm btn-primary">Update Balance Sheet</button>
        </form>
        <?php endif; ?>


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

                <?php
                // Fetch the latest balance sheet entry per client
                $stmt = $pdo->prepare("
                    SELECT bs.*, c.name AS client_name
                    FROM balance_sheets bs
                    JOIN clients c ON bs.client_id = c.id
                    WHERE bs.id = (
                        SELECT id FROM balance_sheets
                        WHERE client_id = bs.client_id
                        ORDER BY sheet_date DESC
                        LIMIT 1
                    )
                    ORDER BY c.name
                ");
                $stmt->execute();
                $latestEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php foreach ($latestEntries as $entry): ?>
                    <?php
                    $totalCurrentAssets = $entry['cash'] + $entry['receivables'] + $entry['inventory'];
                    $totalLongTermAssets = $entry['equipment'] + $entry['other_assets'];
                    $totalAssets = $totalCurrentAssets + $totalLongTermAssets;

                    $totalLiabilities = $entry['accounts_payable'] + $entry['loans'] + $entry['taxes_payable'] + $entry['other_liabilities'];
                    $equity = $totalAssets - $totalLiabilities;
                    ?>
                    <div class="balance-box" style="margin-bottom: 40px;">
                        <h4><?= htmlspecialchars($entry['client_name']) ?></h4>
                        <h5>Balance Sheet<br><?= date('F d, Y', strtotime($entry['sheet_date'])) ?></h5>

                        <table>
                            <tr><td colspan="2" class="bold">ASSETS</td></tr>
                            <tr><td colspan="2" class="bold">Current Assets:</td></tr>
                            <tr><td>Cash</td><td class="right">₱<?= number_format($entry['cash'], 2) ?></td></tr>
                            <tr><td>Accounts Receivable</td><td class="right">₱<?= number_format($entry['receivables'], 2) ?></td></tr>
                            <tr><td>Inventory</td><td class="right">₱<?= number_format($entry['inventory'], 2) ?></td></tr>
                            <tr><td class="bold">Total Current Assets</td><td class="right bold">₱<?= number_format($totalCurrentAssets, 2) ?></td></tr>

                            <tr><td colspan="2" class="bold">Long-Term Assets:</td></tr>
                            <tr><td>Equipment</td><td class="right">₱<?= number_format($entry['equipment'], 2) ?></td></tr>
                            <tr><td>Other Assets</td><td class="right">₱<?= number_format($entry['other_assets'], 2) ?></td></tr>
                            <tr><td class="bold">Total Long-Term Assets</td><td class="right bold">₱<?= number_format($totalLongTermAssets, 2) ?></td></tr>

                            <tr><td class="bold">Total Assets</td><td class="right bold">₱<?= number_format($totalAssets, 2) ?></td></tr>

                            <tr><td colspan="2" class="bold" style="padding-top: 15px;">LIABILITIES</td></tr>
                            <tr><td>Accounts Payable</td><td class="right">₱<?= number_format($entry['accounts_payable'], 2) ?></td></tr>
                            <tr><td>Loans</td><td class="right">₱<?= number_format($entry['loans'], 2) ?></td></tr>
                            <tr><td>Taxes Payable</td><td class="right">₱<?= number_format($entry['taxes_payable'], 2) ?></td></tr>
                            <tr><td>Other Liabilities</td><td class="right">₱<?= number_format($entry['other_liabilities'], 2) ?></td></tr>
                            <tr><td class="bold">Total Liabilities</td><td class="right bold">₱<?= number_format($totalLiabilities, 2) ?></td></tr>

                            <tr><td colspan="2" class="bold" style="padding-top: 15px;">EQUITY</td></tr>
                            <tr><td>Owner’s Equity</td><td class="right">₱<?= number_format($equity, 2) ?></td></tr>

                            <tr><td class="bold">Total Liabilities & Equity</td><td class="right bold">₱<?= number_format($totalLiabilities + $equity, 2) ?></td></tr>
                        </table>
                    </div>
                <?php endforeach; ?>
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
    document.getElementById(id).style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
        document.getElementById(id).style.display = 'none';
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
