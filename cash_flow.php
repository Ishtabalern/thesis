<?php
// Start session to check if the user is logged in
session_start();

// Check if the user is logged in as an employee
if (!isset($_SESSION['logged_in']) && !isset($_SESSION['logged_in'])) {
    // If not logged in, redirect to login page
    header("Location: sign-in.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "admin";  // Change if necessary
$password = "123";     // Change if necessary
$dbname = "cskdb";     // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$clients = mysqli_query($conn, "SELECT id, name FROM clients");

$selected_client = $_GET['client_id'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');

// Fetch Net Income from income_statements
$net_income = 0;

if ($selected_client && $selected_year) {
    $stmt = $conn->prepare("SELECT sales_revenue, other_income, cogs, salaries, rent, utilities, other_expenses 
                            FROM income_statements 
                            WHERE client_id = ? AND statement_date = ?");

    if ($stmt) {
        $stmt->bind_param("ii", $selected_client, $selected_year);
        $stmt->execute();
        $stmt->bind_result($sales, $other, $cogs, $salaries, $rent, $utilities, $others);
        if ($stmt->fetch()) {
            $totalRevenue = $sales + $other;
            $totalExpenses = $cogs + $salaries + $rent + $utilities + $others;
            $net_income = $totalRevenue - $totalExpenses;
        }
        $stmt->close();
    }
}


// Fetch Owner's Equity details (investment & withdrawals)
$investment = $withdrawal = 0;
if ($selected_client && $selected_year) {
    $stmt = $conn->prepare("SELECT additional_investment, withdrawals FROM owners_equity WHERE client_id = ? AND year = ?");
    $stmt->bind_param("is", $selected_client, $selected_year);
    $stmt->execute();
    $stmt->bind_result($investment, $withdrawal);
    $stmt->fetch();
    $stmt->close();
}

// Calculate totals
$cash_from_operating = $net_income;
$cash_from_financing = $investment - $withdrawal;
$net_cash_flow = $cash_from_operating + $cash_from_financing;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cash Flow Statement</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .cash-flow-box { border: 1px solid #ccc; padding: 20px; max-width: 700px; margin: auto; }
        h2 { text-align: center; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        td, th { padding: 8px; border-bottom: 1px solid #ddd; }
        .label { font-weight: bold; }
        .amount { text-align: right; }
    </style>
</head>
<body>

<div class="cash-flow-box">
    <h2>Cash Flow Statement - <?= htmlspecialchars($selected_year) ?></h2>

    <form method="GET">
        <label>Client:</label>
        <select name="client_id" onchange="this.form.submit()">
            <option value="">Select Client</option>
            <?php while ($row = mysqli_fetch_assoc($clients)) { ?>
                <option value="<?= $row['id'] ?>" <?= ($selected_client == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php } ?>
        </select>

        <label style="margin-left: 20px;">Year:</label>
        <input type="number" name="year" value="<?= htmlspecialchars($selected_year) ?>" onchange="this.form.submit()" />
    </form>

    <?php if ($selected_client && $selected_year): ?>
    <table>
        <tr><td colspan="2" class="label">Cash Flows from Operating Activities</td></tr>
        <tr>
            <td>Net Income</td>
            <td class="amount">$<?= number_format($net_income, 2) ?></td>
        </tr>
        <tr><td colspan="2" class="label">Cash Flows from Financing Activities</td></tr>
        <tr>
            <td>Owner Investment</td>
            <td class="amount">$<?= number_format($investment, 2) ?></td>
        </tr>
        <tr>
            <td>Owner Withdrawals</td>
            <td class="amount">($<?= number_format($withdrawal, 2) ?>)</td>
        </tr>
        <tr class="label">
            <td>Net Cash Flow</td>
            <td class="amount">$<?= number_format($net_cash_flow, 2) ?></td>
        </tr>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
