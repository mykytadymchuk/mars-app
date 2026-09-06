<?php
session_start(); if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php'; use MongoDB\BSON\ObjectId;

$name = trim($_POST['name'] ?? ''); $type = $_POST['type'] ?? ''; $folderId = $_POST['folder'] ?? '';
$parent = null; $back = '../addFile.php';
if ($folderId !== '') { try { $parent = $filesCollection->findOne(['_id' => new ObjectId($folderId), 'Тип' => 'folder']); } catch (Exception $e) {} if (!$parent) { header('Location: ../files.php'); exit; } $back .= '?folder=' . rawurlencode($folderId); }
function fail(string $back, string $error): never { header("Location: $back" . (str_contains($back, '?') ? '&' : '?') . "error=$error"); exit; }
if ($name === '') fail($back, 'empty_name'); if (!in_array($type, ['file', 'folder'], true)) fail($back, 'invalid_file');
try {
    $parentId = $parent['_id'] ?? null;
    if ($filesCollection->findOne(['Батьківська папка' => $parentId, 'Назва' => $name])) fail($back, 'item_exists');
    $document = ['_id' => new ObjectId(), 'Назва' => $name, 'Тип' => $type, 'Батьківська папка' => $parentId, 'Дата завантаження' => (new DateTimeImmutable('today', new DateTimeZone('Europe/Kyiv')))->format('Y-m-d')];
    if ($type === 'file') {
        $upload = $_FILES['uploadedFile'] ?? null;
        if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE) fail($back, 'file_required');
        if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE || $upload['size'] > 20 * 1024 * 1024) fail($back, 'file_too_large');
        if ($upload['error'] !== UPLOAD_ERR_OK) fail($back, 'invalid_file');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if (!$mime) fail($back, 'invalid_file');
        $extension = pathinfo($upload['name'], PATHINFO_EXTENSION); $storedName = (string) $document['_id'] . ($extension !== '' ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $extension) : '');
        $directory = __DIR__ . '/../src/files/'; if (!is_dir($directory) && !mkdir($directory, 0755, true)) fail($back, 'invalid_file');
        if (!move_uploaded_file($upload['tmp_name'], $directory . $storedName)) fail($back, 'invalid_file');
        $document['Файл'] = $storedName; $document['Оригінальна назва'] = basename($upload['name']); $document['MIME тип'] = $mime;
    }
    $filesCollection->insertOne($document);
    header('Location: ../files.php' . ($parent ? '?folder=' . rawurlencode((string) $parent['_id']) : '')); exit;
} catch (Exception $e) { fail($back, 'invalid_file'); }
