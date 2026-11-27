<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../../services/databaseConnect.php';
require_once __DIR__ . '/orderFunctions.php';
use MongoDB\BSON\ObjectId;
$user = $usersCollection->findOne(['Логін' => $_SESSION['username']]);
if (!$user) { session_destroy(); header('Location: ../login.php'); exit; }
try { $orderId = new ObjectId((string) ($_GET['id'] ?? '')); } catch (Throwable $e) { http_response_code(404); exit; }
$order = $ordersCollection->findOne(['_id' => $orderId]);
$itemIndex = filter_var($_GET['item'] ?? null, FILTER_VALIDATE_INT);
$type = (string) ($_GET['type'] ?? '');
if (!$order || $itemIndex === false || !isset($order['items'][$itemIndex]) || !in_array($type, ['manager', 'designer'], true)) { http_response_code(404); exit; }
$file = $order['items'][$itemIndex][$type . 'File'] ?? null;
if (!$file || empty($file['storedName'])) { http_response_code(404); exit; }
$path = __DIR__ . '/../src/orders/' . (string) $orderId . '/' . basename((string) $file['storedName']);
if (!is_file($path)) { http_response_code(404); exit; }
$downloadName = basename((string) ($file['originalName'] ?? 'order-file'));
header('Content-Type: ' . (string) ($file['mime'] ?? 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($path));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
