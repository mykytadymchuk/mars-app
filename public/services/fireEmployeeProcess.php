<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) { header("Location: ../login.php"); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$employeeId = $_POST["id"] ?? null;
try {
    $employeeObjectId = new ObjectId($employeeId);
    $employee = $employeesCollection->findOne(["_id" => $employeeObjectId]);
    if (!$employee) { header("Location: ../employees.php"); exit; }
    $employeesCollection->updateOne(["_id" => $employeeObjectId], ['$set' => ["Статус" => "Звільнено"]]);
    header("Location: ../employees.php");
    exit;
} catch (Exception $e) {
    header("Location: ../editEmployee.php?id=" . urlencode($employeeId) . "&error=unknown_error");
    exit;
}
