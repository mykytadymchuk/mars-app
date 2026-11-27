<?php

use MongoDB\BSON\UTCDateTime;

function orderRole($user): string
{
    $role = (string) ($user['Роль'] ?? '');
    foreach (['Менеджер' => 'менеджер', 'Дизайнер' => 'дизайнер', 'Друкар' => 'друкар', 'Макетник' => 'макетник'] as $stored => $normalized) {
        if ($role === $stored || $role === $normalized) return $normalized;
    }
    return function_exists('mb_strtolower') ? mb_strtolower($role, 'UTF-8') : strtolower($role);
}

function orderIsManager($user): bool { return orderRole($user) === 'менеджер'; }
function orderIsDesigner($user): bool { return orderRole($user) === 'дизайнер'; }
function orderDesignerOwns($order, $user): bool { return isset($order['assignedDesignerId'], $user['_id']) && (string) $order['assignedDesignerId'] === (string) $user['_id']; }
function orderIsPrinter($user): bool { return orderRole($user) === 'друкар'; }
function orderIsPostworker($user): bool { return orderRole($user) === 'макетник'; }
function orderVisibleStatuses($user): ?array
{
    if (orderIsManager($user)) return null;
    if (orderIsDesigner($user)) return ['Створено', 'Підготовка макету'];
    if (orderIsPrinter($user)) return ['Очікує виконання', 'В роботі', 'Очікування матеріалу'];
    if (orderIsPostworker($user)) return ['Очікує постобробки', 'Постобробка'];
    return [];
}
function orderCanView($order, $user): bool
{
    $statuses = orderVisibleStatuses($user);
    return $statuses === null || in_array((string) ($order['status'] ?? 'Створено'), $statuses, true);
}
function orderIsResponsibleManager($order, $user): bool
{
    if (!orderIsManager($user)) return false;
    $responsibleId = $order['createdBy']['userId'] ?? null;
    if ($responsibleId !== null && isset($user['_id']) && (string) $responsibleId === (string) $user['_id']) return true;
    $responsibleUsername = (string) ($order['createdBy']['username'] ?? '');
    return $responsibleUsername !== '' && $responsibleUsername === (string) ($user['Логін'] ?? '');
}
function orderText($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function orderIsSizeParameter(string $name): bool
{
    $name = function_exists('mb_strtolower') ? mb_strtolower(trim($name), 'UTF-8') : strtolower(trim($name));
    foreach (['розмір', 'формат', 'ширина', 'довжина', 'намотка', 'size', 'format', 'width', 'length', 'габарит'] as $part) {
        if (function_exists('mb_strpos') ? mb_strpos($name, $part, 0, 'UTF-8') !== false : strpos($name, $part) !== false) return true;
    }
    return false;
}

function orderFormatDateTime($value): string
{
    if ($value instanceof UTCDateTime) return $value->toDateTime()->setTimezone(new DateTimeZone('Europe/Kyiv'))->format('d.m.Y H:i');
    return '';
}

function orderValues($values): array
{
    if (is_array($values)) return $values;
    return $values instanceof Traversable ? iterator_to_array($values, false) : [];
}

function orderCustomerName($customer): string
{
    if (!$customer) return 'Клієнта видалено';
    if (!empty($customer['Назва компанії'])) return (string) $customer['Назва компанії'];
    return trim(implode(' ', array_filter([
        (string) ($customer['Прізвище'] ?? ''),
        (string) ($customer['Імʼя'] ?? ''),
        (string) ($customer['По-батькові'] ?? '')
    ], static fn($part) => $part !== '')));
}

function orderHasPostworks($order): bool
{
    foreach (($order['items'] ?? []) as $item) if (count(orderValues($item['postworks'] ?? [])) > 0) return true;
    return false;
}

function orderLogEntry($user, string $message): array
{
    return [
        'userId' => $user['_id'],
        'username' => (string) ($user['Логін'] ?? ''),
        'role' => (string) ($user['Роль'] ?? ''),
        'message' => $message,
        'createdAt' => new UTCDateTime()
    ];
}

function orderFileExtension(string $filename): string
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return preg_match('/^[a-z0-9]{1,10}$/', $extension) ? $extension : '';
}

function receiveOrderFile($upload, string $directory, string $prefix): array
{
    if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE) return [];
    if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE || $upload['size'] > 20 * 1024 * 1024) {
        throw new RuntimeException('file_too_large');
    }
    if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('file_upload_failed');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('file_upload_failed');
    $extension = orderFileExtension((string) $upload['name']);
    $storedName = $prefix . '-' . bin2hex(random_bytes(10)) . ($extension !== '' ? '.' . $extension : '');
    if (!move_uploaded_file($upload['tmp_name'], $directory . $storedName)) throw new RuntimeException('file_upload_failed');
    $filePath = $directory . $storedName;
    $result = ['storedName' => $storedName, 'originalName' => basename((string) $upload['name']), 'mime' => (new finfo(FILEINFO_MIME_TYPE))->file($filePath) ?: 'application/octet-stream'];
    $dimensions = orderFileDimensionsMm($filePath, $result['mime']);
    if ($dimensions) $result['dimensionsMm'] = $dimensions;
    return $result;
}

