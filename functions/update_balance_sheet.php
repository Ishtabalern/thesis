<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $year = $_POST['year'];
    $sheet_date = "$year-12-31";

    // Fetch computed values from journal entries (same as before)
    $stmt = $pdo->prepare("
        SELECT jel.account_id, coa.name AS account_name, coa.account_type_id,
               SUM(jel.debit) AS total_debit, SUM(jel.credit) AS total_credit
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN chart_of_accounts coa ON jel.account_id = coa.id
        WHERE je.client_id = ? AND YEAR(je.date) = ?
        GROUP BY jel.account_id
    ");
    $stmt->execute([$client_id, $year]);
    $accountSums = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $balances = [
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

    foreach ($accountSums as $row) {
        $net = $row['total_debit'] - $row['total_credit'];
        $name = strtolower($row['account_name']);

        if (strpos($name, 'cash') !== false) $balances['cash'] += $net;
        elseif (strpos($name, 'receivable') !== false) $balances['receivables'] += $net;
        elseif (strpos($name, 'inventory') !== false) $balances['inventory'] += $net;
        elseif (strpos($name, 'equipment') !== false) $balances['equipment'] += $net;
        elseif ($row['account_type_id'] == 1) $balances['other_assets'] += $net;
        elseif (strpos($name, 'payable') !== false && strpos($name, 'tax') === false) $balances['accounts_payable'] += abs($net);
        elseif (strpos($name, 'loan') !== false) $balances['loans'] += abs($net);
        elseif (strpos($name, 'tax') !== false) $balances['taxes_payable'] += abs($net);
        elseif ($row['account_type_id'] == 2) $balances['other_liabilities'] += abs($net);
    }

    // Override with manual values if provided
    foreach ($balances as $key => $value) {
        if (isset($_POST[$key]) && $_POST[$key] !== '') {
            $balances[$key] = floatval($_POST[$key]);
        }
    }

    // Check for existing record
    $stmt = $pdo->prepare("SELECT id FROM balance_sheets WHERE client_id = ? AND YEAR(sheet_date) = ?");
    $stmt->execute([$client_id, $year]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE balance_sheets SET
            sheet_date = ?, cash = ?, receivables = ?, inventory = ?, equipment = ?, other_assets = ?,
            accounts_payable = ?, loans = ?, taxes_payable = ?, other_liabilities = ?
            WHERE client_id = ? AND YEAR(sheet_date) = ?");
        $stmt->execute([
            $sheet_date,
            $balances['cash'], $balances['receivables'], $balances['inventory'], $balances['equipment'], $balances['other_assets'],
            $balances['accounts_payable'], $balances['loans'], $balances['taxes_payable'], $balances['other_liabilities'],
            $client_id, $year
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO balance_sheets (
            client_id, sheet_date, cash, receivables, inventory, equipment, other_assets,
            accounts_payable, loans, taxes_payable, other_liabilities
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $client_id, $sheet_date,
            $balances['cash'], $balances['receivables'], $balances['inventory'], $balances['equipment'], $balances['other_assets'],
            $balances['accounts_payable'], $balances['loans'], $balances['taxes_payable'], $balances['other_liabilities']
        ]);
    }

    header("Location: ../balance_sheet.php?client_id=$client_id&year=$year&success=1");
    exit();
}
