<?php

session_start();

require_once __DIR__ . '/../../services/databaseConnect.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: /../login.php");
    exit;
}

$suppliersName = trim($_POST["suppliersName"] ?? "");
$suppliersPhone = trim($_POST["suppliersPhone"] ?? "");

// Validate supplier name
if ($suppliersName === "") {
    header("Location: /../addSupplier.php?error=empty_suppliers_name");
    exit;
}

// Validate phone
if (!preg_match('/^\+380\d{9}$/', $suppliersPhone)) {
    header("Location: /../addSupplier.php?error=incorrect_phone_number_format");
    exit;
}

try {
    // Check whether supplier with this phone already exists
    $existingSupplier = $suppliersCollection->findOne([
        "Номер телефону" => $suppliersPhone
    ]);

    if ($existingSupplier) {
        header("Location: /../addSupplier.php?error=supplier_exists");
        exit;
    }

    // Create supplier document
    $supplier = [
        "Постачальник" => $suppliersName,
        "Номер телефону" => $suppliersPhone
    ];

    $suppliersCollection->insertOne($supplier);

    header("Location: /../suppliers.php");
    exit;

} catch (Exception $e) {
    header("Location: /../addSupplier.php?error=unkown_error");
    exit;
}