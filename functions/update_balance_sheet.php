<?php
require_once '../config.php'; // adjust path as needed

$client_id = $_POST['client_id'] ?? null;

if (!$client_id) {
    exit("Missing client ID.");
}

$year = date('Y');

// Step 1: Fetch account balances from journal_entry_lines
$sql = "
    SELECT coa.name AS account_name, coa.account_type_id, 
           SUM(jel.debit) AS total_debit, 
           SUM(jel.credit) AS total_credit
    FROM journal_entries je
    JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
    JOIN chart_of_accounts coa ON coa.id = jel.account_id
    WHERE je.client_id = :client_id AND YEAR(je.date) = :year
    GROUP BY coa.name, coa.account_type_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['client_id' => $client_id, 'year' => $year]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// No data? Exit to avoid inserting a blank record
if (empty($entries)) {
    exit("No journal data found for this client/year.");
}

// Step 2: Map data to balance sheet fields
$data = [
    'cash' => 0,
    'receivables' => 0,
    'inventory' => 0,
    'equipment' => 0,
    'other_assets' => 0,
    'accounts_payable' => 0,
    'loans' => 0,
    'taxes_payable' => 0,
    'other_liabilities' => 0,
];

foreach ($entries as $row) {
    $balance = ($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0);
    $name = strtolower($row['account_name']);

    switch ($name) {
        case 'cash':
            $data['cash'] += $balance;
            break;
        case 'accounts receivable':
            $data['receivables'] += $balance;
            break;
        case 'inventory':
            $data['inventory'] += $balance;
            break;
        case 'equipment':
            $data['equipment'] += $balance;
            break;
        case 'accounts payable':
            $data['accounts_payable'] += abs($balance);
            break;
        case 'loans':
            $data['loans'] += abs($balance);
            break;
        case 'taxes payable':
            $data['taxes_payable'] += abs($balance);
            break;
        default:
            // Auto-classify others
            if (in_array($row['account_type_id'], [1])) {
                $data['other_assets'] += $balance;
            } elseif (in_array($row['account_type_id'], [2, 3])) {
                $data['other_liabilities'] += abs($balance);
            }
            break;
    }
}

// Step 3: Check if balance sheet already exists
$check = $pdo->prepare("SELECT id FROM balance_sheets WHERE client_id = :client_id AND YEAR(sheet_date) = :year");
$check->execute(['client_id' => $client_id, 'year' => $year]);
$existing = $check->fetchColumn();

if ($existing) {
    // Update
    $sql = "UPDATE balance_sheets SET
                cash = :cash,
                receivables = :receivables,
                inventory = :inventory,
                equipment = :equipment,
                other_assets = :other_assets,
                accounts_payable = :accounts_payable,
                loans = :loans,
                taxes_payable = :taxes_payable,
                other_liabilities = :other_liabilities
            WHERE client_id = :client_id AND YEAR(sheet_date) = :year";
} else {
    // Insert
    $sql = "INSERT INTO balance_sheets (
                client_id, sheet_date,
                cash, receivables, inventory, equipment, other_assets,
                accounts_payable, loans, taxes_payable, other_liabilities
            ) VALUES (
                :client_id, :sheet_date,
                :cash, :receivables, :inventory, :equipment, :other_assets,
                :accounts_payable, :loans, :taxes_payable, :other_liabilities
            )";
}

// Step 4: Run query
$stmt = $pdo->prepare($sql);
$params = array_merge($data, [
    'client_id' => $client_id,
    'sheet_date' => $year . '-12-31',
    'year' => $year
]);
$stmt->execute($params);

header("Location: ../balance_sheet.php?client_id=$client_id&updated=1");
exit;
