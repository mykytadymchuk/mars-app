<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$supplierId = $_POST['supplierId'] ?? '';
$materialName = trim($_POST['materialName'] ?? '');
$incomeId = $_POST['incomeId'] ?? '';
try { $supplierObjectId = new ObjectId($supplierId); } catch (Throwable $exception) { header('Location: ../addIncome.php?error=unknown_error'); exit; }
$supplier = $suppliersCollection->findOne(['_id' => $supplierObjectId]);
if (!$supplier) { header('Location: ../addIncome.php?error=missing_supplier'); exit; }
$materials = $materialsCollection->find(['name' => $materialName])->toArray();
if ($materialName === '' || empty($materials)) { header('Location: ../addIncome.php?error=missing_material'); exit; }
$incomeDate = $_POST['incomeDate'] ?? '';
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $incomeDate);
if (!$date || $date->format('Y-m-d') !== $incomeDate) { header('Location: ../addIncome.php?error=invalid_date'); exit; }
$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($quantity === false) { header('Location: ../addIncome.php?error=invalid_quantity'); exit; }
$values = $_POST['parameters'] ?? [];
$names = $_POST['parameterNames'] ?? [];
$availableParameters = [];
$materialIds = [];
foreach ($materials as $material) {
    $materialIds[] = $material['_id'];
    foreach (($material['parameters'] ?? []) as $parameter) {
        $name = trim((string) ($parameter['name'] ?? ''));
        if ($name === '') continue;
        if (!isset($availableParameters[$name])) $availableParameters[$name] = [];
        foreach (($parameter['values'] ?? []) as $value) $availableParameters[$name][(string) $value] = (string) $value;
    }
}
$parameters = [];
$postedValues = [];
foreach ($names as $position => $name) $postedValues[(string) $name] = trim((string) ($values[$position] ?? ''));
foreach ($availableParameters as $name => $availableValues) {
    $value = $postedValues[$name] ?? '';
    if ($value === '' || !isset($availableValues[$value])) { header('Location: ../addIncome.php?error=invalid_parameters'); exit; }
    $parameters[] = ['name' => $name, 'value' => $value];
}
try {
    $income = ['supplierId' => $supplierObjectId, 'supplierName' => $supplier['Постачальник'] ?? '', 'supplierPhoto' => $supplier['Фото'] ?? '', 'materialIds' => $materialIds, 'materialName' => $materialName, 'parameters' => $parameters, 'quantity' => $quantity, 'incomeDate' => $incomeDate];
    if ($incomeId === '') {
        $income['createdAt'] = new MongoDB\BSON\UTCDateTime();
        $incomesCollection->insertOne($income);
    } else {
        try { $incomeObjectId = new ObjectId($incomeId); } catch (Throwable $exception) { header('Location: ../incomes.php'); exit; }
        $income['updatedAt'] = new MongoDB\BSON\UTCDateTime();
        $result = $incomesCollection->updateOne(['_id' => $incomeObjectId], ['$set' => $income]);
        if ($result->getMatchedCount() === 0) { header('Location: ../incomes.php'); exit; }
    }
    header('Location: ../incomes.php'); exit;
} catch (Throwable $exception) { header('Location: ../addIncome.php?error=unknown_error'); exit; }
