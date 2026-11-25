<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/stockFunctions.php';

use MongoDB\BSON\ObjectId;

try {
    $incomeId = new ObjectId($_POST['id'] ?? '');
    $incomesCollection->deleteOne(['_id' => $incomeId]);
    $stockCollection->deleteMany(['incomeId' => $incomeId]);
} catch (Throwable $exception) {
    // Invalid or missing IDs leave income data unchanged.
}
header('Location: ../incomes.php');
exit;
