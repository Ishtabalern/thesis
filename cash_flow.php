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
    <style>
        body { font-family: Arial; padding: 20px; }
        .cash-flow-box { border: 1px solid #ccc; padding: 20px; max-width: 700px; margin: auto; }
        h2 { text-align: center; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        td, th { padding: 8px; border-bottom: 1px solid #ddd; }
        .label { font-weight: bold; }
        .amount { text-align: right; }
    </style>
</head>
<body>

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

</body>
</html>
