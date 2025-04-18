<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Fetch clients
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Insert new entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sales_revenue = $_POST['sales_revenue'] ?? 0;
    $other_income = $_POST['other_income'] ?? 0;
    $cogs = $_POST['cogs'] ?? 0;
    $salaries = $_POST['salaries'] ?? 0;
    $rent = $_POST['rent'] ?? 0;
    $utilities = $_POST['utilities'] ?? 0;
    $other_expenses = $_POST['other_expenses'] ?? 0;

    $total_revenue = $sales_revenue + $other_income;
    $total_expenses = $cogs + $salaries + $rent + $utilities + $other_expenses;
    $net_income = $total_revenue - $total_expenses;

    $stmt = $pdo->prepare("
        INSERT INTO income_statements (
            client_id, statement_date, sales_revenue, other_income, cogs,
            salaries, rent, utilities, other_expenses, net_income
        ) VALUES (
            :client_id, :statement_date, :sales_revenue, :other_income, :cogs,
            :salaries, :rent, :utilities, :other_expenses, :net_income
        )
    ");
    $stmt->execute([
        ':client_id' => $_POST['client_id'],
        ':statement_date' => $_POST['statement_date'],
        ':sales_revenue' => $sales_revenue,
        ':other_income' => $other_income,
        ':cogs' => $cogs,
        ':salaries' => $salaries,
        ':rent' => $rent,
        ':utilities' => $utilities,
        ':other_expenses' => $other_expenses,
        ':net_income' => $net_income
    ]);

    header("Location: income_statement.php?success=1");
    exit();
}

// Filtering
$filterClient = $_GET['client_id'] ?? '';
$filterMonth = $_GET['month'] ?? '';

$where = [];
$params = [];

if ($filterClient) {
    $where[] = "inc.client_id = :client_id";
    $params[':client_id'] = $filterClient;
}
if ($filterMonth) {
    $where[] = "DATE_FORMAT(inc.statement_date, '%Y-%m') = :month";
    $params[':month'] = $filterMonth;
}

$sql = "
    SELECT inc.*, c.name AS client_name
    FROM income_statements inc
    JOIN clients c ON inc.client_id = c.id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY inc.statement_date DESC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Create a period label like "For the month ended April 30, 2025"
$periodLabel = 'For the year';
if ($filterMonth) {
    $monthName = date('F', strtotime($filterMonth . '-01'));
    $year = date('Y', strtotime($filterMonth . '-01'));
    $periodLabel = "For the month ended {$monthName} " . date('t, Y', strtotime($filterMonth . '-01'));
} elseif (!empty($entries)) {
    $latest = max(array_column($entries, 'statement_date'));
    $year = date('Y', strtotime($latest));
    $periodLabel = "For the year ended December 31, {$year}";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Income Statement Report</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        .success { color: green; margin-bottom: 10px; }
    </style>
    <link rel="stylesheet" href="styles/income-statement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles/sidebar.css">
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
            <h1>Income Statement Report</h1>
            <div class="user-controls">
                <a href="functions/logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
                <div class="dropdown">
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">Income statement saved!</div>
        <?php endif; ?>

        <h2>Add New Entry</h2>
        <form method="POST">
            <label>Client:
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date: <input type="date" name="statement_date" required></label><br><br>

            <label>Sales Revenue: <input type="number" step="0.01" name="sales_revenue"></label>
            <label>Other Income: <input type="number" step="0.01" name="other_income"></label><br><br>
            <label>COGS: <input type="number" step="0.01" name="cogs"></label>
            <label>Salaries: <input type="number" step="0.01" name="salaries"></label>
            <label>Rent: <input type="number" step="0.01" name="rent"></label>
            <label>Utilities: <input type="number" step="0.01" name="utilities"></label>
            <label>Other Expenses: <input type="number" step="0.01" name="other_expenses"></label><br><br>

            <button type="submit">Save Statement</button>
        </form>

        <hr>
        <h2>Income Statement Summary</h2>
        <h3><?= $periodLabel ?></h3>
        

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
            <label>Month: <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>"></label>
            <button type="submit">Filter</button>
        </form>

        <table id="incomeTable">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Sales Revenue</th>
                    <th>Other Income</th>
                    <th>Total Revenue</th>
                    <th>COGS</th>
                    <th>Salaries</th>
                    <th>Rent</th>
                    <th>Utilities</th>
                    <th>Other Expenses</th>
                    <th>Total Expenses</th>
                    <th>Net Income</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): 
                    $totalRevenue = $e['sales_revenue'] + $e['other_income'];
                    $totalExpenses = $e['cogs'] + $e['salaries'] + $e['rent'] + $e['utilities'] + $e['other_expenses'];
                    $netIncome = $totalRevenue - $totalExpenses;
                ?>
                <tr>
                    <td><?= htmlspecialchars($e['client_name']) ?></td>
                    <td><?= htmlspecialchars($e['statement_date']) ?></td>
                    <td><?= number_format($e['sales_revenue'], 2) ?></td>
                    <td><?= number_format($e['other_income'], 2) ?></td>
                    <td><strong><?= number_format($totalRevenue, 2) ?></strong></td>
                    <td><?= number_format($e['cogs'], 2) ?></td>
                    <td><?= number_format($e['salaries'], 2) ?></td>
                    <td><?= number_format($e['rent'], 2) ?></td>
                    <td><?= number_format($e['utilities'], 2) ?></td>
                    <td><?= number_format($e['other_expenses'], 2) ?></td>
                    <td><strong><?= number_format($totalExpenses, 2) ?></strong></td>
                    <td><strong><?= number_format($netIncome, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button onclick="exportPDF()">Export as PDF</button>
    </div>
    <script src="script/dashboard.js"></script>
    <!-- jQuery, DataTables, jsPDF CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" 
      href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    $(document).ready(function () {
        $('#incomeTable').DataTable({
            paging: true,
            ordering: true,
            info: false
        });
    });

    async function exportPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFontSize(14);
        doc.text("Income Statement", 14, 20);
        doc.setFontSize(11);
        doc.text("<?= $periodLabel ?>", 14, 28);

        let startY = 40;
        const headers = [
            "Client", "Date", "Sales Revenue", "Other Income", "Total Revenue",
            "COGS", "Salaries", "Rent", "Utilities", "Other Expenses",
            "Total Expenses", "Net Income"
        ];

        const rows = <?php echo json_encode(array_map(function($e) {
            $totalRevenue = $e['sales_revenue'] + $e['other_income'];
            $totalExpenses = $e['cogs'] + $e['salaries'] + $e['rent'] + $e['utilities'] + $e['other_expenses'];
            $netIncome = $totalRevenue - $totalExpenses;
            return [
                $e['client_name'],
                $e['statement_date'],
                number_format($e['sales_revenue'], 2),
                number_format($e['other_income'], 2),
                number_format($totalRevenue, 2),
                number_format($e['cogs'], 2),
                number_format($e['salaries'], 2),
                number_format($e['rent'], 2),
                number_format($e['utilities'], 2),
                number_format($e['other_expenses'], 2),
                number_format($totalExpenses, 2),
                number_format($netIncome, 2)
            ];
        }, $entries)); ?>;

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: startY,
            styles: { fontSize: 8 },
            margin: { left: 14, right: 14 },
        });

        doc.save("income_statement.pdf");
    }
</script>

</body>
</html>
