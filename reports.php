<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$servername = "localhost";
$username = "admin";
$password = "123";
$dbname = "cskdb";
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

$client = $_GET['client'] ?? null;
$month = $_GET['month'] ?? null;
$vendor = $_GET['vendor'] ?? null;

$where = [];
$params = [];

if ($client) {
    $where[] = 'r.client_id = :client';
    $params[':client'] = $client;
}
if ($month) {
    $where[] = "DATE_FORMAT(r.date, '%Y-%m') = :month";
    $params[':month'] = $month;
}
if ($vendor) {
    $where[] = 'r.vendor = :vendor';
    $params[':vendor'] = $vendor;
}

$sql = "
    SELECT 
        r.client_id,
        c.name AS client_name,
        r.category,
        r.type,
        r.total,
        r.vendor,
        r.img_url,
        r.payment_method,
        DATE_FORMAT(r.date, '%Y-%m') AS period
    FROM receipts r
    INNER JOIN clients c ON r.client_id = c.id
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY c.name, r.vendor";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch dropdown options
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$vendors = $pdo->query("SELECT DISTINCT vendor FROM receipts ORDER BY vendor")->fetchAll(PDO::FETCH_ASSOC);

// Totals
$clientTotals = [];
$paymentTotals = [];

foreach ($data as $row) {
    $client = $row['client_name'];
    $type = strtolower(trim($row['type']));
    $amount = $row['total'];

    if (!in_array($type, ['income', 'expense'])) continue;

    if (!isset($clientTotals[$client])) {
        $clientTotals[$client] = ['income' => 0, 'expense' => 0];
    }
    $clientTotals[$client][$type] += $amount;

    $pm = $row['payment_method'];
    if (!isset($paymentTotals[$pm])) {
        $paymentTotals[$pm] = 0;
    }
    $paymentTotals[$pm] += $amount;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt Summary Report</title>
    <link rel="stylesheet" href="styles/sidebar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=scan" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=receipt_long" />
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        img.preview { width: 50px; cursor: pointer; transition: 0.3s; }
        img.preview:hover { transform: scale(2); position: relative; z-index: 99; }
        form.filters { margin-bottom: 20px; }
        .summary-box { display: flex; gap: 40px; margin-bottom: 30px; }
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
            <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
            <a class="btn-tabs" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        </div>
    </div>

    <div class="dashboard">
        <div class="top-bar">
            <h1>Receipt Summary Report</h1>
            <div class="user-controls">
                <a href="logout.php"><button class="logout-btn">Log out</button></a> <!-- Link to logout -->
            </div>
        </div>

        <form class="filters" method="get">
            <label>Month:
                <input type="month" name="month" value="<?= htmlspecialchars($month ?? '') ?>">
            </label>
            <label>Client:
                <select name="client">
                    <option value="">All</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $client == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Vendor:
                <select name="vendor">
                    <option value="">All</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?= htmlspecialchars($v['vendor']) ?>" <?= $vendor == $v['vendor'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['vendor']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Filter</button>
        </form>

        <!-- Main Data Table -->
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Vendor</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><?= htmlspecialchars($row['vendor']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td>$<?= number_format($row['total'], 2) ?></td>
                    <td>
                        <?php if ($row['img_url']): ?>
                            <a href="<?= htmlspecialchars($row['img_url']) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($row['img_url']) ?>" class="preview">
                            </a>
                        <?php else: ?> N/A <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Profit/Loss Summary -->
        <h3>Profit/Loss Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Total Income</th>
                    <th>Total Expenses</th>
                    <th>Profit/Loss</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientTotals as $name => $totals):
                    $income = $totals['income'];
                    $expense = $totals['expense'];
                    $net = $income - $expense;
                ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td style="color:green;">$<?= number_format($income, 2) ?></td>
                    <td style="color:red;">$<?= number_format($expense, 2) ?></td>
                    <td style="<?= $net >= 0 ? 'color:green' : 'color:red' ?>">
                        $<?= number_format($net, 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Payment Method Summary -->
        <h3>Summary by Payment Method</h3>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Total Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paymentTotals as $pm => $total): ?>
                <tr>
                    <td><?= htmlspecialchars($pm) ?></td>
                    <td>$<?= number_format($total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(() => {
        $('#reportTable').DataTable({
            paging: true,
            searching: true,
            ordering: true
        });
    });
</script>

</body>
</html>
