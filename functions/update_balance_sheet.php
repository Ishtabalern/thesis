<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
function updateBalanceSheetFromReceipt($pdo, $receipt, $clientId) {
    $date = new DateTime($receipt['date']);
    $year = $date->format('Y');
    $total = floatval($receipt['total']);
    $category = $receipt['category'];
    $paymentMethod = $receipt['payment_method'];

    // Get or create balance sheet
    $stmt = $pdo->prepare("SELECT id FROM balance_sheets WHERE client_id = ? AND year = ?");
    $stmt->execute([$clientId, $year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $balanceSheetId = $row['id'];
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO balance_sheets (client_id, year) VALUES (?, ?)");
        $insertStmt->execute([$clientId, $year]);
        $balanceSheetId = $pdo->lastInsertId();
    }

    // Determine balance sheet impact
    $updateFields = [];

    if ($category === "Inventory") {
        $updateFields['inventory'] = $total;
        if ($paymentMethod === "Credit") {
            $updateFields['accounts_payable'] = $total;
        } elseif ($paymentMethod === "Cash") {
            $updateFields['cash'] = -$total;
        }

    } elseif ($category === "Equipment") {
        $updateFields['equipment'] = $total;
        if ($paymentMethod === "Cash") {
            $updateFields['cash'] = -$total;
        }

    } elseif ($category === "Loan Payment") {
        $updateFields['loans'] = -$total;
        $updateFields['cash'] = -$total;

    } elseif ($category === "Tax Payment") {
        $updateFields['taxes_payable'] = -$total;
        $updateFields['cash'] = -$total;
    }

    // Prepare dynamic update query
    if (!empty($updateFields)) {
        $setClause = [];
        $values = [];

        foreach ($updateFields as $field => $value) {
            $setClause[] = "$field = $field + ?";
            $values[] = $value;
        }

        $values[] = $balanceSheetId;
        $sql = "UPDATE balance_sheets SET " . implode(', ', $setClause) . " WHERE id = ?";
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($values);
    }
}


$clientId = $_POST['client_id'] ?? null;

if (!$clientId) {
    die("No client selected.");
}

// Get all receipts for this client
$stmt = $pdo->prepare("SELECT * FROM receipts WHERE client_id = ?");
$stmt->execute([$clientId]);
$receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Loop through and update the balance sheet
foreach ($receipts as $receipt) {
    updateBalanceSheetFromReceipt($pdo, $receipt, $clientId);
}

header("Location: ../balance_sheet.php");
exit;
?>
