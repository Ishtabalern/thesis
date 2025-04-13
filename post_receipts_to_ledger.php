<?php
// Start session to check if the user is logged in
session_start();

// Check if the user is logged in as an employee
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

// Database credentials
$host = "localhost";
$dbname = "cskdb";
$username = "admin";
$password = "123";

// Set up PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $pdo->beginTransaction();

    // Step 1: Fetch unposted receipts
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
        $category = strtolower($receipt['category']); // lowercase for consistency
        echo "Category: $category | Debit: $debit_name | Credit: $credit_name<br>";


        // Step 2: Determine debit and credit accounts
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
            default:
                echo "Skipping unknown category: $category<br>";
                continue 2;
        }
        

        // Step 3: Get account IDs
        $getAccountId = $pdo->prepare("SELECT id FROM chart_of_accounts WHERE name = ? AND client_id = ?");

        $getAccountId->execute([$debit_name, $client_id]);
        $debit_id = $getAccountId->fetchColumn();

        $getAccountId->execute([$credit_name, $client_id]);
        $credit_id = $getAccountId->fetchColumn();

        if (!$debit_id || !$credit_id) {
            continue; // Skip if account IDs are missing
        }

        // Step 4: Insert journal entry
        $insertEntry = $pdo->prepare("INSERT INTO journal_entries (date, description, client_id) VALUES (?, ?, ?)");
        $insertEntry->execute([$date, ucfirst($category) . " from receipt #$receipt_id", $client_id]);
        $entry_id = $pdo->lastInsertId();

        // Step 5: Insert journal lines
        $insertLine = $pdo->prepare("INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, ?)");
        $insertLine->execute([$entry_id, $debit_id, $amount, 0]);
        $insertLine->execute([$entry_id, $credit_id, 0, $amount]);

        // Step 6: Mark receipt as posted
        $markPosted = $pdo->prepare("UPDATE receipts SET posted_to_ledger = 1 WHERE id = ?");
        $markPosted->execute([$receipt_id]);
    }

    $pdo->commit();
    echo "Receipts posted to ledger successfully.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error posting receipts: " . $e->getMessage();
}
?>
