<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../../services/databaseConnect.php';
use MongoDB\BSON\ObjectId;

$id = $_POST['id'] ?? '';
$back = '../editCustomer.php?id=' . urlencode($id);
try {
    $customerId = new ObjectId($id);
} catch (Throwable $e) {
    header('Location: ../customers.php');
    exit;
}
$customer = $customersCollection->findOne(['_id' => $customerId]);
if (!$customer) {
    header('Location: ../customers.php');
    exit;
}
$type = $_POST['customerType'] ?? '';
$lastName = trim($_POST['customerLastName'] ?? '');
$firstName = trim($_POST['customerFirstName'] ?? '');
$middleName = trim($_POST['customerMiddleName'] ?? '');
$companyName = trim($_POST['customerCompanyName'] ?? '');
$phone = trim($_POST['customerPhone'] ?? '');
if (!in_array($type, ['individual', 'legal'], true) || ($type === 'individual' && ($lastName === '' || $firstName === '' || $middleName === '')) || ($type === 'legal' && $companyName === '')) {
    header("Location: $back&error=empty_name");
    exit;
}
if (!preg_match('/^\+380\d{9}$/', $phone)) {
    header("Location: $back&error=incorrect_phone_number_format");
    exit;
}
try {
    if ($customersCollection->findOne(['Номер телефону' => $phone, '_id' => ['$ne' => $customerId]])) {
        header("Location: $back&error=customer_exists");
        exit;
    }
    $set = ['Тип клієнта' => $type, 'Номер телефону' => $phone];
    if ($type === 'individual') {
        $set['Прізвище'] = $lastName;
        $set['Імʼя'] = $firstName;
        $set['По-батькові'] = $middleName;
    } else {
        $set['Назва компанії'] = $companyName;
    }
    $photoName = $customer['Фото'] ?? null;
    if (isset($_FILES['customerPhoto']) && $_FILES['customerPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $photo = $_FILES['customerPhoto'];
        if ($photo['error'] === UPLOAD_ERR_INI_SIZE || $photo['error'] === UPLOAD_ERR_FORM_SIZE || $photo['size'] > 20 * 1024 * 1024) {
            header("Location: $back&error=too_large_file");
            exit;
        }
        if ($photo['error'] !== UPLOAD_ERR_OK) {
            header("Location: $back&error=unknown_error");
            exit;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            header("Location: $back&error=unknown_error");
            exit;
        }
        $directory = __DIR__ . '/../src/customers/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create customer photo directory.');
        }
        $photoName = (string) $customerId . '.' . $extensions[$mime];
        if (!move_uploaded_file($photo['tmp_name'], $directory . $photoName)) {
            throw new RuntimeException('Could not save customer photo.');
        }
        if (!empty($customer['Фото']) && $customer['Фото'] !== $photoName && is_file($directory . $customer['Фото'])) {
            unlink($directory . $customer['Фото']);
        }
        $set['Фото'] = $photoName;
    }
    $unset = [];
    foreach (['Прізвище', 'Імʼя', 'По-батькові', 'Назва компанії'] as $field) {
        if (!array_key_exists($field, $set)) $unset[$field] = '';
    }
    if ($photoName !== null) $set['Фото'] = $photoName;
    $operations = ['$set' => $set];
    if ($unset) $operations['$unset'] = $unset;
    $customersCollection->updateOne(['_id' => $customerId], $operations);
    header('Location: ../customers.php');
    exit;
} catch (Throwable $e) {
    header("Location: $back&error=unknown_error");
    exit;
}
