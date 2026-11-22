<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$id = $_POST['id'] ?? '';
$back = '../editMaterial.php?id=' . urlencode($id);
try { $materialId = new ObjectId($id); } catch (Throwable $exception) { header('Location: ../materials.php'); exit; }
$name = trim($_POST['materialName'] ?? '');
$type = $_POST['materialType'] ?? '';
if ($name === '') { header("Location: $back&error=empty_name"); exit; }
if (!in_array($type, ['roll', 'sheet'], true)) { header("Location: $back&error=invalid_type"); exit; }
$parameters = [];
foreach (($_POST['customFieldName'] ?? []) as $index => $fieldName) {
    $fieldName = trim((string) $fieldName);
    $values = array_values(array_filter(array_map(static fn($value) => trim((string) $value), (array) (($_POST['customFieldValue'] ?? [])[$index] ?? [])), static fn($value) => $value !== ''));
    if ($fieldName === '' && $values === []) continue;
    if ($fieldName === '' || $values === []) { header("Location: $back&error=invalid_parameter"); exit; }
    $parameters[] = ['name' => $fieldName, 'values' => $values];
}
try {
    $result = $materialsCollection->updateOne(['_id' => $materialId], ['$set' => ['name' => $name, 'type' => $type, 'parameters' => $parameters, 'updatedAt' => new MongoDB\BSON\UTCDateTime()]]);
    if ($result->getMatchedCount() === 0) { header('Location: ../materials.php'); exit; }
    header('Location: ../materials.php'); exit;
} catch (Throwable $exception) { header("Location: $back&error=unknown_error"); exit; }
