<?php
function getChartOfAccounts($pdo) {
    $stmt = $pdo->query("SELECT * FROM chart_of_accounts");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateBalanceSheetFromJournals($pdo, $clientId, $year) {
    // 1. Create or get existing balance sheet
    $stmt = $pdo->prepare("SELECT id FROM balance_sheets WHERE client_id = ? AND year = ?");
    $stmt->execute([$clientId, $year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $balanceSheetId = $row['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO balance_sheets (client_id, year) VALUES (?, ?)");
        $stmt->execute([$clientId, $year]);
        $balanceSheetId = $pdo->lastInsertId();
    }

    // 2. Sum journal entry lines per relevant account
    $accountMap = [
        'Cash' => 'cash',
        'Accounts Receivable' => 'receivables',
        'Inventory' => 'inventory',
        'Equipment' => 'equipment',
        'Accounts Payable' => 'accounts_payable',
        'Loans' => 'loans',
        'Taxes Payable' => 'taxes_payable',
    ];

    $sql = "
        SELECT a.name, 
            SUM(l.debit - l.credit) as net_amount
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

    // 3. Perform update
    if (!empty($updateFields)) {
        $setClause = [];
        $values = [];

        foreach ($updateFields as $field => $amount) {
            $setClause[] = "$field = ?";
            $values[] = $amount;
        }

        $values[] = $balanceSheetId;
        $updateSQL = "UPDATE balance_sheets SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $pdo->prepare($updateSQL);
        $stmt->execute($values);
    }
}


?>
