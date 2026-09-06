<?php
session_start(); if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php'; use MongoDB\BSON\ObjectId;
$id = $_POST['id'] ?? ''; $folderId = $_POST['folder'] ?? '';
try { $rootId = new ObjectId($id); $root = $filesCollection->findOne(['_id' => $rootId]); } catch (Exception $e) { $root = null; }
if (!$root) { header('Location: ../files.php'); exit; }
try {
    $ids = [$rootId]; $queue = [$rootId];
    while ($queue) { $parentId = array_pop($queue); foreach ($filesCollection->find(['Батьківська папка' => $parentId]) as $child) { $ids[] = $child['_id']; $queue[] = $child['_id']; } }
    foreach ($filesCollection->find(['_id' => ['$in' => $ids]]) as $item) { if (!empty($item['Файл'])) { $path = __DIR__ . '/../src/files/' . basename($item['Файл']); if (is_file($path)) unlink($path); } }
    $filesCollection->deleteMany(['_id' => ['$in' => $ids]]);
} catch (Exception $e) {}
header('Location: ../files.php' . ($folderId !== '' ? '?folder=' . rawurlencode($folderId) : '')); exit;