function orderFileDimensionsMm(string $path, string $mime): ?array
{
    $widthMm = $heightMm = null;
    $estimated = false;
    if ($mime === 'application/pdf') {
        $data = @file_get_contents($path, false, null, 0, 8 * 1024 * 1024);
        if (is_string($data) && preg_match('/\/(?:CropBox|MediaBox)\s*\[\s*(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)\s*\]/', $data, $match)) {
            $widthMm = abs((float) $match[3] - (float) $match[1]) * 25.4 / 72;
            $heightMm = abs((float) $match[4] - (float) $match[2]) * 25.4 / 72;
        }
    } elseif ($mime === 'image/svg+xml' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
        $xml = @simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
        if ($xml) {
            $attributes = $xml->attributes();
            $parse = static function ($value): ?float {
                if (preg_match('/^\s*([\d.]+)\s*(mm|cm|in|pt|px)?\s*$/i', (string) $value, $m)) {
                    $factor = ['mm' => 1, 'cm' => 10, 'in' => 25.4, 'pt' => 25.4 / 72, 'px' => 25.4 / 96, '' => 25.4 / 96];
                    return (float) $m[1] * $factor[strtolower($m[2] ?? '')];
                }
                return null;
            };
            $widthMm = $parse((string) ($attributes->width ?? ''));
            $heightMm = $parse((string) ($attributes->height ?? ''));
            if ((!$widthMm || !$heightMm) && preg_match('/^\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*$/', (string) ($attributes->viewBox ?? ''), $m)) {
                $widthMm = (float) $m[1] * 25.4 / 96; $heightMm = (float) $m[2] * 25.4 / 96;
            $estimated = true;
            }
        }
    } elseif (str_starts_with($mime, 'image/')) {
        $image = @getimagesize($path);
        if ($image) {
            $dpiX = $dpiY = 96;
            if (function_exists('exif_read_data') && in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
                $exif = @exif_read_data($path);
                if (is_array($exif) && isset($exif['XResolution'], $exif['YResolution'])) {
                    $toFloat = static function ($value): float {
                        $parts = explode('/', (string) $value, 2);
                        return (float) $parts[0] / max(0.000001, (float) ($parts[1] ?? 1));
                    };
                    $unit = (int) ($exif['ResolutionUnit'] ?? 2);
                    if ($unit === 2 || $unit === 3) {
                        $factor = $unit === 3 ? 2.54 : 1;
                        $dpiX = $toFloat($exif['XResolution']) * $factor;
                        $dpiY = $toFloat($exif['YResolution']) * $factor;
                    }
                }
            }
            if ($dpiX === 96 && $dpiY === 96) $estimated = true;
            $widthMm = (float) $image[0] * 25.4 / max(1, $dpiX); $heightMm = (float) $image[1] * 25.4 / max(1, $dpiY);
        }
    }
    if (!$widthMm || !$heightMm || $widthMm > 100000 || $heightMm > 100000) return null;
    return ['width' => round($widthMm, 2), 'height' => round($heightMm, 2), 'estimated' => $estimated];
}

function orderEntityDimensionsMm($entity): ?array
{
    $dimensions = [];
    $rollWidth = $rollLength = null;
    $toMm = static function (string $number, string $unit): float {
        $unit = function_exists('mb_strtolower') ? mb_strtolower(trim($unit), 'UTF-8') : strtolower(trim($unit));
        $factor = in_array($unit, ['см', 'cm'], true) ? 10 : (in_array($unit, ['м', 'm', 'метр', 'метра', 'метрів'], true) ? 1000 : 1);
        return (float) str_replace(',', '.', $number) * $factor;
    };
    $isRoll = (string) ($entity['type'] ?? '') === 'roll';
    foreach (($entity['parameters'] ?? []) as $parameter) {
        $name = (string) ($parameter['name'] ?? '');
        $value = (string) ($parameter['value'] ?? '');
        if (!preg_match_all('/(\d+(?:[.,]\d+)?)\s*(мм|см|cm|метр(?:а|ів)?|м|m)?/iu', $value, $matches, PREG_SET_ORDER)) continue;
        $namedUnit = '';
        if (preg_match('/(мм|см|cm|метр(?:а|ів)?|м|m)\s*\)?\s*$/iu', trim($name), $unitMatch)) $namedUnit = $unitMatch[1];
        if ($isRoll && count($matches) === 1) {
            $normalizedName = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            $unit = ($matches[0][2] ?? '') !== '' ? $matches[0][2] : $namedUnit;
            if (str_contains($normalizedName, 'ширина') || str_contains($normalizedName, 'width')) $rollWidth = $toMm($matches[0][1], $unit);
            if (str_contains($normalizedName, 'намотка') || str_contains($normalizedName, 'довжина') || str_contains($normalizedName, 'length')) $rollLength = $toMm($matches[0][1], $unit);
        }
        if (count($matches) >= 2) {
            $fallbackUnit = $namedUnit;
            foreach ($matches as $match) if (!empty($match[2])) $fallbackUnit = $match[2];
            $units = [];
            foreach (array_slice($matches, 0, 2) as $match) $units[] = $toMm($match[1], ($match[2] ?? '') !== '' ? $match[2] : $fallbackUnit);
            if ($units[0] > 0 && $units[1] > 0) {
                if ($isRoll) { $rollWidth ??= $units[0]; $rollLength ??= $units[1]; }
                else return ['width' => $units[0], 'height' => $units[1]];
            }
        } elseif (orderIsSizeParameter($name)) {
            $unit = ($matches[0][2] ?? '') !== '' ? $matches[0][2] : $namedUnit;
            $dimensions[] = $toMm($matches[0][1], $unit);
        }
    }
    if ($isRoll && $rollWidth > 0 && $rollLength > 0) return ['width' => $rollWidth, 'height' => $rollLength];
    return count($dimensions) >= 2 && $dimensions[0] > 0 && $dimensions[1] > 0 ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : null;
}

function orderEntityLabel($entity): string
{
    $parts = [];
    foreach (($entity['parameters'] ?? []) as $parameter) $parts[] = (string) ($parameter['value'] ?? '');
    return implode(' · ', $parts) . ' — ' . rtrim(rtrim(number_format((float) ($entity['percentage'] ?? 0), 2, '.', ''), '0'), '.') . '%';
}
