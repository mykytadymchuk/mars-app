<?php
session_start(); if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php'; use MongoDB\BSON\ObjectId;
$id = $_POST['id'] ?? ''; $name = trim($_POST['name'] ?? '');
try { $objectId = new ObjectId($id); $item = $filesCollection->findOne(['_id' => $objectId]); } catch (Exception $e) { $item = null; }
if (!$item) { header('Location: ../files.php'); exit; }
$back = '../editFile.php?id=' . rawurlencode($id); if ($name === '') { header("Location: $back&error=empty_name"); exit; }
try { if ($filesCollection->findOne(['Батьківська папка' => $item['Батьківська папка'] ?? null, 'Назва' => $name, '_id' => ['$ne' => $objectId]])) { header("Location: $back&error=item_exists"); exit; } $filesCollection->updateOne(['_id' => $objectId], ['$set' => ['Назва' => $name]]); header('Location: ../files.php' . (!empty($item['Батьківська папка']) ? '?folder=' . rawurlencode((string) $item['Батьківська папка']) : '')); exit; } catch (Exception $e) { header("Location: $back&error=empty_name"); exit; }
