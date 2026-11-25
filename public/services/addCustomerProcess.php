<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../services/databaseConnect.php';

$type = $_POST['customerType'] ?? '';
$lastName = trim($_POST['customerLastName'] ?? '');
$firstName = trim($_POST['customerFirstName'] ?? '');
$middleName = trim($_POST['customerMiddleName'] ?? '');
$companyName = trim($_POST['customerCompanyName'] ?? '');
$phone = trim($_POST['customerPhone'] ?? '');

if (!in_array($type, ['individual', 'legal'], true)) {
    header('Location: ../addCustomer.php?error=empty_name');
    exit;
}
if (($type === 'individual' && ($lastName === '' || $firstName === '' || $middleName === '')) || ($type === 'legal' && $companyName === '')) {
    header('Location: ../addCustomer.php?error=empty_name');
    exit;
}
if (!preg_match('/^\+380\d{9}$/', $phone)) {
    header('Location: ../addCustomer.php?error=incorrect_phone_number_format');
    exit;
}

try {
    if ($customersCollection->findOne(['Номер телефону' => $phone])) {
        header('Location: ../addCustomer.php?error=customer_exists');
        exit;
    }

    $customer = ['Тип клієнта' => $type, 'Номер телефону' => $phone];
    if ($type === 'individual') {
        $customer['Прізвище'] = $lastName;
        $customer['Імʼя'] = $firstName;
        $customer['По-батькові'] = $middleName;
    } else {
        $customer['Назва компанії'] = $companyName;
    }
    if (isset($_FILES['customerPhoto']) && $_FILES['customerPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $photo = $_FILES['customerPhoto'];
        if ($photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 20 * 1024 * 1024) {
            header('Location: ../addCustomer.php?error=unknown_error');
            exit;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            header('Location: ../addCustomer.php?error=unknown_error');
            exit;
        }
        $directory = __DIR__ . '/../src/customers/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create customer photo directory.');
        }
        $photoName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($photo['tmp_name'], $directory . $photoName)) {
            throw new RuntimeException('Could not save customer photo.');
        }
        $customer['Фото'] = $photoName;
    }
    $customersCollection->insertOne($customer);

    header('Location: ../customers.php');
    exit;
} catch (Throwable $e) {
    header('Location: ../addCustomer.php?error=unknown_error');
    exit;
}
