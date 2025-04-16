<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// === CONFIGURABLE ACCOUNTS MAPPING ===
$accountMap = [
    'Cash' => 'cash',
    'Accounts Receivable' => 'receivables',
    'Inventory' => 'inventory',
    'Equipment' => 'equipment',
    'Accounts Payable' => 'accounts_payable',
    'Loans' => 'loans',
    'Taxes Payable' => 'taxes_payable',
];

// === AUTO UPDATE FROM JOURNAL ENTRIES ===
function updateBalanceSheetFromJournals($pdo, $clientId, $year)
{
    global $accountMap;

    // Create or get balance sheet
    $stmt = $pdo->prepare("SELECT id FROM balance_sheets WHERE client_id = ? AND YEAR(sheet_date) = ?");
    $stmt->execute([$clientId, $year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $balanceSheetId = $row['id'];
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO balance_sheets (client_id, sheet_date) VALUES (?, ?)");
        $insertStmt->execute([$clientId, "$year-12-31"]);
        $balanceSheetId = $pdo->lastInsertId();
    }

    // Gather balances
    $sql = "
        SELECT a.name, SUM(l.debit - l.credit) as net_amount
        FROM journal_entries e
        JOIN journal_entry_lines l ON e.id = l.journal_entry_id
        JOIN chart_of_accounts a ON l.account_id = a.id
        WHERE e.client_id = ? AND YEAR(e.date) = ?
        GROUP BY a.name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clientId, $year]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateFields = [];
    foreach ($updates as $row) {
        $field = $accountMap[$row['name']] ?? null;
        if ($field) {
            $updateFields[$field] = floatval($row['net_amount']);
        }
    }

    // Update balance sheet
    if (!empty($updateFields)) {
        $setClause = [];
        $values = [];

        foreach ($updateFields as $field => $amount) {
            $setClause[] = "$field = ?";
            $values[] = $amount;
        }

        $values[] = $balanceSheetId;
        $sql = "UPDATE balance_sheets SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
}

// === MAIN EXECUTION ===
$clientId = $_POST['client_id'] ?? null;
if (!$clientId) {
    die("No client selected.");
}

// Get journal years for client
$stmt = $pdo->prepare("SELECT DISTINCT YEAR(date) as year FROM journal_entries WHERE client_id = ?");
$stmt->execute([$clientId]);
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($years as $year) {
    updateBalanceSheetFromJournals($pdo, $clientId, $year);
}

header("Location: ../balance_sheet.php");
exit;
