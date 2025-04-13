<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$host = "localhost";
$dbname = "cskdb";
$username = "admin";
$password = "123";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    // Reusable function to get account ID with fallback
    function getAccountIdWithFallback($pdo, $account_name, $client_id) {
        $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE name = ? AND client_id = ?");
        $stmt->execute([$account_name, $client_id]);
        $account_id = $stmt->fetchColumn();

        if (!$account_id) {
            $stmt = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE name = ? AND client_id IS NULL");
            $stmt->execute([$account_name]);
            $account_id = $stmt->fetchColumn();
        }

        return $account_id;
    }
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT * FROM receipts WHERE posted_to_ledger = 0");
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($receipts)) {
        echo "No new receipts to post.";
        exit;
    }

    foreach ($receipts as $receipt) {
        $receipt_id = $receipt['id'];
        $client_id = $receipt['client_id'];
        $date = $receipt['date'];
        $amount = $receipt['total'];
        $category = strtolower(trim($receipt['category'])); // lowercase and trim

        if (!$date || !$amount || !$category) {
            echo "Skipping receipt #$receipt_id due to missing date, amount, or category.<br>";
            continue;
        }

        switch ($category) {
            case 'sales':
                $debit_name = 'Cash';
                $credit_name = 'Sales Revenue';
                break;
            case 'utilities':
                $debit_name = 'Utilities Expense';
                $credit_name = 'Cash';
                break;
            case 'supplies':
                $debit_name = 'Supplies Expense';
                $credit_name = 'Cash';
                break;
            case 'rent':
                $debit_name = 'Rent Expense';
                $credit_name = 'Cash';
                break;
            case 'clothing':
            case 'clothing expense':
                $debit_name = 'Clothing Expense';
                $credit_name = 'Cash';
                break;
            case 'gas':
            case 'gas expense':
                $debit_name = 'Fuel Expense';
                $credit_name = 'Cash';
                break;
            case 'luxury':
            case 'luxury expense':
                $debit_name = 'Luxury Expense';
                $credit_name = 'Cash';
                break;
            case 'food':
                $debit_name = 'Food & Beverage Expense';
                $credit_name = 'Cash';
                break;
            case 'wages':
                $debit_name = 'Wages Expense';
                $credit_name = 'Cash';
                break;
            default:
                echo "Skipping unknown category: '$category' in receipt #$receipt_id<br>";
                continue 2;
        }

        

        // Get debit and credit account IDs
        $debit_id = getAccountIdWithFallback($pdo, $debit_name, $client_id);
        $credit_id = getAccountIdWithFallback($pdo, $credit_name, $client_id);


        if (!$debit_id || !$credit_id) {
            echo "Skipping receipt #$receipt_id: Missing account IDs → Debit: $debit_id, Credit: $credit_id<br>";
            continue;
        }

        if (!$debit_id) {
            $createAccount = $pdo->prepare("INSERT INTO chart_of_accounts (name, account_type_id, description, client_id) VALUES (?, ?, ?, ?)");
            $createAccount->execute([$debit_name, 5, "$debit_name auto-created", $client_id]); // 5 is usually expense
            $debit_id = $pdo->lastInsertId();
            echo "🛠 Created missing debit account '$debit_name' for client $client_id<br>";
        }
        
        if (!$credit_id) {
            $createAccount = $pdo->prepare("INSERT INTO chart_of_accounts (name, account_type_id, description, client_id) VALUES (?, ?, ?, ?)");
            $createAccount->execute([$credit_name, 1, "$credit_name auto-created", $client_id]); // 1 is usually asset (Cash)
            $credit_id = $pdo->lastInsertId();
            echo "🛠 Created missing credit account '$credit_name' for client $client_id<br>";
        }
        

        // Insert journal entry
        $insertEntry = $pdo->prepare("INSERT INTO journal_entries (date, description, client_id) VALUES (?, ?, ?)");
        $insertEntry->execute([$date, ucfirst($category) . " from receipt #$receipt_id", $client_id]);
        $entry_id = $pdo->lastInsertId();

        // Insert lines
        $insertLine = $pdo->prepare("INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        $insertLine->execute([$entry_id, $debit_id, $amount, 0]);
        $insertLine->execute([$entry_id, $credit_id, 0, $amount]);

        // Update posted_to_ledger
        $markPosted = $pdo->prepare("UPDATE receipts SET posted_to_ledger = 1 WHERE id = ?");
        if ($markPosted->execute([$receipt_id])) {
            echo "✅ Posted receipt #$receipt_id to ledger.<br>";
        } else {
            echo "❌ Failed to mark receipt #$receipt_id as posted.<br>";
        }
    }

    $pdo->commit();
    echo "<br>🎉 All eligible receipts posted.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "🚨 Error posting receipts: " . $e->getMessage();
}
?>
