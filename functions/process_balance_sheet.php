<?php
require 'functions/helper_functions.php';

$clientId = $_POST['client_id'] ?? null;
$year = $_POST['year'] ?? date('Y');

if (!$clientId) {
    die("No client selected.");
}

updateBalanceSheetFromJournals($pdo, $clientId, $year);
header("Location: ../balance_sheet.php");
exit;

?>