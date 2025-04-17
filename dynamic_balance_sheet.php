<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

$clientId = $_SESSION['client_id'] ?? 1; // Default for now
$asOfDate = date('Y-m-d'); // Default to today

// ========== Get Auto-Generated Balances from Journal Entries ==========
$sql = "
SELECT 
    at.name AS account_type,
    a.name AS account_name,
    SUM(jel.debit) AS total_debit,
    SUM(jel.credit) AS total_credit
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN chart_of_accounts a ON jel.account_id = a.id
JOIN account_types at ON a.account_type_id = at.id
WHERE je.client_id = ? AND je.date <= ?
GROUP BY at.name, a.name
ORDER BY FIELD(at.name, 'Asset', 'Liability', 'Equity'), a.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$clientId, $asOfDate]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize results
$generated = ['Asset' => [], 'Liability' => [], 'Equity' => []];
$totals = ['Asset' => 0, 'Liability' => 0, 'Equity' => 0];

foreach ($results as $row) {
    $type = $row['account_type'];
    $balance = $row['total_debit'] - $row['total_credit'];
    if ($type === 'Liability' || $type === 'Equity') {
        $balance = $row['total_credit'] - $row['total_debit'];
    }

    $validTypes = ['Asset', 'Liability', 'Equity'];
    if (!in_array($type, $validTypes)) continue;
    
    $generated[$type][] = ['name' => $row['account_name'], 'balance' => $balance];
    $totals[$type] += $balance;    
}

// ========== Handle Save Snapshot Action ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'save_snapshot') {
    // Clear existing manual data for this client and date
    $pdo->prepare("DELETE FROM balance_sheets WHERE client_id = ? AND sheet_date = ?")
        ->execute([$clientId, $asOfDate]);

    $insertStmt = $pdo->prepare("
        INSERT INTO balance_sheets (client_id, sheet_date, account_type, account_name, balance)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($generated as $type => $accounts) {
        foreach ($accounts as $acc) {
            $insertStmt->execute([
                $clientId,
                $asOfDate,
                $type,
                $acc['name'],
                $acc['balance']
            ]);
        }
    }

    $snapshotMessage = "✅ Snapshot saved to manual balance sheet!";
}

// ========== Get Manual Balances from balance_sheets ==========
$sqlManual = "
SELECT * FROM balance_sheets 
WHERE client_id = ? AND sheet_date = ?
";
$stmtManual = $pdo->prepare($sqlManual);
$stmtManual->execute([$clientId, $asOfDate]);
$manualData = $stmtManual->fetchAll(PDO::FETCH_ASSOC);

// Fetch all clients (for admin use)
$clientStmt = $pdo->query("SELECT id, name FROM clients ORDER BY name");
$clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a client is selected from the dropdown first
if (isset($_GET['view_client_id'])) {
    $clientId = $_GET['view_client_id'];
} else {
    // Default to session's client_id
    $clientId = $_SESSION['client_id'] ?? 1;
}
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');


?>

<!DOCTYPE html>
<html>
<head>
    <title>Dynamic Balance Sheet</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .section { margin-bottom: 30px; }
        .section h3 { border-bottom: 1px solid #ccc; }
        .account { margin-left: 20px; }
        .totals { font-weight: bold; margin-top: 10px; }
        .divider { margin: 40px 0; border-top: 2px dashed #888; }
        .success { color: green; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
<form method="GET" style="margin-bottom: 20px;">
    <label for="view_client_id">🔍 View Balance Sheet for Client:</label>
    <select name="view_client_id" id="view_client_id" onchange="this.form.submit()">
        <?php foreach ($clients as $client): ?>
            <option value="<?= $client['id'] ?>" <?= ($client['id'] == $clientId ? 'selected' : '') ?>>
                <?= htmlspecialchars($client['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
<form method="GET">
    <label>Client:</label>
    <select name="view_client_id" id="view_client_id" onchange="this.form.submit()">
        <?php foreach ($clients as $client): ?>
            <option value="<?= $client['id'] ?>" <?= ($client['id'] == $clientId ? 'selected' : '') ?>>
                <?= htmlspecialchars($client['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>As of date:</label>
    <input type="date" name="as_of_date" value="<?= htmlspecialchars($asOfDate) ?>" onchange="this.form.submit()">
</form>



<h2>📊 Dynamic Balance Sheet (Generated from Journal Entries)</h2>
<p>As of: <strong><?= htmlspecialchars($asOfDate) ?></strong></p>

<?php if (isset($snapshotMessage)): ?>
    <div class="success"><?= $snapshotMessage ?></div>
<?php endif; ?>

<?php foreach (['Asset', 'Liability', 'Equity'] as $type): ?>
    <div class="section">
        <h3><?= $type ?>s</h3>
        <?php foreach ($generated[$type] as $acc): ?>
            <div class="account"><?= htmlspecialchars($acc['name']) ?>: $<?= number_format($acc['balance'], 2) ?></div>
        <?php endforeach; ?>
        <div class="totals">Total <?= $type ?>s: $<?= number_format($totals[$type], 2) ?></div>
    </div>
<?php endforeach; ?>

<div class="totals" style="margin-top: 30px; border-top: 2px solid #000; padding-top: 10px;">
    ✅ <strong>Check:</strong> Assets ($<?= number_format($totals['Asset'], 2) ?>) = Liabilities ($<?= number_format($totals['Liability'], 2) ?>) + Equity ($<?= number_format($totals['Equity'], 2) ?>)
</div>

<form method="POST" style="margin-top: 30px;">
    <input type="hidden" name="action" value="save_snapshot">
    <button type="submit">💾 Save Snapshot to Manual Table</button>
</form>

<div class="divider"></div>

<h2>🗃️ Manual Balance Sheet (from `balance_sheets` table)</h2>

<?php if (count($manualData) > 0): ?>
    <?php foreach ($manualData as $row): ?>
        <div class="section">
            <strong><?= htmlspecialchars($row['account_type']) ?> - <?= htmlspecialchars($row['account_name']) ?></strong>: $<?= number_format($row['balance'], 2) ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No manual balance sheet data found for this date.</p>
<?php endif; ?>

</body>
</html>
