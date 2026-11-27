<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/orderFunctions.php';
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user || !orderIsManager($user)) { header('Location: ../orders.php'); exit; }
$orderId = (string) ($_POST['orderId'] ?? '');
$existing = null;
if ($orderId !== '') {
    try { $objectId = new ObjectId($orderId); } catch (Throwable $e) { header('Location: ../orders.php'); exit; }
    $existing = $ordersCollection->findOne(['_id' => $objectId]);
    if (!$existing) { header('Location: ../orders.php'); exit; }
    if (!orderIsResponsibleManager($existing, $user) || (string) ($existing['status'] ?? '') === 'Готове') { header('Location: ../order.php?id=' . rawurlencode($orderId)); exit; }
} else {
    $objectId = new ObjectId();
}
$fail = static function (string $error) use ($orderId): never {
    header('Location: ../addOrder.php' . ($orderId !== '' ? '?id=' . rawurlencode($orderId) . '&error=' : '?error=') . rawurlencode($error));
    exit;
};
try {
    try { $customerObjectId = new ObjectId((string) ($_POST['customerId'] ?? '')); } catch (Throwable $e) { $fail('invalid_customer'); }
    $customer = $customersCollection->findOne(['_id' => $customerObjectId]);
    if (!$customer) $fail('invalid_customer');
    $postedItems = $_POST['items'] ?? [];
    if (!is_array($postedItems) || count($postedItems) < 1 || count($postedItems) > 30) $fail('invalid_items');
    $allowedPrintTypes = ['УФ-друк', 'Сольвентний друк', 'Еко-сольвентний друк'];
    $allowedPreparations = ['Різка на лазері', 'Різка на фрезері'];
    $allowedPostworks = ['Підвороти', 'Проклейка', 'Люверси', 'ОПК', 'Глянцева ламінація', 'Матова ламінація'];
    $materialsByName = [];
    $items = [];
    $sourceItems = [];
    foreach (array_values($postedItems) as $index => $posted) {
        $printType = trim((string) ($posted['printType'] ?? ''));
        $preparation = trim((string) ($posted['preparation'] ?? ''));
        $materialName = trim((string) ($posted['materialName'] ?? ''));
        if (!in_array($printType, $allowedPrintTypes, true) || ($preparation !== '' && !in_array($preparation, $allowedPreparations, true)) || $materialName === '') $fail('invalid_items');
        if (!isset($materialsByName[$materialName])) $materialsByName[$materialName] = $materialsCollection->find(['name' => $materialName])->toArray();
        if (!$materialsByName[$materialName]) $fail('invalid_items');
        $postedParameterValues = $posted['parameters'] ?? [];
        $postedParameterNames = $posted['parameterNames'] ?? [];
        if (!is_array($postedParameterValues) || !is_array($postedParameterNames)) $fail('invalid_items');
        $postedParameters = [];
        foreach ($postedParameterValues as $parameterIndex => $parameterValue) {
            $parameterName = trim((string) ($postedParameterNames[$parameterIndex] ?? ''));
            if ($parameterName === '') $fail('invalid_items');
            if (orderIsSizeParameter($parameterName)) continue;
            $postedParameters[$parameterName] = trim((string) $parameterValue);
        }
        $availableParameters = [];
        foreach ($materialsByName[$materialName] as $material) foreach (($material['parameters'] ?? []) as $parameter) {
            $parameterName = trim((string) ($parameter['name'] ?? ''));
            if ($parameterName === '' || orderIsSizeParameter($parameterName)) continue;
            if (!isset($availableParameters[$parameterName])) $availableParameters[$parameterName] = [];
            foreach (($parameter['values'] ?? []) as $value) $availableParameters[$parameterName][(string) $value] = true;
        }
        $cleanParameters = [];
        foreach ($availableParameters as $parameterName => $values) {
            $value = trim((string) ($postedParameters[$parameterName] ?? ''));
            if ($value === '' || !isset($values[$value])) $fail('invalid_items');
            $cleanParameters[] = ['name' => $parameterName, 'value' => $value];
        }
        foreach ($postedParameters as $parameterName => $value) if (!array_key_exists((string) $parameterName, $availableParameters)) $fail('invalid_items');
        $validMaterial = $materialsByName[$materialName][0];
        $bestMaterialScore = -1;
        foreach ($materialsByName[$materialName] as $material) {
            $candidateValues = [];
            foreach (($material['parameters'] ?? []) as $parameter) {
                $values = [];
                foreach (($parameter['values'] ?? []) as $value) $values[] = (string) $value;
                $candidateValues[(string) ($parameter['name'] ?? '')] = $values;
            }
            $matches = true;
            $score = 0;
            foreach ($cleanParameters as $parameter) {
                if (isset($candidateValues[$parameter['name']])) {
                    if (!in_array($parameter['value'], $candidateValues[$parameter['name']], true)) $matches = false;
                    else $score++;
                }
            }
            if ($matches && $score > $bestMaterialScore) { $validMaterial = $material; $bestMaterialScore = $score; }
        }
        $postworks = array_values(array_unique(array_intersect($allowedPostworks, array_map('strval', (array) ($posted['postworks'] ?? [])))));
        $items[] = [
            'printType' => $printType,
            'preparation' => $preparation,
            'materialId' => $validMaterial['_id'],
            'materialName' => $materialName,
            'materialType' => (string) ($validMaterial['type'] ?? ''),
            'parameters' => $cleanParameters,
            'postworks' => $postworks,
            'designerComment' => trim((string) ($posted['designerComment'] ?? '')),
            'printerComment' => trim((string) ($posted['printerComment'] ?? '')),
            'postworkerComment' => trim((string) ($posted['postworkerComment'] ?? ''))
        ];
        $sourceIndex = filter_var($posted['existingIndex'] ?? null, FILTER_VALIDATE_INT);
        $sourceItems[] = $existing && $sourceIndex !== false && isset($existing['items'][$sourceIndex]) ? $existing['items'][$sourceIndex] : null;
    }

    $uploadRoot = __DIR__ . '/../src/orders/' . (string) $objectId . '/';
    $fileChanges = [];
    foreach ($items as $index => &$item) {
        $sourceItem = $sourceItems[$index] ?? null;
        if ($sourceItem && isset($sourceItem['managerFile'])) $item['managerFile'] = $sourceItem['managerFile'];
        if ($sourceItem && isset($sourceItem['designerFile'])) $item['designerFile'] = $sourceItem['designerFile'];
        $upload = null;
        if (isset($_FILES['items']['name'][$index]['managerFile'])) {
            $upload = [
                'name' => $_FILES['items']['name'][$index]['managerFile'],
                'type' => $_FILES['items']['type'][$index]['managerFile'] ?? '',
                'tmp_name' => $_FILES['items']['tmp_name'][$index]['managerFile'] ?? '',
                'error' => $_FILES['items']['error'][$index]['managerFile'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['items']['size'][$index]['managerFile'] ?? 0
            ];
        }
        if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = receiveOrderFile($upload, $uploadRoot, 'manager-' . ($index + 1));
            $item['managerFile'] = $file;
            $fileChanges[] = 'Менеджер завантажив файл для позиції ' . ($index + 1) . ': ' . $file['originalName'];
        }
    }
    unset($item);

    $customerName = orderCustomerName($customer);
    $payload = [
        'customerId' => $customerObjectId,
        'customerName' => $customerName,
        'items' => $items
    ];
    if (!$existing) {
        $payload += [
            'status' => 'Створено',
            'createdAt' => new UTCDateTime(),
            'createdDate' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Kyiv')))->format('Y-m-d'),
            'createdBy' => ['userId' => $user['_id'], 'username' => (string) $user['Логін']],
            'history' => [orderLogEntry($user, 'Замовлення створено.')]
        ];
        foreach ($fileChanges as $change) $payload['history'][] = orderLogEntry($user, $change);
        $ordersCollection->insertOne(['_id' => $objectId] + $payload);
    } else {
        $changes = [];
        if ((string) ($existing['customerId'] ?? '') !== (string) $customerObjectId) $changes[] = 'змінено клієнта';
        if ((string) ($existing['customerName'] ?? '') !== $customerName) $changes[] = 'оновлено дані клієнта';
        $oldItems = json_encode($existing['items'] ?? [], JSON_UNESCAPED_UNICODE);
        $newItems = json_encode($items, JSON_UNESCAPED_UNICODE);
        if ($oldItems !== $newItems) $changes[] = 'оновлено позиції, коментарі або постобробку';
        $operations = ['$set' => $payload];
        if ($changes || $fileChanges) {
            $entries = [];
            if ($changes) $entries[] = orderLogEntry($user, 'Менеджер змінив замовлення: ' . implode(', ', $changes) . '.');
            foreach ($fileChanges as $change) $entries[] = orderLogEntry($user, $change);
            $operations['$push'] = ['history' => ['$each' => $entries]];
        }
        $ordersCollection->updateOne(['_id' => $objectId], $operations);
    }
    header('Location: ../order.php?id=' . rawurlencode((string) $objectId));
    exit;
} catch (Throwable $e) {
    $fail($e->getMessage() === 'file_too_large' ? 'file_too_large' : 'save_failed');
}
