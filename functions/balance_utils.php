<?php
function updateBalanceSheetFromReceipt($pdo, $receipt, $clientId) {
    $category = strtolower($receipt['category']);
    $type = strtolower($receipt['type']);
    $total = floatval($receipt['total']);

    // Get current values
    $stmt = $pdo->prepare("SELECT * FROM balance_sheets WHERE client_id = ? AND year = ?");
    $year = date('Y', strtotime($receipt['date']));
    $stmt->execute([$clientId, $year]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $fields = [
            'client_id' => $clientId,
            'year' => $year,
            'cash' => 0, 'receivables' => 0, 'inventory' => 0,
            'equipment' => 0, 'other_assets' => 0,
            'accounts_payable' => 0, 'loans' => 0,
            'taxes_payable' => 0, 'other_liabilities' => 0
        ];
    } else {
        $fields = $existing;
    }

    // Categorize logic
    if ($type === 'income') {
        $fields['cash'] += $total;
    } elseif ($type === 'expense') {
        switch ($category) {
            case 'inventory':
                $fields['inventory'] += $total;
                break;
            case 'equipment':
                $fields['equipment'] += $total;
                break;
            case 'utilities':
            case 'rent':
                $fields['accounts_payable'] += $total;
                break;
            case 'tax':
                $fields['taxes_payable'] += $total;
                break;
            default:
                $fields['other_liabilities'] += $total;
        }
    }

    // Upsert into balance_sheets
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $updates = implode(', ', array_map(fn($k) => "$k = VALUES($k)", array_keys($fields)));

    $stmt = $pdo->prepare("
        INSERT INTO balance_sheets (" . implode(', ', array_keys($fields)) . ")
        VALUES ($placeholders)
        ON DUPLICATE KEY UPDATE $updates
    ");
    $stmt->execute(array_values($fields));
}
?>
