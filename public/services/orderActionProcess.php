<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/orderFunctions.php';
use MongoDB\BSON\ObjectId;

$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user) { session_destroy(); header('Location: ../login.php'); exit; }
$id = (string) ($_POST['id'] ?? '');
try { $objectId = new ObjectId($id); } catch (Throwable $e) { header('Location: ../orders.php'); exit; }
$order = $ordersCollection->findOne(['_id' => $objectId]);
if (!$order) { header('Location: ../orders.php'); exit; }
if (!orderCanView($order, $user)) { header('Location: ../orders.php'); exit; }
$action = (string) ($_POST['action'] ?? '');
$status = (string) ($order['status'] ?? 'Створено');
$nextStatus = null;
$message = '';
$materialUsage = [];

switch ($action) {
    case 'designer_accept':
        if (!orderIsDesigner($user) || $status !== 'Створено') break;
        $claim = $ordersCollection->updateOne(
            ['_id' => $objectId, 'status' => 'Створено', '$or' => [['assignedDesignerId' => ['$exists' => false]], ['assignedDesignerId' => null]]],
            ['$set' => ['status' => 'Підготовка макету', 'assignedDesignerId' => $user['_id'], 'assignedDesignerUsername' => (string) ($user['Логін'] ?? ''), 'assignedDesignerRole' => (string) ($user['Роль'] ?? ''), 'updatedAt' => new MongoDB\BSON\UTCDateTime()], '$push' => ['history' => orderLogEntry($user, 'Дизайнер прийняв замовлення в роботу.')]]
        );
        header('Location: ../order.php?id=' . rawurlencode($id)); exit;
    case 'designer_release':
        if (!orderIsDesigner($user) || $status !== 'Підготовка макету' || !orderDesignerOwns($order, $user)) break;
        $released = $ordersCollection->updateOne(
            ['_id' => $objectId, 'status' => 'Підготовка макету', 'assignedDesignerId' => $user['_id']],
            ['$set' => ['status' => 'Створено', 'updatedAt' => new MongoDB\BSON\UTCDateTime()], '$unset' => ['assignedDesignerId' => '', 'assignedDesignerUsername' => '', 'assignedDesignerRole' => ''], '$push' => ['history' => orderLogEntry($user, 'Дизайнер відмовився від замовлення. Його може прийняти інший дизайнер.')]]
        );
        header('Location: ../order.php?id=' . rawurlencode($id)); exit;
    case 'designer_finish':
        if (!orderIsDesigner($user) || $status !== 'Підготовка макету' || !orderDesignerOwns($order, $user)) break;
        $finished = $ordersCollection->updateOne(
            ['_id' => $objectId, 'status' => 'Підготовка макету', 'assignedDesignerId' => $user['_id']],
            ['$set' => ['status' => 'Очікує виконання', 'updatedAt' => new MongoDB\BSON\UTCDateTime()], '$push' => ['history' => orderLogEntry($user, 'Підготовку макету завершено. Замовлення очікує виконання.')]]
        );
        header('Location: ../order.php?id=' . rawurlencode($id)); exit;
    case 'printer_accept':
        if (!orderIsPrinter($user) || $status !== 'Очікує виконання') break;
        $nextStatus = 'В роботі'; $message = 'Друкар прийняв замовлення в роботу.'; break;
    case 'printer_wait_material':
        if (!orderIsPrinter($user) || $status !== 'В роботі') break;
        $nextStatus = 'Очікування матеріалу'; $message = 'Замовлення призупинено: очікування матеріалу.'; break;
    case 'printer_resume':
        if (!orderIsPrinter($user) || $status !== 'Очікування матеріалу') break;
        $nextStatus = 'В роботі'; $message = 'Матеріал надійшов. Друк відновлено.'; break;
    case 'printer_finish':
        if (!orderIsPrinter($user) || $status !== 'В роботі') break;
        $deductions = [];
        $chosenEntities = $_POST['entityId'] ?? [];
        $rotations = $_POST['rotated'] ?? [];
        if (!is_array($chosenEntities)) break;
        $valid = true;
        foreach (($order['items'] ?? []) as $index => $item) {
            $fileSize = $item['designerFile']['dimensionsMm'] ?? null;
            if (!$fileSize || empty($chosenEntities[$index])) { $valid = false; break; }
            try { $entityId = new ObjectId((string) $chosenEntities[$index]); } catch (Throwable $e) { $valid = false; break; }
            $entity = $stockCollection->findOne(['_id' => $entityId, 'materialName' => $item['materialName'] ?? '']);
            if (!$entity) { $valid = false; break; }
            if (!empty($item['materialType']) && (string) ($entity['type'] ?? '') !== (string) $item['materialType']) { $valid = false; break; }
            $wanted = []; foreach (($item['parameters'] ?? []) as $parameter) $wanted[(string) ($parameter['name'] ?? '')] = (string) ($parameter['value'] ?? '');
            foreach ($wanted as $parameterName => $value) if (orderIsSizeParameter((string) $parameterName)) unset($wanted[$parameterName]);
            $actual = []; foreach (($entity['parameters'] ?? []) as $parameter) {
                $parameterName = (string) ($parameter['name'] ?? '');
                if (!orderIsSizeParameter($parameterName)) $actual[$parameterName] = (string) ($parameter['value'] ?? '');
            }
            ksort($wanted); ksort($actual);
            if ($wanted !== $actual) { $valid = false; break; }
            $stockSize = orderEntityDimensionsMm($entity);
            if (!$stockSize) { $valid = false; break; }
            $printW = (float) $fileSize['width']; $printH = (float) $fileSize['height'];
            if (!empty($rotations[$index])) [$printW, $printH] = [$printH, $printW];
            if ($printW > $stockSize['width'] || $printH > $stockSize['height']) { $valid = false; break; }
            $amount = ($printW * $printH) / ($stockSize['width'] * $stockSize['height']) * 100;
            $key = (string) $entityId;
            if (!isset($deductions[$key])) $deductions[$key] = ['entity' => $entity, 'amount' => 0.0, 'items' => []];
            $deductions[$key]['amount'] += $amount;
            $deductions[$key]['items'][] = $index + 1;
            $materialUsage[$index] = ['stockEntityId' => $entityId, 'entityLabel' => orderEntityLabel($entity), 'rotated' => !empty($rotations[$index]), 'printWidthMm' => round($printW, 2), 'printHeightMm' => round($printH, 2), 'percentageUsed' => round($amount, 4), 'usedAt' => new MongoDB\BSON\UTCDateTime()];
        }
        if (!$valid) { header('Location: ../order.php?id=' . rawurlencode($id) . '&error=material_selection'); exit; }
        $updated = [];
        foreach ($deductions as $key => $deduction) {
            $entity = $deduction['entity'];
            $current = (float) ($entity['percentage'] ?? 0);
            if ($deduction['amount'] > $current || $current <= 0) { $valid = false; break; }
            $remaining = max(0, round($current - $deduction['amount'], 4));
            $result = $stockCollection->updateOne(['_id' => $entity['_id'], 'percentage' => $entity['percentage']], ['$set' => ['percentage' => $remaining, 'lastOrderId' => $objectId, 'updatedAt' => new MongoDB\BSON\UTCDateTime()]]);
            if ($result->getModifiedCount() !== 1) { $valid = false; break; }
            $updated[] = ['id' => $entity['_id'], 'amount' => $deduction['amount']];
        }
        if (!$valid) {
            foreach ($updated as $change) $stockCollection->updateOne(['_id' => $change['id']], ['$inc' => ['percentage' => $change['amount']]]);
            header('Location: ../order.php?id=' . rawurlencode($id) . '&error=material_selection'); exit;
        }
        $nextStatus = orderHasPostworks($order) ? 'Очікує постобробки' : 'Готове до видачі';
        $message = 'Використано матеріал зі складу; позиції: ' . implode(', ', array_map(static fn($d) => implode('/', $d['items']), array_values($deductions))) . '. ' . (orderHasPostworks($order) ? 'Друк завершено. Замовлення передано в чергу на постобробку.' : 'Друк завершено. Замовлення готове до видачі.');
        break;
    case 'postworker_accept':
        if (!orderIsPostworker($user) || $status !== 'Очікує постобробки') break;
        $nextStatus = 'Постобробка'; $message = 'Працівник постобробки прийняв замовлення.'; break;
    case 'postworker_finish':
        if (!orderIsPostworker($user) || $status !== 'Постобробка') break;
        $nextStatus = 'Готове до видачі'; $message = 'Постобробку завершено. Замовлення готове до видачі.'; break;
    case 'manager_complete':
        if (!orderIsResponsibleManager($order, $user) || $status !== 'Готове до видачі') break;
        $nextStatus = 'Готове'; $message = 'Менеджер закрив замовлення.'; break;
    case 'manager_cancel':
        if (!orderIsManager($user) || $status === 'Скасовано') break;
        $nextStatus = 'Скасовано'; $message = 'Менеджер скасував замовлення.'; break;
    case 'designer_upload':
        if (!orderIsDesigner($user) || !orderDesignerOwns($order, $user)) break;
        $index = filter_var($_POST['itemIndex'] ?? null, FILTER_VALIDATE_INT);
        if ($index === false || !isset($order['items'][$index])) break;
        try {
            $file = receiveOrderFile($_FILES['designerFile'] ?? null, __DIR__ . '/../src/orders/' . (string) $objectId . '/', 'designer-' . ($index + 1));
            if (!$file) break;
            $ordersCollection->updateOne(['_id' => $objectId, 'assignedDesignerId' => $user['_id']], [
                '$set' => ['items.' . $index . '.designerFile' => $file],
                '$push' => ['history' => orderLogEntry($user, 'Дизайнер завантажив новий файл для позиції ' . ($index + 1) . ': ' . $file['originalName'])]
            ]);
            header('Location: ../order.php?id=' . rawurlencode($id)); exit;
        } catch (Throwable $e) {
            header('Location: ../order.php?id=' . rawurlencode($id) . '&error=' . rawurlencode($e->getMessage() === 'file_too_large' ? 'file_too_large' : 'file_upload_failed')); exit;
        }
}

if ($nextStatus !== null) {
    $set = ['status' => $nextStatus, 'updatedAt' => new MongoDB\BSON\UTCDateTime()];
    foreach ($materialUsage as $index => $usage) $set['items.' . $index . '.materialUsage'] = $usage;
    $ordersCollection->updateOne(['_id' => $objectId], [
        '$set' => $set,
        '$push' => ['history' => orderLogEntry($user, $message)]
    ]);
}
header('Location: ../order.php?id=' . rawurlencode($id));
exit;
