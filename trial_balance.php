<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: sign-in.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cskdb", "admin", "123");

// Get client dropdown
$clients = $pdo->query("SELECT id, name FROM clients")->fetchAll(PDO::FETCH_ASSOC);

// Get selected client and date
$clientId = $_GET['client_id'] ?? ($_SESSION['client_id'] ?? 1);
$asOfDate = $_GET['as_of'] ?? date('Y-m-d');

// Query journal entry lines grouped by account
$sql = "
SELECT 
    a.name AS account_name,
    SUM(jel.debit) AS total_debit,
    SUM(jel.credit) AS total_credit
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN chart_of_accounts a ON jel.account_id = a.id
WHERE je.client_id = ? AND je.date <= ?
GROUP BY a.name
ORDER BY a.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$clientId, $asOfDate]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>🧾 Trial Balance</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 40px;
            background-color: #fefefe;
        }

        .receipt {
            width: 400px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border: 2px dashed #333;
        }

        .receipt h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt .line {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #ccc;
        }

        .receipt .bold {
            font-weight: bold;
        }

        .form-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-bar form {
            display: inline-block;
        }

        .totals {
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        button {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="form-bar">
    <form method="GET">
        <label>Client:
            <select name="client_id">
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= $client['id'] == $clientId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($client['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>As of:
            <input type="date" name="as_of" value="<?= htmlspecialchars($asOfDate) ?>">
        </label>

        <button type="submit">📄 View</button>
    </form>
</div>

<div class="receipt">
    <h2>📜 Trial Balance</h2>
    <p style="text-align:center">As of <?= htmlspecialchars($asOfDate) ?></p>
    <hr>

    <?php
    $totalDebit = 0;
    $totalCredit = 0;

    foreach ($entries as $entry):
        $debit = floatval($entry['total_debit']);
        $credit = floatval($entry['total_credit']);
        $totalDebit += $debit;
        $totalCredit += $credit;
    ?>
        <div class="line">
            <span><?= htmlspecialchars($entry['account_name']) ?></span>
            <span>
                <?= $debit > 0 ? '₱' . number_format($debit, 2) : '' ?>
                <?= $credit > 0 ? ' | ₱' . number_format($credit, 2) : '' ?>
            </span>
        </div>
    <?php endforeach; ?>

    <div class="totals line bold">
        <span>Total</span>
        <span>₱<?= number_format($totalDebit, 2) ?> | ₱<?= number_format($totalCredit, 2) ?></span>
    </div>

    <p style="text-align:center; margin-top: 10px;">
        <?= $totalDebit == $totalCredit ? '✅ Balanced' : '⚠️ Not Balanced' ?>
    </p>
</div>

</body>
</html>
