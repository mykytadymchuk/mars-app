<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';

$name = trim($_POST['materialName'] ?? '');
$type = $_POST['materialType'] ?? '';
$fieldNames = $_POST['customFieldName'] ?? [];
$fieldValues = $_POST['customFieldValue'] ?? [];
if ($name === '') { header('Location: ../addMaterial.php?error=empty_name'); exit; }
if (!in_array($type, ['roll', 'sheet'], true)) { header('Location: ../addMaterial.php?error=invalid_type'); exit; }

$parameters = [];
foreach ($fieldNames as $index => $fieldName) {
    $fieldName = trim((string) $fieldName);
    $values = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), (array) ($fieldValues[$index] ?? [])), static fn ($value) => $value !== ''));
    if ($fieldName === '' && $values === []) continue;
    if ($fieldName === '' || $values === []) { header('Location: ../addMaterial.php?error=invalid_parameter'); exit; }
    $parameters[] = ['name' => $fieldName, 'values' => $values];
}
try {
    $materialsCollection->insertOne(['name' => $name, 'type' => $type, 'parameters' => $parameters, 'createdAt' => new MongoDB\BSON\UTCDateTime()]);
    header('Location: ../materials.php'); exit;
} catch (Throwable $exception) {
    header('Location: ../addMaterial.php?error=unknown_error'); exit;
}
