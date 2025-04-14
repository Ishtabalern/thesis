<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

require 'helper_functions.php'; // optional helper file

function processReceiptToBalanceSheet($pdo, $receiptId) {
    // 1. Fetch receipt
    $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = ?");
    $stmt->execute([$receiptId]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        echo "Receipt not found.";
        return;
    }

    $date = $receipt['date'];
    $year = date('Y', strtotime($date));
    $total = floatval($receipt['total']);
    $category = $receipt['category'];
    $paymentMethod = $receipt['payment_method'];
    $clientId = $receipt['client_id'];

    // 2. Create Journal Entry
    $desc = "Auto entry from receipt ID #{$receiptId}";
    $insertJournal = $pdo->prepare("INSERT INTO journal_entries (date, description, client_id, reference_id) VALUES (?, ?, ?, ?)");
    $insertJournal->execute([$date, $desc, $clientId, $receiptId]);
    $journalEntryId = $pdo->lastInsertId();

    // 3. Map accounts
    $accounts = getChartOfAccounts($pdo); // pulls all chart_of_accounts rows

    // Helper: resolve account by name
    function accountId($name, $accounts) {
        foreach ($accounts as $acc) {
            if (strcasecmp($acc['name'], $name) === 0) return $acc['id'];
        }
        return null;
    }

    // 4. Journal logic based on category
    $lines = [];

    switch ($category) {
        case 'Inventory':
            $lines[] = ['account' => 'Inventory', 'debit' => $total, 'credit' => 0];
            if ($paymentMethod === 'Cash') {
                $lines[] = ['account' => 'Cash', 'debit' => 0, 'credit' => $total];
            } else {
                $lines[] = ['account' => 'Accounts Payable', 'debit' => 0, 'credit' => $total];
            }
            break;

        case 'Equipment':
            $lines[] = ['account' => 'Equipment', 'debit' => $total, 'credit' => 0];
            $lines[] = ['account' => 'Cash', 'debit' => 0, 'credit' => $total];
            break;

        case 'Loan Payment':
            $lines[] = ['account' => 'Loans', 'debit' => $total, 'credit' => 0];
            $lines[] = ['account' => 'Cash', 'debit' => 0, 'credit' => $total];
            break;

        case 'Tax Payment':
            $lines[] = ['account' => 'Taxes Payable', 'debit' => $total, 'credit' => 0];
            $lines[] = ['account' => 'Cash', 'debit' => 0, 'credit' => $total];
            break;

        default:
            // General Expense or Revenue (like "Rent Expense", "Sales Revenue", etc.)
            $lines[] = ['account' => $category, 'debit' => $total, 'credit' => 0];
            $lines[] = ['account' => 'Cash', 'debit' => 0, 'credit' => $total];
            break;
    }

    // 5. Insert Journal Lines
    $insertLine = $pdo->prepare("INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");

    foreach ($lines as $line) {
        $accountId = accountId($line['account'], $accounts);
        if ($accountId) {
            $insertLine->execute([$journalEntryId, $accountId, $line['debit'], $line['credit']]);
        }
    }

    // 6. Update or create balance sheet
    updateBalanceSheetFromJournals($pdo, $clientId, $year);
    echo "Journal entry and balance sheet updated from receipt.";
}

// Call it here (e.g., via button GET param ?receipt_id=123)
if (isset($_GET['receipt_id'])) {
    processReceiptToBalanceSheet($pdo, $_GET['receipt_id']);
}
?>
