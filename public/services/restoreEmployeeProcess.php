<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../services/databaseConnect.php';

use MongoDB\BSON\ObjectId;

$employeeId = $_POST["id"] ?? null;

try {
    $employeeObjectId = new ObjectId($employeeId);
    $employee = $employeesCollection->findOne(["_id" => $employeeObjectId]);
    if (!$employee) {
        header("Location: ../employees.php");
        exit;
    }

    $employeesCollection->updateOne(
        ["_id" => $employeeObjectId],
        ['$set' => ["\xD0\xA1\xD1\x82\xD0\xB0\xD1\x82\xD1\x83\xD1\x81" => "\xD0\x9F\xD1\x80\xD0\xB0\xD1\x86\xD1\x8E\xD1\x94"]]
    );

    header("Location: ../employees.php");
    exit;
} catch (Exception $e) {
    header("Location: ../employees.php");
    exit;
}
